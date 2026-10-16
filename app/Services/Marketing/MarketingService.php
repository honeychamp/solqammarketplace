<?php

namespace App\Services\Marketing;

use App\Models\MarketingEventLogModel;
use App\Models\MarketingSettingModel;
use App\Models\OrderItemModel;
use App\Models\OrderModel;
use App\Models\PaymentModel;
use App\Models\UserModel;

class MarketingService
{
    public const CURRENCY = 'PKR';

    public const GA4 = [
        'PageView'              => 'page_view',
        'ViewContent'           => 'view_item',
        'Search'                => 'search',
        'AddToCart'             => 'add_to_cart',
        'RemoveFromCart'        => 'remove_from_cart',
        'ViewCart'              => 'view_cart',
        'InitiateCheckout'      => 'begin_checkout',
        'AddPaymentInfo'        => 'add_payment_info',
        'Purchase'              => 'purchase',
        'CompleteRegistration'  => 'sign_up',
        'Lead'                  => 'generate_lead',
        'Refund'                => 'refund',
        'AddToWishlist'         => 'add_to_wishlist',
        'CouponApplied'         => 'select_promotion',
        'OrderDelivered'        => 'order_delivered',
    ];

    public static function settings(): array
    {
        static $cached;
        if ($cached === null) {
            try {
                $cached = (new MarketingSettingModel())->current();
            } catch (\Throwable $e) {
                $cached = (new MarketingSettingModel())->defaults();
            }
        }

        return $cached;
    }

    public static function publicConfig(): array
    {
        try {
            return (new MarketingSettingModel())->publicConfig();
        } catch (\Throwable $e) {
            return ['enabled' => false];
        }
    }

    public static function enabled(): bool
    {
        $s = self::settings();

        return (int) ($s['enabled'] ?? 0) === 1;
    }

    public static function hashPii(?string $value): string
    {
        $value = strtolower(trim((string) $value));
        $value = preg_replace('/\s+/', '', $value) ?? $value;
        if ($value === '') {
            return '';
        }

        return hash('sha256', $value);
    }

    public static function track(string $eventName, array $data = []): void
    {
        try {
            if (! self::enabled()) {
                return;
            }
            $settings = self::settings();
            if ((int) ($settings['consent_required'] ?? 0) === 1 && ! self::hasConsent()) {
                return;
            }
            $currency = $settings['currency'] ?: self::CURRENCY;
            $eventId  = (string) ($data['event_id'] ?? self::newEventId($eventName));
            $payload  = self::normalize($eventName, $data, $settings);
            $payload['event_id'] = $eventId;
            $payload['currency'] = $payload['currency'] ?? $currency;

            $isPageView = $eventName === 'PageView';
            if ($isPageView && (int) ($settings['server_pageview'] ?? 1) !== 1) {
                return;
            }
            if (! $isPageView) {
                self::queueBrowser($eventName, $payload, $eventId);
                (new MarketingEventLogModel())->record('browser', $eventName, $eventId, $payload, 'queued', 'queued');
            }

            $user    = self::userData($data);
            $custom  = self::metaCustom($payload);
            $url     = (string) ($data['event_source_url'] ?? current_url());
            $tiktokU = self::tiktokUser($data);

            (new MetaTrackingService())->sendCapi($settings, $eventName, $eventId, $custom, $user, $url);
            (new TikTokTrackingService())->sendEventsApi($settings, $eventName, $eventId, $custom, $tiktokU, $url);
        } catch (\Throwable $e) {
            log_message('error', 'Marketing track ' . $eventName . ': ' . $e->getMessage());
        }
    }

    public static function trackProduct(string $eventName, array $product, array $extra = []): void
    {
        $qty   = max(1, (int) ($extra['quantity'] ?? 1));
        $price = (float) ($extra['price'] ?? $product['price'] ?? 0);
        $id    = (string) ($product['id'] ?? '');
        self::track($eventName, array_merge($extra, [
            'value'             => $price * $qty,
            'content_ids'       => [$id],
            'content_name'      => (string) ($product['name'] ?? ''),
            'content_category'  => (string) ($product['category_name'] ?? $product['brand'] ?? ''),
            'content_type'      => 'product',
            'contents'          => [['id' => $id, 'quantity' => $qty, 'item_price' => $price]],
            'items'             => [[
                'item_id'       => $id,
                'item_name'     => (string) ($product['name'] ?? ''),
                'item_category' => (string) ($product['category_name'] ?? ''),
                'item_brand'    => (string) ($product['brand'] ?? ''),
                'price'         => $price,
                'quantity'      => $qty,
            ]],
        ]));
    }

    public static function trackCartItems(string $eventName, array $items, array $extra = []): void
    {
        $mapped = self::mapCartItems($items);
        $value  = (float) ($extra['value'] ?? $mapped['value']);
        self::track($eventName, array_merge($extra, $mapped, ['value' => $value]));
    }

