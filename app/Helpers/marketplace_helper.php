<?php

if (! function_exists('storefront_remember')) {
    /**
     * @template T
     * @param callable(): T $maker
     * @return T
     */
    function storefront_remember(string $key, int $ttl, callable $maker)
    {
        try {
            $cache = cache();
            $hit   = $cache->get($key);
            if ($hit !== null) {
                return $hit;
            }
            $value = $maker();
            $cache->save($key, $value, $ttl);

            return $value;
        } catch (\Throwable $e) {
            return $maker();
        }
    }
}

if (! function_exists('storefront_forget')) {
    function storefront_forget($keys): void
    {
        try {
            $cache = cache();
            foreach ((array) $keys as $key) {
                $cache->delete((string) $key);
            }
        } catch (\Throwable $e) {
        }
    }
}

if (!function_exists('category_tree')) {
    function category_tree(): array
    {
        return storefront_remember('sf_category_tree', 600, static function (): array {
            try {
                return (new \App\Models\CategoryModel())->getTree();
            } catch (\Throwable $e) {
                return [];
            }
        });
    }
}

if (! function_exists('header_nav_state')) {
    /**
     * @return array{user:?array, cartCount:int, wishlistCount:int, walletBalance:?float}
     */
    function header_nav_state(): array
    {
        $session = session();
        $user    = $session->get('user');
        if (! is_array($user) || empty($user['id'])) {
            $user = null;
        }
        $uid = (int) ($user['id'] ?? 0);
        $now = time();
        $cached = $session->get('nav_state');
        if (
            is_array($cached)
            && (int) ($cached['t'] ?? 0) > $now - 45
            && (int) ($cached['uid'] ?? -1) === $uid
            && ($cached['sid'] ?? '') === session_id()
        ) {
            return [
                'user'          => $user,
                'cartCount'     => (int) ($cached['cart'] ?? 0),
                'wishlistCount' => (int) ($cached['wl'] ?? 0),
                'walletBalance' => array_key_exists('wallet', $cached) ? $cached['wallet'] : null,
            ];
        }

        $cartCount = 0;
        $wishlist  = 0;
        $wallet    = null;
        try {
            $cartModel = new \App\Models\CartModel();
            $itemModel = new \App\Models\CartItemModel();
            $cart      = $uid > 0
                ? $cartModel->where('user_id', $uid)->first()
                : $cartModel->where('session_id', session_id())->first();
            if ($cart) {
                $cartCount = (int) $itemModel->where('cart_id', $cart['id'])->countAllResults();
            }
            if ($uid > 0) {
                $wishlist = (int) (new \App\Models\WishlistModel())->where('user_id', $uid)->countAllResults();
                try {
                    $wallet = (new \App\Services\Wallet\WalletService())->getBalance($uid);
                } catch (\Throwable $e) {
                    $wallet = 0.0;
                }
            }
        } catch (\Throwable $e) {
        }

        $session->set('nav_state', [
            't'      => $now,
            'uid'    => $uid,
            'sid'    => session_id(),
            'cart'   => $cartCount,
            'wl'     => $wishlist,
            'wallet' => $wallet,
        ]);

        return [
            'user'          => $user,
            'cartCount'     => $cartCount,
            'wishlistCount' => $wishlist,
            'walletBalance' => $wallet,
        ];
    }
}

if (!function_exists('wishlist_count')) {
    function wishlist_count(): int
    {
        return (int) (header_nav_state()['wishlistCount'] ?? 0);
    }
}

if (!function_exists('cashback_rate')) {
    function cashback_rate($source = null): float
    {
        if (is_array($source)) {
            if (isset($source['cashback_percent']) && $source['cashback_percent'] !== '' && $source['cashback_percent'] !== null) {
                return max(0.0, min(100.0, (float) $source['cashback_percent']));
            }
        } elseif ($source !== null && $source !== '') {
            return max(0.0, min(100.0, (float) $source));
        }

        return 0.0;
    }
}

if (!function_exists('cashback_percent_label')) {
    function cashback_percent_label($source = null): string
    {
        $rate = cashback_rate($source);
        $label = rtrim(rtrim(number_format($rate, 1, '.', ''), '0'), '.');

        return ($label === '' ? '0' : $label) . '%';
    }
}

