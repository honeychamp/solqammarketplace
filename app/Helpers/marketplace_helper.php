<?php

if (!function_exists('category_tree')) {
    function category_tree(): array
    {
        try {
            $model = new \App\Models\CategoryModel();
            return $model->getTree();
        } catch (\Throwable $e) {
            return [];
        }
    }
}

if (!function_exists('wishlist_count')) {
    function wishlist_count(): int
    {
        $userId = (int) (session()->get('user.id') ?? 0);
        if ($userId <= 0) {
            return 0;
        }
        try {
            return (new \App\Models\WishlistModel())->where('user_id', $userId)->countAllResults();
        } catch (\Throwable $e) {
            return 0;
        }
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
        try {
            return (new \App\Services\Shipping\ShippingService())->publicHint();
        } catch (\Throwable $e) {
            return ['has_rates' => false, 'min_rate' => 0.0, 'max_rate' => 0.0, 'label' => 'At checkout'];
        }
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
        $goods = (float) ($item['subtotal'] ?? 0);
        $commission = (float) ($item['commission_amount'] ?? 0);

        return round(max(0.0, $goods - $commission - item_cashback($item)), 2);
    }
}

if (!function_exists('is_online_gateway')) {
    function is_online_gateway(?string $method): bool
    {
        return in_array((string) $method, ['payfast', 'jazzcash', 'easypaisa', 'card'], true);
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