    public static function trackOrder(int $orderId, string $eventName = 'Purchase'): void
    {
        try {
            $order = (new OrderModel())->find($orderId);
            if (! $order) {
                return;
            }
            $items   = (new OrderItemModel())->where('order_id', $orderId)->findAll();
            $payment = (new PaymentModel())->where('order_id', $orderId)->first();
            $user    = (new UserModel())->find((int) $order['user_id']);
            $eventId = (string) ($order['marketing_event_id'] ?: ($order['order_number'] ?? ('order_' . $orderId)));
            if (empty($order['marketing_event_id'])) {
                try {
                    (new OrderModel())->update($orderId, ['marketing_event_id' => $eventId]);
                } catch (\Throwable $e) {
                }
            }
            $goods = (float) $order['total_amount'] - (float) ($order['discount_amount'] ?? 0);
            $value = $goods + (float) ($order['shipping_amount'] ?? 0);
            if ($eventName === 'Refund') {
                $value = (float) ($order['total_amount'] ?? $value);
            }
            $mapped = self::mapOrderItems($items);
            self::track($eventName, array_merge($mapped, [
                'event_id'        => $eventName === 'Purchase' ? $eventId : ($eventName . '_' . $eventId),
                'transaction_id'  => (string) ($order['order_number'] ?? $orderId),
                'value'           => round($value, 2),
                'tax'             => 0,
                'shipping'        => (float) ($order['shipping_amount'] ?? 0),
                'coupon'          => (string) ($order['coupon_code'] ?? ''),
                'payment_type'    => (string) ($payment['payment_method'] ?? ''),
                'email'           => (string) ($user['email'] ?? ''),
                'phone'           => (string) ($user['phone'] ?? ''),
                'external_id'     => (string) ($order['user_id'] ?? ''),
                'first_name'      => explode(' ', (string) ($user['name'] ?? ''))[0] ?? '',
                'last_name'       => trim(strstr((string) ($user['name'] ?? ''), ' ') ?: ''),
            ]));
        } catch (\Throwable $e) {
            log_message('error', 'Marketing order: ' . $e->getMessage());
        }
    }

    public static function pageViewIfStorefront(): void
    {
        try {
            if (! self::enabled() || is_cli()) {
                return;
            }
            $method = strtoupper((string) ($_SERVER['REQUEST_METHOD'] ?? 'GET'));
            if ($method !== 'GET') {
                return;
            }
            $uri = (string) uri_string();
            if ($uri !== '' && (str_starts_with($uri, 'admin') || str_starts_with($uri, 'seller') || str_starts_with($uri, 'api'))) {
                return;
            }
            $path = current_url();
            $last = session()->get('mkt_last_pv');
            if (is_array($last) && ($last['u'] ?? '') === $path && (int) ($last['t'] ?? 0) > time() - 8) {
                return;
            }
            session()->set('mkt_last_pv', ['u' => $path, 't' => time()]);
            self::track('PageView', ['event_source_url' => $path]);
        } catch (\Throwable $e) {
        }
    }

    public static function pullBrowserQueue(): array
    {
        try {
            $queue = session()->get('marketing_browser_queue') ?? [];
            session()->remove('marketing_browser_queue');

            return is_array($queue) ? $queue : [];
        } catch (\Throwable $e) {
            return [];
        }
    }

    public static function mapCartItems(array $items): array
    {
        $contents = [];
        $ids      = [];
        $gaItems  = [];
        $value    = 0.0;
        $qty      = 0;
        foreach ($items as $item) {
            $id    = (string) ($item['product_id'] ?? $item['id'] ?? '');
            $q     = max(1, (int) ($item['quantity'] ?? 1));
            $price = (float) ($item['unit_price'] ?? $item['price'] ?? 0);
            $name  = (string) ($item['product_name'] ?? $item['name'] ?? '');
            $ids[] = $id;
            $qty  += $q;
            $value += $price * $q;
            $contents[] = ['id' => $id, 'quantity' => $q, 'item_price' => $price];
            $gaItems[]  = [
                'item_id'       => $id,
                'item_name'     => $name,
                'item_category' => (string) ($item['category_name'] ?? ''),
                'item_brand'    => (string) ($item['brand'] ?? ''),
                'price'         => $price,
                'quantity'      => $q,
            ];
        }

        return [
            'content_ids'  => $ids,
            'content_type' => 'product',
            'contents'     => $contents,
            'items'        => $gaItems,
            'num_items'    => $qty,
            'value'        => round($value, 2),
        ];
    }

    protected static function mapOrderItems(array $items): array
    {
        $fake = [];
        foreach ($items as $item) {
            $fake[] = [
                'product_id'   => $item['product_id'] ?? '',
                'quantity'     => $item['quantity'] ?? 1,
                'unit_price'   => $item['unit_price'] ?? $item['price'] ?? 0,
                'product_name' => $item['product_name'] ?? $item['name'] ?? '',
            ];
        }

        return self::mapCartItems($fake);
    }