if (!function_exists('delivery_hint')) {
    function delivery_hint(): array
    {
        return storefront_remember('sf_delivery_hint', 300, static function (): array {
            try {
                return (new \App\Services\Shipping\ShippingService())->publicHint();
            } catch (\Throwable $e) {
                return ['has_rates' => false, 'min_rate' => 0.0, 'max_rate' => 0.0, 'label' => 'At checkout'];
            }
        });
    }
}

if (!function_exists('delivery_tag_html')) {
    function delivery_tag_html(): string
    {
        $hint = delivery_hint();
        if (empty($hint['has_rates'])) {
            return '<span class="delivery-rate-tag">Delivery at checkout</span>';
        }

        return '<span class="delivery-rate-tag">' . esc($hint['label']) . ' delivery</span>';
    }
}

if (!function_exists('cashback_chip')) {
    function cashback_chip($source = null): string
    {
        return '<span class="cashback-chip">' . htmlspecialchars(cashback_percent_label($source), ENT_QUOTES, 'UTF-8') . ' Cashback</span>';
    }
}

if (!function_exists('session_gets_cashback')) {
    function session_gets_cashback(): bool
    {
        try {
            return (bool) session()->get('user.isLoggedIn')
                && (string) session()->get('user.role') === 'customer';
        } catch (\Throwable $e) {
            return false;
        }
    }
}

if (!function_exists('cashback_amount')) {
    function cashback_amount(float $goodsTotal, $rate = null): float
    {
        return round($goodsTotal * (cashback_rate($rate) / 100), 2);
    }
}

if (!function_exists('cart_cashback_total')) {
    function cart_cashback_total(array $items): float
    {
        $total = 0.0;
        foreach ($items as $item) {
            $line = isset($item['subtotal'])
                ? (float) $item['subtotal']
                : ((float) ($item['unit_price'] ?? 0) * (int) ($item['quantity'] ?? 1));
            $total += cashback_amount($line, $item);
        }

        return round($total, 2);
    }
}

if (!function_exists('commission_rate')) {
    function commission_rate(?int $categoryId = null): float
    {
        try {
            $service = new \App\Services\Commission\CommissionService();

            return $categoryId
                ? $service->rateForCategory($categoryId)
                : $service->getCommissionRate();
        } catch (\Throwable $e) {
            return 10.0;
        }
    }
}

if (!function_exists('commission_amount')) {
    function commission_amount(float $goodsTotal, bool $isAdminSeller = false, ?float $rate = null): float
    {
        if ($isAdminSeller) {
            return 0.0;
        }

        $pct = $rate === null ? commission_rate() : max(0.0, min(50.0, $rate));

        return round($goodsTotal * ($pct / 100), 2);
    }
}

if (!function_exists('item_cashback')) {
    function item_cashback(array $item): float
    {
        if (array_key_exists('cashback_amount', $item) && $item['cashback_amount'] !== null && $item['cashback_amount'] !== '') {
            return round((float) $item['cashback_amount'], 2);
        }

        return cashback_amount((float) ($item['subtotal'] ?? 0));
    }
}

if (!function_exists('item_seller_net')) {
    function item_seller_net(array $item): float
    {
        $goods = (float) ($item['subtotal'] ?? 0) + (float) ($item['gift_wrap_fee'] ?? 0);
        $commission = (float) ($item['commission_amount'] ?? 0);

        return round(max(0.0, $goods - $commission - item_cashback($item)), 2);
    }
}

if (! function_exists('hub_stock_cell')) {
    function hub_stock_cell($stock): string
    {
        $n = (int) $stock;
        if ($n <= 0) {
            return '<span class="badge bg-danger">Out of stock</span><div class="small text-danger mt-1">Add stock so you can deliver pending orders</div>';
        }
        if ($n <= 5) {
            return '<span class="badge bg-warning text-dark">' . $n . ' low</span>';
        }

        return '<span class="badge bg-success-subtle text-success border border-success-subtle">' . $n . ' units</span>';
    }
}

if (!function_exists('is_online_gateway')) {
    function is_online_gateway(?string $method): bool
    {
        return in_array((string) $method, ['payfast', 'jazzcash', 'easypaisa', 'card'], true);
    }
}

if (! function_exists('payment_method_label')) {
    function payment_method_label(?string $method): string
    {
        return match ((string) $method) {
            'cod' => 'Cash on Delivery',
            'payfast', 'jazzcash', 'easypaisa', 'card' => 'Pay online (PayFast)',
            'wallet' => 'Wallet',
            'pay_later' => 'Pay later',
            default => $method !== '' ? strtoupper($method) : 'Cash on Delivery',
        };
    }
}

