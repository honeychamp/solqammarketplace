<?php

namespace App\Controllers\Customer;

use App\Controllers\BaseController;
use App\Models\CartItemModel;
use App\Models\CartModel;
use App\Models\ProductModel;
use App\Models\ProductVariantModel;
use App\Services\Catalog\PricingService;
use App\Services\Checkout\GiftWrapService;

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
        $rawItems = $this->cartItemModel->getItemsWithProducts((int) $cart['id']);
        $items = class_exists(GiftWrapService::class) ? GiftWrapService::enrichItems($rawItems) : $rawItems;
        $subtotal = 0.0;
        foreach ($items as $item) {
            $subtotal += ((float) ($item['unit_price'] ?? 0) * (int) ($item['quantity'] ?? 1));
        }
        $giftWrapTotal = class_exists(GiftWrapService::class) ? GiftWrapService::wrapTotal($items) : 0.0;

        $estimatedCashback = 0.0;
        try {
            $estimatedCashback = session_gets_cashback() ? cart_cashback_total($items) : 0.0;
        } catch (\Throwable $e) {
            $estimatedCashback = 0.0;
        }

        \App\Services\Marketing\MarketingService::trackCartItems('ViewCart', $items, ['value' => $subtotal]);

        return view('customer/cart', [
            'title'           => 'Shopping Cart — Solqam Marketplace',
            'cart'            => $cart,
            'items'           => $items,
            'subtotal'          => $subtotal,
            'giftWrapTotal'     => $giftWrapTotal,
            'giftWrapEnabled'   => class_exists(GiftWrapService::class) && GiftWrapService::enabled(),
            'giftWrapFee'       => class_exists(GiftWrapService::class) ? GiftWrapService::unitFee() : 150.0,
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

        $unitPrice = $this->pricingService->resolveUnitPrice($product, $variant);
        $cart = $this->getOrCreateUserCart();
        $cartId = (int) $cart['id'];
        $giftReady = class_exists(GiftWrapService::class) && GiftWrapService::cartReady();
        $wantGift = $giftReady && GiftWrapService::enabled() && (string) $this->request->getPost('gift_wrap') === '1';
        $giftMessage = $wantGift ? GiftWrapService::sanitizeMessage((string) $this->request->getPost('gift_message')) : null;
        $giftHide = $wantGift && (string) $this->request->getPost('gift_hide_price') === '1';

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
            $payload = [
                'quantity'   => $newQty,
                'unit_price' => $unitPrice,
            ];
            if ($wantGift && $giftReady) {
                $payload['gift_wrap'] = 1;
                $payload['gift_message'] = $giftMessage ?: ($existing['gift_message'] ?? null);
                $payload['gift_hide_price'] = $giftHide ? 1 : (int) ($existing['gift_hide_price'] ?? 0);
            }
            $this->cartItemModel->update($existing['id'], $payload);
        } else {
            $row = [
                'cart_id'    => $cartId,
                'product_id' => $productId,
                'variant_id' => $variantId,
                'quantity'   => $quantity,
                'unit_price' => $unitPrice,
            ];
            if ($giftReady) {
                $row['gift_wrap'] = $wantGift ? 1 : 0;
                $row['gift_message'] = $giftMessage;
                $row['gift_hide_price'] = $giftHide ? 1 : 0;
            }
            try {
                $this->cartItemModel->insert($row);
            } catch (\Throwable $e) {
                unset($row['gift_wrap'], $row['gift_message'], $row['gift_hide_price']);
                $this->cartItemModel->insert($row);
            }
        }

        if (function_exists('nav_forget')) {
            nav_forget(['cart']);
        }
        \App\Services\Marketing\MarketingService::trackProduct('AddToCart', $product, [
            'quantity'    => $quantity,
            'price'       => $unitPrice,
            'variant_id'  => $variantId,
            'phone'       => (string) ($this->request->getPost('phone') ?: session()->get('user.phone') ?? ''),
            'email'       => (string) (session()->get('user.email') ?? ''),
            'external_id' => (string) (session()->get('user.id') ?? ''),
            'fbp'         => (string) ($this->request->getPost('fbp') ?? ''),
            'fbc'         => (string) ($this->request->getPost('fbc') ?? ''),
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
                $this->cartItemModel->update($itemId, ['quantity' => max(1, min(99, $quantity))]);
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

    public function gift()
    {
        $cart = $this->getOrCreateUserCart();
        if (class_exists(GiftWrapService::class) && GiftWrapService::cartReady()) {
            GiftWrapService::applyCartPost((int) $cart['id'], $this->request->getPost() ?? []);
        }

        $back = (string) $this->request->getPost('redirect');
        if ($back === 'checkout') {
            return redirect()->to('/checkout')->with('success', 'Gift options saved.');
        }

        return redirect()->to('/cart')->with('success', 'Gift options saved.');
    }
}