    protected static function normalize(string $eventName, array $data, array $settings): array
    {
        $currency = $settings['currency'] ?: self::CURRENCY;

        return [
            'event'            => $eventName,
            'ga4_event'        => self::GA4[$eventName] ?? strtolower($eventName),
            'currency'         => $currency,
            'value'            => isset($data['value']) ? round((float) $data['value'], 2) : null,
            'content_ids'      => $data['content_ids'] ?? [],
            'content_type'     => $data['content_type'] ?? 'product',
            'content_name'     => $data['content_name'] ?? '',
            'content_category' => $data['content_category'] ?? '',
            'contents'         => $data['contents'] ?? [],
            'items'            => $data['items'] ?? [],
            'num_items'        => $data['num_items'] ?? null,
            'search_string'    => $data['search_string'] ?? ($data['search_term'] ?? ''),
            'transaction_id'   => $data['transaction_id'] ?? '',
            'tax'              => $data['tax'] ?? 0,
            'shipping'         => $data['shipping'] ?? 0,
            'coupon'           => $data['coupon'] ?? '',
            'payment_type'     => $data['payment_type'] ?? '',
        ];
    }

    protected static function metaCustom(array $payload): array
    {
        $custom = array_filter([
            'currency'         => $payload['currency'] ?? self::CURRENCY,
            'value'            => $payload['value'],
            'content_ids'      => $payload['content_ids'] ?: null,
            'content_type'     => $payload['content_type'] ?? 'product',
            'content_name'     => $payload['content_name'] ?: null,
            'content_category' => $payload['content_category'] ?: null,
            'contents'         => $payload['contents'] ?: null,
            'num_items'        => $payload['num_items'],
            'search_string'    => $payload['search_string'] ?: null,
            'order_id'         => $payload['transaction_id'] ?: null,
        ], static fn ($v) => $v !== null && $v !== '' && $v !== []);

        return $custom;
    }

    protected static function userData(array $data): array
    {
        $email = self::hashPii($data['email'] ?? (string) (session()->get('user.email') ?? ''));
        $phone = self::hashPii(preg_replace('/\D+/', '', (string) ($data['phone'] ?? session()->get('user.phone') ?? '')) ?? '');
        $ext   = self::hashPii((string) ($data['external_id'] ?? session()->get('user.id') ?? ''));
        $fn    = self::hashPii($data['first_name'] ?? '');
        $ln    = self::hashPii($data['last_name'] ?? '');
        $out   = array_filter([
            'em'                 => $email !== '' ? [$email] : null,
            'ph'                 => $phone !== '' ? [$phone] : null,
            'fn'                 => $fn !== '' ? [$fn] : null,
            'ln'                 => $ln !== '' ? [$ln] : null,
            'external_id'        => $ext !== '' ? [$ext] : null,
            'client_ip_address'  => self::clientIp(),
            'client_user_agent'  => substr((string) ($_SERVER['HTTP_USER_AGENT'] ?? ''), 0, 512),
            'fbc'                => self::cookie('_fbc'),
            'fbp'                => self::cookie('_fbp'),
        ], static fn ($v) => $v !== null && $v !== '' && $v !== []);

        return $out;
    }

    protected static function tiktokUser(array $data): array
    {
        $email = self::hashPii($data['email'] ?? (string) (session()->get('user.email') ?? ''));
        $phone = self::hashPii(preg_replace('/\D+/', '', (string) ($data['phone'] ?? session()->get('user.phone') ?? '')) ?? '');
        $ext   = self::hashPii((string) ($data['external_id'] ?? session()->get('user.id') ?? ''));

        return array_filter([
            'email'        => $email,
            'phone'        => $phone,
            'external_id'  => $ext,
            'ip'           => self::clientIp(),
            'user_agent'   => substr((string) ($_SERVER['HTTP_USER_AGENT'] ?? ''), 0, 512),
            'ttclid'       => self::cookie('ttclid') ?: (string) ($_GET['ttclid'] ?? ''),
            'ttp'          => self::cookie('_ttp'),
        ]);
    }

    protected static function queueBrowser(string $eventName, array $payload, string $eventId): void
    {
        $queue   = session()->get('marketing_browser_queue') ?? [];
        $queue[] = [
            'event'     => $eventName,
            'event_id'  => $eventId,
            'ga4_event' => $payload['ga4_event'] ?? strtolower($eventName),
            'params'    => $payload,
        ];
        session()->set('marketing_browser_queue', array_slice($queue, -40));
    }

    protected static function newEventId(string $eventName): string
    {
        return strtolower($eventName) . '_' . bin2hex(random_bytes(8));
    }

    protected static function clientIp(): string
    {
        foreach (['HTTP_CF_CONNECTING_IP', 'HTTP_X_FORWARDED_FOR', 'REMOTE_ADDR'] as $key) {
            $raw = (string) ($_SERVER[$key] ?? '');
            if ($raw === '') {
                continue;
            }
            $ip = trim(explode(',', $raw)[0]);
            if (filter_var($ip, FILTER_VALIDATE_IP)) {
                return $ip;
            }
        }

        return '0.0.0.0';
    }

    protected static function cookie(string $name): string
    {
        return (string) ($_COOKIE[$name] ?? '');
    }

    protected static function hasConsent(): bool
    {
        return (string) ($_COOKIE['solqam_ads_consent'] ?? '1') !== '0';
    }
}