if (!function_exists('sms_demo_mode')) {
    function sms_demo_mode(): bool
    {
        $v = strtolower((string) env('sms.demoBypass', 'false'));

        return in_array($v, ['1', 'true', 'yes', 'on'], true);
    }
}

if (!function_exists('pk_msisdn')) {
    function pk_msisdn(string $phone): string
    {
        $digits = preg_replace('/\D+/', '', $phone) ?? '';
        if (str_starts_with($digits, '0092')) {
            $digits = substr($digits, 2);
        }
        if (str_starts_with($digits, '0') && strlen($digits) === 11) {
            $digits = '92' . substr($digits, 1);
        } elseif (strlen($digits) === 10) {
            $digits = '92' . $digits;
        }

        return $digits;
    }
}

if (!function_exists('seller_approval_status')) {
    function seller_approval_status(?int $userId = null): string
    {
        $userId = $userId ?? (int) (session()->get('user.id') ?? 0);
        if ($userId <= 0) {
            return '';
        }
        try {
            $profile = (new \App\Models\SellerProfileModel())->getByUserId($userId);

            return (string) ($profile['approval_status'] ?? '');
        } catch (\Throwable $e) {
            return (string) (session()->get('user.approval_status') ?? '');
        }
    }
}

if (!function_exists('seller_is_approved')) {
    function seller_is_approved(?int $userId = null): bool
    {
        return seller_approval_status($userId) === 'approved';
    }
}

if (!function_exists('register_keep')) {
    function register_keep(string $key): string
    {
        $draft = session()->get('register_draft') ?? [];
        $old   = old($key, '');
        $value = is_string($old) && trim(html_entity_decode($old, ENT_QUOTES)) !== ''
            ? html_entity_decode($old, ENT_QUOTES)
            : (string) ($draft[$key] ?? '');
        if ($key === 'phone') {
            $digits = preg_replace('/\D+/', '', $value) ?? '';
            if (strlen($digits) === 11 && str_starts_with($digits, '0')) {
                $digits = substr($digits, 1);
            }
            $value = substr($digits, 0, 10);
        }

        return esc($value);
    }
}

if (!function_exists('form_keep_raw')) {
    function form_keep_raw(string $bag, string $key): string
    {
        $draft = session()->get($bag) ?? [];
        $old   = old($key, '');
        if (is_string($old) && trim(html_entity_decode($old, ENT_QUOTES)) !== '') {
            return html_entity_decode($old, ENT_QUOTES);
        }

        return (string) ($draft[$key] ?? '');
    }
}

if (!function_exists('shipping_eta_label')) {
    function shipping_eta_label(?string $eta): string
    {
        $eta = trim((string) $eta);
        if ($eta === '') {
            return '';
        }
        if (stripos($eta, 'day') !== false) {
            return '(' . $eta . ')';
        }

        return '(' . $eta . ' days)';
    }
}

if (!function_exists('checkout_keep')) {
    function checkout_keep(string $key): string
    {
        return esc(form_keep_raw('checkout_draft', $key));
    }
}

if (!function_exists('media_url')) {
    function media_url(?string $path): string
    {
        $path = trim((string) $path);
        if ($path === '') {
            return '';
        }
        if (str_starts_with($path, '//')) {
            $path = 'https:' . $path;
        }
        if (preg_match('#^https?://#i', $path)) {
            $rel = ltrim((string) (parse_url($path, PHP_URL_PATH) ?? ''), '/');
            if (preg_match('#(?:^|/)(uploads/.+)$#', $rel, $m)) {
                return base_url($m[1]);
            }

            return $path;
        }
        $path = ltrim(str_replace('\\', '/', $path), '/');
        if (str_starts_with($path, 'public/')) {
            $path = substr($path, 7);
        }

        return base_url($path);
    }
}

if (!function_exists('pk_datetime')) {
    /**
     * Order / ledger clock in Pakistan Standard Time.
     */
    function pk_datetime($value, string $format = 'd M Y, h:i A'): string
    {
        if ($value instanceof \DateTimeInterface) {
            return (new \DateTimeImmutable($value->format('Y-m-d H:i:s'), $value->getTimezone()))
                ->setTimezone(new \DateTimeZone('Asia/Karachi'))
                ->format($format);
        }
        $raw = trim((string) $value);
        if ($raw === '' || str_starts_with($raw, '0000-00-00')) {
            return '';
        }
        try {
            $dt = new \DateTimeImmutable($raw);

            return $dt->setTimezone(new \DateTimeZone('Asia/Karachi'))->format($format);
        } catch (\Throwable $e) {
            return $raw;
        }
    }
}

