<?php

namespace App\Controllers\Customer;

use App\Controllers\BaseController;
use App\Models\CartItemModel;
use App\Models\CartModel;
use App\Models\ProductModel;
use App\Models\ProductVariantModel;
use App\Services\Catalog\PricingService;

class CartController extends BaseController
{
    protected CartModel $cartModel;
    protected CartItemModel $cartItemModel;
    protected ProductModel $productModel;
    protected ProductVariantModel $variantModel;
    protected PricingService $pricingService;

    public function __construct()
    {
        $this->cartModel      = new CartModel();
        $this->cartItemModel  = new CartItemModel();
        $this->productModel   = new ProductModel();
        $this->variantModel   = new ProductVariantModel();
        $this->pricingService = new PricingService();
    }

    protected function getOrCreateUserCart(): array
    {
        $userId = session()->get('user.id');
        $sessionId = session_id();
        return $this->cartModel->getOrCreateCart($userId ? (int) $userId : null, $sessionId);
    }

    public function index()
    {
        $cart = $this->getOrCreateUserCart();
        $items = $this->cartItemModel->getItemsWithProducts((int) $cart['id']);

        $subtotal = 0.0;
        foreach ($items as $item) {
            $subtotal += ((float) $item['unit_price'] * (int) $item['quantity']);
        }

        $estimatedCashback = 0.0;
        try {
            $estimatedCashback = cart_cashback_total($items);
        } catch (\Throwable $e) {
            $estimatedCashback = 0.0;
        }

        \App\Services\Marketing\MarketingService::trackCartItems('ViewCart', $items, ['value' => $subtotal]);

        return view('customer/cart', [
            'title'           => 'Shopping Cart — Solqam Marketplace',
            'cart'            => $cart,
            'items'           => $items,
            'subtotal'        => $subtotal,
            'estimatedCashback' => $estimatedCashback,
        ]);
    }

    public function add()
    {
        $productId = (int) $this->request->getPost('product_id');
        $quantity  = max(1, (int) ($this->request->getPost('quantity') ?? 1));
        $variantId = (int) ($this->request->getPost('variant_id') ?? 0) ?: null;

        $product = $this->productModel->find($productId);
        if (!$product || $product['status'] !== 'active') {
            return redirect()->back()->with('error', 'Product not available.');
        }
        if (! (new \App\Models\SellerProfileModel())->isApprovedForUser((int) $product['seller_id'])) {
            return redirect()->back()->with('error', 'This seller is not yet approved on Solqam.');
        }

        $variant = null;
        $variants = $this->variantModel->forProduct($productId);
        if (! empty($variants)) {
            if (! $variantId) {
                return redirect()->back()->with('error', 'Please choose a color and size.');
            }
            $variant = $this->variantModel->where('id', $variantId)->where('product_id', $productId)->first();
            if (! $variant) {
                return redirect()->back()->with('error', 'Please select a valid color / size.');
            }
        } elseif ($variantId) {
            return redirect()->back()->with('error', 'Please select a valid color / size.');
        }

        $stock = $variant ? (int) $variant['stock'] : (int) $product['stock'];
        if ($stock < $quantity) {
            return redirect()->back()->with('error', "Only {$stock} units available in stock.");
        }

        $unitPrice = $this->pricingService->resolveUnitPrice($product, $variant);
        $cart = $this->getOrCreateUserCart();
        $cartId = (int) $cart['id'];

        $existingQuery = $this->cartItemModel
            ->where('cart_id', $cartId)
            ->where('product_id', $productId);
        if ($variantId) {
            $existingQuery->where('variant_id', $variantId);
        } else {
            $existingQuery->where('variant_id', null);
        }
        $existing = $existingQuery->first();

        if ($existing) {
            $newQty = $existing['quantity'] + $quantity;
            if ($newQty > $stock) {
                $newQty = $stock;
            }
            $this->cartItemModel->update($existing['id'], [
                'quantity'   => $newQty,
                'unit_price' => $unitPrice,
            ]);
        } else {
            $this->cartItemModel->insert([
                'cart_id'    => $cartId,
                'product_id' => $productId,
                'variant_id' => $variantId,
                'quantity'   => $quantity,
                'unit_price' => $unitPrice,
            ]);
        }

        if (function_exists('nav_forget')) {
            nav_forget(['cart']);
        }
        \App\Services\Marketing\MarketingService::trackProduct('AddToCart', $product, [
            'quantity' => $quantity,
            'price'    => $unitPrice,
        ]);

        $buyNow = (string) $this->request->getPost('buy_now') === '1';
        if ($buyNow) {
            return redirect()->to('/checkout')->with('success', 'Proceed to checkout.');
        }

        return redirect()->to('/cart')->with('success', 'Added to cart!');
    }

    public function update()
    {
        $itemId   = (int) $this->request->getPost('item_id');
        $quantity = (int) $this->request->getPost('quantity');

        $cart = $this->getOrCreateUserCart();
        $item = $this->cartItemModel
            ->where('id', $itemId)
            ->where('cart_id', $cart['id'])
            ->first();

        if ($item) {
            if ($quantity <= 0) {
                $p = $this->productModel->find((int) $item['product_id']);
                if ($p) {
                    \App\Services\Marketing\MarketingService::trackProduct('RemoveFromCart', $p, [
                        'quantity' => (int) $item['quantity'],
                        'price'    => (float) $item['unit_price'],
                    ]);
                }
                $this->cartItemModel->delete($itemId);
            } else {
                $stock = (int) $this->productModel->find($item['product_id'])['stock'];
                if (!empty($item['variant_id'])) {
                    $variant = $this->variantModel->find($item['variant_id']);
                    $stock = (int) ($variant['stock'] ?? 0);
                }
                if ($quantity > $stock) {
                    $quantity = $stock;
                }
                $this->cartItemModel->update($itemId, ['quantity' => $quantity]);
            }
        }

        if (function_exists('nav_forget')) {
            nav_forget(['cart']);
        }

        return redirect()->to('/cart')->with('success', 'Cart updated.');
    }

    public function remove($itemId)
    {
        $cart = $this->getOrCreateUserCart();
        $item = $this->cartItemModel->where('id', (int) $itemId)->where('cart_id', $cart['id'])->first();
        if ($item) {
            $p = $this->productModel->find((int) $item['product_id']);
            if ($p) {
                \App\Services\Marketing\MarketingService::trackProduct('RemoveFromCart', $p, [
                    'quantity' => (int) $item['quantity'],
                    'price'    => (float) $item['unit_price'],
                ]);
            }
        }
        $this->cartItemModel
            ->where('id', (int) $itemId)
            ->where('cart_id', $cart['id'])
            ->delete();

        if (function_exists('nav_forget')) {
            nav_forget(['cart']);
        }

        return redirect()->to('/cart')->with('success', 'Item removed.');
    }
}
