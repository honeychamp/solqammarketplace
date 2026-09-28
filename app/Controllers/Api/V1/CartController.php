<?php

namespace App\Controllers\Api\V1;

use App\Models\CartItemModel;
use App\Models\CartModel;
use App\Models\ProductModel;

class CartController extends BaseApiController
{
    protected CartModel $cartModel;
    protected CartItemModel $cartItemModel;
    protected ProductModel $productModel;

    public function __construct()
    {
        $this->cartModel     = new CartModel();
        $this->cartItemModel = new CartItemModel();
        $this->productModel  = new ProductModel();
    }

    protected function resolveCart(): array
    {
        $user = $this->getAuthenticatedUser();
        $userId = $user ? (int) $user['id'] : null;
        $sessionId = session_id();

        return $this->cartModel->getOrCreateCart($userId, $sessionId);
    }

    public function index()
    {
        $cart = $this->resolveCart();
        $items = $this->cartItemModel->getItemsWithProducts((int) $cart['id']);

        $subtotal = 0.0;
        foreach ($items as $item) {
            $subtotal += ((float) $item['unit_price'] * (int) $item['quantity']);
        }

        return $this->respondSuccess([
            'cart_id'  => (int) $cart['id'],
            'items'    => $items,
            'subtotal' => round($subtotal, 2),
            'count'    => count($items),
        ], 'Cart retrieved.');
    }

    public function add()
    {
        $input = $this->request->getJSON(true) ?? $this->request->getPost();
        $productId = (int) ($input['product_id'] ?? 0);
        $quantity  = max(1, (int) ($input['quantity'] ?? 1));

        $product = $this->productModel->find($productId);
        if (!$product || $product['status'] !== 'active') {
            return $this->respondFail('Product not available.', null, 404);
        }

        if ($product['stock'] < $quantity) {
            return $this->respondFail("Only {$product['stock']} units available in stock.", null, 400);
        }

        $cart = $this->resolveCart();
        $cartId = (int) $cart['id'];

        // Check if item already in cart
        $existing = $this->cartItemModel
            ->where('cart_id', $cartId)
            ->where('product_id', $productId)
            ->first();

        if ($existing) {
            $newQty = $existing['quantity'] + $quantity;
            if ($newQty > $product['stock']) {
                $newQty = $product['stock'];
            }
            $this->cartItemModel->update($existing['id'], [
                'quantity'   => $newQty,
                'unit_price' => $product['price'],
            ]);
        } else {
            $this->cartItemModel->insert([
                'cart_id'    => $cartId,
                'product_id' => $productId,
                'quantity'   => $quantity,
                'unit_price' => $product['price'],
            ]);
        }

        return $this->index();
    }

    public function updateItem()
    {
        $input = $this->request->getJSON(true) ?? $this->request->getPost();
        $itemId   = (int) ($input['item_id'] ?? 0);
        $quantity = (int) ($input['quantity'] ?? 1);

        $cart = $this->resolveCart();
        $item = $this->cartItemModel
            ->where('id', $itemId)
            ->where('cart_id', $cart['id'])
            ->first();

        if (!$item) {
            return $this->respondFail('Cart item not found.', null, 404);
        }

        if ($quantity <= 0) {
            $this->cartItemModel->delete($itemId);
        } else {
            $product = $this->productModel->find($item['product_id']);
            if ($quantity > $product['stock']) {
                $quantity = $product['stock'];
            }
            $this->cartItemModel->update($itemId, ['quantity' => $quantity]);
        }

        return $this->index();
    }

    public function removeItem()
    {
        $input = $this->request->getJSON(true) ?? $this->request->getPost();
        $itemId = (int) ($input['item_id'] ?? 0);

        $cart = $this->resolveCart();
        $item = $this->cartItemModel
            ->where('id', $itemId)
            ->where('cart_id', $cart['id'])
            ->first();

        if ($item) {
            $this->cartItemModel->delete($itemId);
        }

        return $this->index();
    }
}