if (!function_exists('dash_delta')) {
    function dash_delta(float $now, float $prev): array
    {
        if ($now <= 0 && $prev <= 0) {
            return ['0%', 'flat'];
        }
        if ($prev <= 0) {
            return ['New', 'up'];
        }
        $pct = (int) round((($now - $prev) / $prev) * 100);

        return [($pct > 0 ? '+' : '') . $pct . '%', $pct > 0 ? 'up' : ($pct < 0 ? 'down' : 'flat')];
    }
}

if (!function_exists('hub_paginate_array')) {
    /**
     * @return array{0: array, 1: \CodeIgniter\Pager\Pager}
     */
    function hub_paginate_array(array $rows, int $perPage = 25): array
    {
        $page  = max(1, (int) (service('request')->getGet('page') ?: 1));
        $total = count($rows);
        $pager = service('pager');
        $pager->store('default', $page, $perPage, $total);
        $offset = ($page - 1) * $perPage;

        return [array_slice($rows, $offset, $perPage), $pager];
    }
}

if (! function_exists('catalog_content_id')) {
    /**
     * Meta catalog feed id — must match pixel content_ids.
     * Product without variants: "123". With a variant: "123_45".
     */
    function catalog_content_id(int $productId, $variantId = null): string
    {
        if ($productId < 1) {
            return '';
        }
        $variantId = (int) $variantId;

        return $variantId > 0 ? $productId . '_' . $variantId : (string) $productId;
    }
}

if (!function_exists('is_product_video')) {
    function is_product_video($path, $type = ''): bool
    {
        if (strtolower((string) $type) === 'video') {
            return true;
        }
        $path = strtolower((string) $path);

        return (bool) preg_match('/\.(mp4|webm|ogg|ogv|mov|m4v)(\?|#|$)/i', $path);
    }
}

if (!function_exists('nav_forget')) {
    function nav_forget($keys = null): void
    {
        try {
            $session = session();
            $keys    = $keys === null ? ['cart', 'wishlist', 'wallet'] : (array) $keys;
            foreach ($keys as $k) {
                $session->remove('nav_' . $k);
            }
            $session->remove('nav_state');
        } catch (\Throwable $e) {
            // session may be unavailable in CLI
        }
    }
}

if (! function_exists('is_customer_logged_in')) {
    function is_customer_logged_in(): bool
    {
        return (bool) session()->get('user.isLoggedIn') && session()->get('user.role') === 'customer';
    }
}

if (! function_exists('remember_guest_order')) {
    function remember_guest_order(int $orderId): void
    {
        if ($orderId < 1) {
            return;
        }
        $ids = array_map('intval', session()->get('guest_order_ids') ?? []);
        $ids[] = $orderId;
        session()->set('guest_order_ids', array_values(array_unique($ids)));
        session()->set('guest_mode', 1);
    }
}

if (! function_exists('guest_can_see_order')) {
    function guest_can_see_order(int $orderId): bool
    {
        return in_array($orderId, array_map('intval', session()->get('guest_order_ids') ?? []), true);
    }
}

if (! function_exists('gift_wrap_enabled')) {
    function gift_wrap_enabled(): bool
    {
        if (! class_exists(\App\Services\Checkout\GiftWrapService::class)) {
            return false;
        }
        try {
            return \App\Services\Checkout\GiftWrapService::enabled();
        } catch (\Throwable $e) {
            return false;
        }
    }
}

if (! function_exists('gift_wrap_fee')) {
    function gift_wrap_fee(): float
    {
        if (! class_exists(\App\Services\Checkout\GiftWrapService::class)) {
            return 150.0;
        }
        try {
            return \App\Services\Checkout\GiftWrapService::unitFee();
        } catch (\Throwable $e) {
            return 150.0;
        }
    }
}

if (! function_exists('order_status_url')) {
    function order_status_url(int $orderId): string
    {
        if (session()->get('user.isLoggedIn')) {
            return site_url('account/orders/' . $orderId);
        }

        return site_url('order/thanks/' . $orderId);
    }
}
