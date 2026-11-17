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

    protected static ?string $pageViewEventId = null;

    /** @var array<string, string>|null Unhashed Meta advanced matching for this response */
    protected static ?array $pagePixelUser = null;

    /** @var array<string, mixed>|null Session/DB identity for this request */
    protected static ?array $baseIdentity = null;

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
        'Contact'               => 'generate_lead',
        'Refund'                => 'refund',
        'AddToWishlist'         => 'add_to_wishlist',
        'CouponApplied'         => 'select_promotion',
        'OrderDelivered'        => 'order_delivered',
    ];

    public static function settings(): array
    {
        static $cached;
        if ($cached !== null) {
            return $cached;
        }
        try {
            $hit = cache()->get('mkt_settings_row');
            if (is_array($hit)) {
                return $cached = $hit;
            }
            $cached = (new MarketingSettingModel())->current();
            cache()->save('mkt_settings_row', $cached, 120);
        } catch (\Throwable $e) {
            $cached = (new MarketingSettingModel())->defaults();
        }

        return $cached;
    }

    public static function publicConfig(): array
    {
        static $pub;
        if ($pub !== null) {
            return $pub;
        }
        try {
            $cache = cache();
            $hit   = $cache->get('mkt_public_cfg');
            if (is_array($hit)) {
                return $pub = $hit;
            }
            $hit = (new MarketingSettingModel())->publicConfig();
            $cache->save('mkt_public_cfg', $hit, 300);

            return $pub = $hit;
        } catch (\Throwable $e) {
            return $pub = ['enabled' => false];
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

        return self::sha256Hex($value);
    }

    public static function normalizePkPhone(?string $phone): string
    {
        $digits = preg_replace('/\D+/', '', (string) $phone) ?? '';
        if ($digits === '') {
            return '';
        }
        if (str_starts_with($digits, '0092')) {
            $digits = substr($digits, 2);
        }
        if (str_starts_with($digits, '0') && strlen($digits) === 11) {
            $digits = '92' . substr($digits, 1);
        } elseif (strlen($digits) === 10 && str_starts_with($digits, '3')) {
            $digits = '92' . $digits;
        }

        return $digits;
    }

    /**
     * Plaintext advanced matching for the browser pixel (Meta hashes these itself).
     *
     * @return array<string, string>
     */
    public static function pixelAdvancedMatching(): array
    {
        if (is_array(self::$pagePixelUser) && self::$pagePixelUser !== []) {
            return self::$pagePixelUser;
        }

        return self::plainUserFromFields(self::identityFromContext([]));
    }

    /**
     * Persist checkout / form PII for later CAPI events in this session.
     *
     * @param array<string, mixed> $fields
     */
    public static function rememberLead(array $fields): void
    {
        try {
            $cur  = session()->get('mkt_lead');
            $lead = is_array($cur) ? $cur : [];
            $map  = [
                'email'        => ['email', 'em', 'guest_email'],
                'phone'        => ['phone', 'ph', 'recipient_phone'],
                'name'         => ['name', 'recipient_name', 'full_name'],
                'first_name'   => ['first_name', 'fn'],
                'last_name'    => ['last_name', 'ln'],
                'city'         => ['city', 'ct'],
                'province'     => ['province', 'state', 'st'],
                'postal_code'  => ['postal_code', 'zp'],
            ];
            foreach ($map as $dest => $keys) {
                foreach ($keys as $key) {
                    $v = trim((string) ($fields[$key] ?? ''));
                    if ($v === '') {
                        continue;
                    }
                    if ($dest === 'phone') {
                        $v = self::normalizePkPhone($v);
                        if ($v === '') {
                            continue;
                        }
                    }
                    if ($dest === 'email' && ! str_contains($v, '@')) {
                        continue;
                    }
                    $lead[$dest] = $v;
                    break;
                }
            }
            if ($lead !== []) {
                session()->set('mkt_lead', $lead);
                self::$baseIdentity = null;
            }
        } catch (\Throwable $e) {
        }
    }

    /**
     * Fire the browser Purchase once on the Thank You page, with order PII in pixel init.
     * CAPI Purchase is already sent from checkout with the same event_id.
     */
    public static function prepareThanksPurchase(array $order): void
    {
        try {
            $plain = self::plainUserFromOrder($order);
            self::$pagePixelUser = $plain;
            if (! self::enabled() || (($order['id'] ?? 0) < 1) || self::isMachineTraffic()) {
                return;
            }
            $settings = self::settings();
            if ((int) ($settings['consent_required'] ?? 0) === 1 && ! self::hasConsent()) {
                return;
            }
            $orderId = (int) $order['id'];
            $eventId = (string) ($order['marketing_event_id'] ?: ($order['order_number'] ?? ('order_' . $orderId)));
            $flag    = 'mkt_px_purchase_' . $eventId;
            if (session()->get($flag)) {
                return;
            }
            try {
                if (cache()->get($flag)) {
                    session()->set($flag, 1);

                    return;
                }
            } catch (\Throwable $e) {
            }
            session()->set($flag, 1);
            try {
                cache()->save($flag, 1, 86400 * 120);
            } catch (\Throwable $e) {
            }

            $items  = $order['items'] ?? [];
            if ($items === []) {
                $items = (new OrderItemModel())->where('order_id', $orderId)->findAll();
            }
            $mapped = self::mapOrderItems(is_array($items) ? $items : []);
            $value  = self::orderEventValue($order, $mapped, 'Purchase');
            if ($value <= 0) {
                return;
            }
            $payload  = self::normalize('Purchase', array_merge($mapped, [
                'transaction_id' => (string) ($order['order_number'] ?? $orderId),
                'value'          => $value,
                'tax'            => 0,
                'shipping'       => (float) ($order['shipping_amount'] ?? 0),
                'coupon'         => (string) ($order['coupon_code'] ?? ''),
                'payment_type'   => (string) ($order['payment_method'] ?? $order['payment_status'] ?? ''),
            ]), $settings);
            $payload['event_id'] = $eventId;
            self::queueBrowser('Purchase', $payload, $eventId);
            (new MarketingEventLogModel())->record('browser', 'Purchase', $eventId, $payload, 'queued', 'queued');
        } catch (\Throwable $e) {
            log_message('error', 'Marketing thanks purchase: ' . $e->getMessage());
        }
    }

    public static function track(string $eventName, array $data = []): void
    {
        try {
            if (! self::enabled() || is_cli() || self::isMachineTraffic()) {
                return;
            }
            self::captureClickIds();
            $settings = self::settings();
            if ((int) ($settings['consent_required'] ?? 0) === 1 && ! self::hasConsent()) {
                return;
            }
            $currency = $settings['currency'] ?: self::CURRENCY;
            $eventId  = (string) ($data['event_id'] ?? self::newEventId($eventName));
            $payload  = self::normalize($eventName, $data, $settings);
            $payload['event_id'] = $eventId;
            $payload['currency'] = strtoupper((string) ($payload['currency'] ?: $currency ?: self::CURRENCY)) ?: self::CURRENCY;

            if (self::isDuplicateBrowse($eventName, $payload, $data)) {
                return;
            }

            $isPageView = $eventName === 'PageView';
            if ($isPageView) {
                return;
            }
            if (in_array($eventName, ['Purchase', 'Refund'], true) && (($payload['value'] ?? 0) <= 0)) {
                return;
            }
            // Browser Purchase must run on the Thank You page (with advanced matching), not on pay redirect / home.
            if ($eventName !== 'Purchase') {
                self::queueBrowser($eventName, $payload, $eventId);
                (new MarketingEventLogModel())->record('browser', $eventName, $eventId, $payload, 'queued', 'queued');
            }

            $user    = self::userData($data);
            $custom  = self::metaCustom($payload);
            if (in_array($eventName, ['Purchase', 'Refund'], true)) {
                if (empty($custom['currency'])) {
                    $custom['currency'] = $payload['currency'];
                }
                if (! isset($custom['value']) || (float) $custom['value'] <= 0) {
                    return;
                }
            }
            $url     = (string) ($data['event_source_url'] ?? self::sourceUrl());
            $tiktokU = self::tiktokUser($data);

            $needMatch = in_array($eventName, [
                'ViewContent', 'AddToCart', 'InitiateCheckout', 'Search', 'ViewCart',
                'AddToWishlist', 'AddPaymentInfo', 'CompleteRegistration', 'Lead', 'Contact',
            ], true);
            if ($needMatch && ! self::hasHashedCustomerMatch($user)) {
                return;
            }

            self::sendCapiAfterResponse($settings, $eventName, $eventId, $custom, $user, $tiktokU, $url);
        } catch (\Throwable $e) {
            log_message('error', 'Marketing track ' . $eventName . ': ' . $e->getMessage());
        }
    }

    public static function trackProduct(string $eventName, array $product, array $extra = []): void
    {
        $qty   = max(1, (int) ($extra['quantity'] ?? 1));
        $price = (float) ($extra['price'] ?? $product['price'] ?? 0);
        $vid   = (int) ($extra['variant_id'] ?? $product['variant_id'] ?? 0);
        if ($vid < 1) {
            $first = $product['variants'][0]['id'] ?? 0;
            $vid   = (int) $first;
        }
        $id = function_exists('catalog_content_id')
            ? catalog_content_id((int) ($product['id'] ?? 0), $vid ?: null)
            : (string) ($product['id'] ?? '');
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
            $mapped = self::mapOrderItems($items);
            $value  = self::orderEventValue($order, $mapped, $eventName);
            $pii    = self::orderPiiFields($order, is_array($user) ? $user : []);
            self::track($eventName, array_merge($mapped, $pii, [
                'event_id'        => $eventName === 'Purchase' ? $eventId : ($eventName . '_' . $eventId),
                'transaction_id'  => (string) ($order['order_number'] ?? $orderId),
                'value'           => $value,
                'tax'             => 0,
                'shipping'        => (float) ($order['shipping_amount'] ?? 0),
                'coupon'          => (string) ($order['coupon_code'] ?? ''),
                'payment_type'    => (string) ($payment['payment_method'] ?? ''),
            ]));
        } catch (\Throwable $e) {
            log_message('error', 'Marketing order: ' . $e->getMessage());
        }
    }

    public static function pageViewIfStorefront(): void
    {
        try {
            if (is_cli() || self::isMachineTraffic()) {
                return;
            }
            $path = ltrim((string) uri_string(), '/');
            if (str_starts_with($path, 'admin') || str_starts_with($path, 'seller')) {
                return;
            }
            self::captureClickIds();
            $eventId = self::pageViewEventId();
            $url     = self::sourceUrl();
            $user    = self::userData([]);
            $tiktokU = self::tiktokUser([]);
            register_shutdown_function(static function () use ($eventId, $url, $user, $tiktokU): void {
                try {
                    if (function_exists('fastcgi_finish_request')) {
                        @fastcgi_finish_request();
                    }
                    ignore_user_abort(true);
                    if (! self::enabled()) {
                        return;
                    }
                    $settings = self::settings();
                    if ((int) ($settings['server_pageview'] ?? 1) !== 1) {
                        return;
                    }
                    if ((int) ($settings['consent_required'] ?? 0) === 1 && ! self::hasConsent()) {
                        return;
                    }
                    $freshUser = self::withClientNetwork($user);
                    (new MetaTrackingService())->sendCapi($settings, 'PageView', $eventId, [], $freshUser, $url);
                    (new TikTokTrackingService())->sendEventsApi($settings, 'PageView', $eventId, [], $tiktokU, $url);
                } catch (\Throwable $e) {
                    log_message('error', 'Marketing pageview: ' . $e->getMessage());
                }
            });
        } catch (\Throwable $e) {
        }
    }

    public static function pageViewEventId(): string
    {
        if (self::$pageViewEventId === null) {
            self::$pageViewEventId = self::newEventId('PageView');
        }

        return self::$pageViewEventId;
    }

    /**
     * @param array<string, mixed> $settings
     * @param array<string, mixed> $custom
     * @param array<string, mixed> $user
     * @param array<string, mixed> $tiktokU
     */
    protected static function sendCapiAfterResponse(array $settings, string $eventName, string $eventId, array $custom, array $user, array $tiktokU, string $url): void
    {
        $send = static function () use ($settings, $eventName, $eventId, $custom, $user, $tiktokU, $url): void {
            try {
                $freshUser = self::withClientNetwork($user);
                (new MetaTrackingService())->sendCapi($settings, $eventName, $eventId, $custom, $freshUser, $url);
                (new TikTokTrackingService())->sendEventsApi($settings, $eventName, $eventId, $custom, $tiktokU, $url);
            } catch (\Throwable $e) {
                log_message('error', 'Marketing capi ' . $eventName . ': ' . $e->getMessage());
            }
        };
        if (PHP_SAPI === 'cli') {
            $send();

            return;
        }
        register_shutdown_function(static function () use ($send): void {
            if (function_exists('fastcgi_finish_request')) {
                @fastcgi_finish_request();
            }
            ignore_user_abort(true);
            $send();
        });
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
            $pid   = (int) ($item['product_id'] ?? $item['id'] ?? 0);
            $vid   = (int) ($item['variant_id'] ?? 0);
            $id    = function_exists('catalog_content_id') ? catalog_content_id($pid, $vid ?: null) : (string) $pid;
            $q     = max(1, (int) ($item['quantity'] ?? 1));
            $price = self::firstMoney($item['price'] ?? null, $item['unit_price'] ?? null, $item['sale_price'] ?? null);
            $line  = self::firstMoney($item['subtotal'] ?? null, $item['line_total'] ?? null);
            if ($line <= 0) {
                $line = round($price * $q, 2);
            }
            if ($price <= 0 && $q > 0 && $line > 0) {
                $price = round($line / $q, 2);
            }
            $name  = (string) ($item['product_name'] ?? $item['name'] ?? '');
            $ids[] = $id;
            $qty  += $q;
            $value += $line;
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
                'variant_id'   => $item['variant_id'] ?? 0,
                'quantity'     => $item['quantity'] ?? 1,
                'price'        => $item['price'] ?? $item['unit_price'] ?? 0,
                'unit_price'   => $item['unit_price'] ?? $item['price'] ?? 0,
                'subtotal'     => $item['subtotal'] ?? 0,
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
            'value'            => self::moneyOrNull($data['value'] ?? null),
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
        $value = self::moneyOrNull($payload['value'] ?? null);
        $currency = strtoupper(trim((string) ($payload['currency'] ?? self::CURRENCY)));
        if ($currency === '' || ! preg_match('/^[A-Z]{3}$/', $currency)) {
            $currency = self::CURRENCY;
        }

        $custom = array_filter([
            'content_ids'      => $payload['content_ids'] ?: null,
            'content_type'     => $payload['content_type'] ?? 'product',
            'content_name'     => $payload['content_name'] ?: null,
            'content_category' => $payload['content_category'] ?: null,
            'contents'         => $payload['contents'] ?: null,
            'num_items'        => $payload['num_items'],
            'search_string'    => $payload['search_string'] ?: null,
            'order_id'         => $payload['transaction_id'] ?: null,
        ], static fn ($v) => $v !== null && $v !== '' && $v !== []);
        $custom['currency'] = $currency;
        if ($value !== null) {
            $custom['value'] = $value;
        }

        return $custom;
    }

    /**
     * @return array<string, string>
     */
    protected static function orderPiiFields(array $order, array $user): array
    {
        $addr = [];
        $aid  = (int) ($order['address_id'] ?? 0);
        if ($aid > 0) {
            try {
                $addr = \Config\Database::connect()->table('addresses')->where('id', $aid)->get()->getRowArray() ?: [];
            } catch (\Throwable $e) {
                $addr = [];
            }
        }
        $name = (string) ($addr['recipient_name'] ?? $order['recipient_name'] ?? $user['name'] ?? $order['customer_name'] ?? '');
        [$fn, $ln] = self::splitPersonName($name);

        return [
            'email'        => (string) ($user['email'] ?? $order['customer_email'] ?? ''),
            'phone'        => (string) ($addr['phone'] ?? $order['recipient_phone'] ?? $user['phone'] ?? $order['customer_phone'] ?? ''),
            'first_name'   => $fn,
            'last_name'    => $ln,
            'city'         => (string) ($addr['city'] ?? $order['city'] ?? ''),
            'province'     => (string) ($addr['province'] ?? $order['province'] ?? ''),
            'postal_code'  => (string) ($addr['postal_code'] ?? $order['postal_code'] ?? ''),
            'country'      => 'pk',
            'external_id'  => (string) (
                ((int) ($order['user_id'] ?? 0) > 0)
                    ? (int) $order['user_id']
                    : ((int) ($user['id'] ?? 0) > 0 ? (int) $user['id'] : '')
            ),
        ];
    }

    /**
     * @return array<string, string>
     */
    protected static function plainUserFromOrder(array $order): array
    {
        $user = [];
        try {
            $uid = (int) ($order['user_id'] ?? 0);
            if ($uid > 0) {
                $user = (new UserModel())->find($uid) ?: [];
            }
        } catch (\Throwable $e) {
            $user = [];
        }

        return self::plainUserFromFields(self::orderPiiFields($order, is_array($user) ? $user : []));
    }

    /**
     * @param array<string, mixed> $f
     * @return array<string, string>
     */
    protected static function plainUserFromFields(array $f): array
    {
        [$fn, $ln] = self::splitPersonName(trim(
            (string) ($f['first_name'] ?? '') . ' ' . (string) ($f['last_name'] ?? '')
        ));
        if ($fn === '' && $ln === '') {
            [$fn, $ln] = self::splitPersonName((string) ($f['name'] ?? ''));
        }
        $em  = strtolower(trim((string) ($f['email'] ?? $f['em'] ?? '')));
        $em  = preg_replace('/\s+/', '', $em) ?? $em;
        $ph  = self::normalizePkPhone((string) ($f['phone'] ?? $f['ph'] ?? ''));
        $ct  = self::normalizeGeo((string) ($f['city'] ?? $f['ct'] ?? ''));
        $st  = self::normalizeGeo((string) ($f['province'] ?? $f['st'] ?? $f['state'] ?? ''));
        $zp  = preg_replace('/\D+/', '', (string) ($f['postal_code'] ?? $f['zp'] ?? '')) ?? '';
        $cc  = strtolower(trim((string) ($f['country'] ?? 'pk')));
        $cc  = preg_replace('/[^a-z]/', '', $cc) ?? 'pk';
        if (strlen($cc) !== 2) {
            $cc = 'pk';
        }
        $ext = trim((string) ($f['external_id'] ?? ''));
        if ($ext === '0') {
            $ext = '';
        }

        $out = [];
        if ($em !== '' && str_contains($em, '@')) {
            $out['em'] = $em;
        }
        if ($ph !== '') {
            $out['ph'] = $ph;
        }
        if ($fn !== '') {
            $out['fn'] = $fn;
        }
        if ($ln !== '') {
            $out['ln'] = $ln;
        }
        if ($ct !== '') {
            $out['ct'] = $ct;
        }
        if ($st !== '') {
            $out['st'] = $st;
        }
        if ($zp !== '') {
            $out['zp'] = $zp;
        }
        if ($ext !== '') {
            $out['external_id'] = $ext;
        }
        if ($out !== []) {
            $out['country'] = $cc !== '' ? $cc : 'pk';
        }

        return $out;
    }

    /**
     * @return array{0: string, 1: string}
     */
    protected static function splitPersonName(string $name): array
    {
        $name  = strtolower(trim($name));
        $name = trim($name);
        if ($name === '') {
            return ['', ''];
        }
        $name  = function_exists('mb_strtolower') ? mb_strtolower($name, 'UTF-8') : strtolower($name);
        $name  = preg_replace('/[^\p{L}\s]/u', ' ', $name) ?? $name;
        $parts = array_values(array_filter(preg_split('/\s+/u', $name) ?: [], static fn ($p) => $p !== ''));
        if ($parts === []) {
            return ['', ''];
        }
        $fn = preg_replace('/[^\p{L}]/u', '', $parts[0]) ?? '';
        $ln = preg_replace('/[^\p{L}]/u', '', implode('', array_slice($parts, 1))) ?? '';
        return [$fn, $ln];
    }

    protected static function normalizeGeo(string $value): string
    {
        $value = strtolower(trim($value));

        return preg_replace('/[^a-z]/', '', $value) ?? '';
    }

    protected static function hashAdvanced(string $value): string
    {
        $value = strtolower(trim($value));
        $value = trim($value);
        if ($value === '') {
            return '';
        }
        $value = function_exists('mb_strtolower') ? mb_strtolower($value, 'UTF-8') : strtolower($value);
        if ($value === '') {
            return '';
        }

        return self::sha256Hex($value);
    }

    /**
     * Logged-in account + session lead + non-empty event overlay. Never lets empty
     * POST fields wipe a known email/phone.
     *
     * @param array<string, mixed> $data
     * @return array<string, mixed>
     */
    protected static function identityFromContext(array $data): array
    {
        if (self::$baseIdentity === null) {
            $base = [
                'email'        => '',
                'phone'        => '',
                'name'         => '',
                'first_name'   => '',
                'last_name'    => '',
                'city'         => '',
                'province'     => '',
                'postal_code'  => '',
                'country'      => 'pk',
                'external_id'  => '',
            ];
            try {
                $sess = session()->get('user');
                if (is_array($sess)) {
                    $base['email']        = (string) ($sess['email'] ?? '');
                    $base['phone']        = (string) ($sess['phone'] ?? '');
                    $base['name']         = (string) ($sess['name'] ?? '');
                    $base['external_id']  = (string) ($sess['id'] ?? '');
                }
                $lead = session()->get('mkt_lead');
                if (is_array($lead)) {
                    foreach ($base as $k => $cur) {
                        if ($cur === '' && ! empty($lead[$k])) {
                            $base[$k] = (string) $lead[$k];
                        }
                    }
                }
            } catch (\Throwable $e) {
            }

            $uid = (int) $base['external_id'];
            if ($uid < 1) {
                $base['external_id'] = self::visitorExternalId();
            }
            if ($uid > 0 && ($base['email'] === '' || $base['phone'] === '' || $base['name'] === '')) {
                try {
                    $row = (new UserModel())->find($uid);
                    if (is_array($row)) {
                        if ($base['email'] === '') {
                            $base['email'] = (string) ($row['email'] ?? '');
                        }
                        if ($base['phone'] === '') {
                            $base['phone'] = (string) ($row['phone'] ?? '');
                        }
                        if ($base['name'] === '') {
                            $base['name'] = (string) ($row['name'] ?? '');
                        }
                    }
                } catch (\Throwable $e) {
                }
            }
            if ($uid > 0 && ($base['city'] === '' || $base['province'] === '' || $base['postal_code'] === '' || $base['name'] === '')) {
                try {
                    $addr = (new \App\Models\AddressModel())->getDefaultAddress($uid);
                    if (is_array($addr)) {
                        if ($base['city'] === '') {
                            $base['city'] = (string) ($addr['city'] ?? '');
                        }
                        if ($base['province'] === '') {
                            $base['province'] = (string) ($addr['province'] ?? '');
                        }
                        if ($base['postal_code'] === '') {
                            $base['postal_code'] = (string) ($addr['postal_code'] ?? '');
                        }
                        if ($base['name'] === '' && ! empty($addr['recipient_name'])) {
                            $base['name'] = (string) $addr['recipient_name'];
                        }
                        if ($base['phone'] === '' && ! empty($addr['phone'])) {
                            $base['phone'] = (string) $addr['phone'];
                        }
                    }
                } catch (\Throwable $e) {
                }
            }
            self::$baseIdentity = $base;
        }

        $out = self::$baseIdentity;

        foreach (['email', 'phone', 'name', 'first_name', 'last_name', 'city', 'province', 'postal_code', 'external_id'] as $k) {
            $v = trim((string) ($data[$k] ?? ''));
            if ($v !== '' && $v !== '0') {
                $out[$k] = $v;
            }
        }
        if ($out['external_id'] === '0' || $out['external_id'] === '') {
            $out['external_id'] = self::visitorExternalId();
        }

        return $out;
    }

    protected static function hasHashedCustomerMatch(array $user): bool
    {
        foreach (['em', 'ph', 'fn', 'ln', 'ge', 'db', 'ct', 'st', 'zp'] as $key) {
            if (! empty($user[$key])) {
                return true;
            }
        }

        return false;
    }

    protected static function visitorExternalId(): string
    {
        $id = '';
        try {
            $id = trim((string) session()->get('mkt_vid'));
        } catch (\Throwable $e) {
        }
        if ($id === '' || ! preg_match('/^[a-f0-9]{16,64}$/', $id)) {
            $id = trim(self::rawCookie('solqam_vid'));
        }
        if ($id === '' || ! preg_match('/^[a-f0-9]{16,64}$/', $id)) {
            try {
                $id = bin2hex(random_bytes(16));
            } catch (\Throwable $e) {
                $id = hash('sha256', (self::clientIp() ?: '0') . '|' . (string) ($_SERVER['HTTP_USER_AGENT'] ?? '') . '|' . microtime(true));
                $id = substr($id, 0, 32);
            }
            try {
                session()->set('mkt_vid', $id);
            } catch (\Throwable $e) {
            }
            self::persistPixelCookie('solqam_vid', $id);
        }

        return $id;
    }

    protected static function userData(array $data): array
    {
        $plain = self::plainUserFromFields(self::identityFromContext($data));
        $hash  = static function (string $key) use ($plain): ?array {
            $v = (string) ($plain[$key] ?? '');
            if ($v === '') {
                return null;
            }
            $h = self::hashAdvanced($v);
            if ($h === '') {
                return null;
            }

            return [$h];
        };
        $ip  = self::clientIp();
        $ua  = substr((string) ($_SERVER['HTTP_USER_AGENT'] ?? ''), 0, 512);
        $ids = self::facebookClickIds($data);

        $countryHash = $hash('country') ?: [self::hashAdvanced('pk')];
        $extHash     = $hash('external_id');
        if ($extHash === null) {
            $vid = self::visitorExternalId();
            $h   = self::hashAdvanced($vid);
            $extHash = $h !== '' ? [$h] : null;
        }

        $out = array_filter([
            'em'                 => $hash('em'),
            'ph'                 => $hash('ph'),
            'fn'                 => $hash('fn'),
            'ln'                 => $hash('ln'),
            'ct'                 => $hash('ct'),
            'st'                 => $hash('st'),
            'zp'                 => $hash('zp'),
            'country'            => $countryHash,
            'client_ip_address'  => ($ip !== '' && $ip !== '0.0.0.0') ? $ip : null,
            'client_user_agent'  => $ua !== '' ? $ua : null,
            'external_id'        => $extHash,
            'fbp'                => $ids['fbp'] !== '' ? $ids['fbp'] : null,
            'fbc'                => $ids['fbc'] !== '' ? $ids['fbc'] : null,
        ], static fn ($v) => $v !== null && $v !== '' && $v !== []);

        return $out;
    }

    protected static function hashEmail(string $email): string
    {
        $email = strtolower(trim($email));
        $email = preg_replace('/\s+/', '', $email) ?? $email;
        if ($email === '' || ! str_contains($email, '@')) {
            return '';
        }

        return self::sha256Hex($email);
    }

    protected static function hashPhone(string $phone): string
    {
        $digits = self::normalizePkPhone($phone);
        if ($digits === '') {
            return '';
        }

        return self::sha256Hex($digits);
    }

    protected static function sha256Hex(string $value): string
    {
        if (preg_match('/^[a-f0-9]{64}$/', $value)) {
            return $value;
        }

        return hash('sha256', $value);
    }

    protected static function facebookClickIds(array $data): array
    {
        $fbp = self::exactFbp((string) ($data['fbp'] ?? ''));
        $fbc = self::exactFbc((string) ($data['fbc'] ?? ''));
        if ($fbp === '') {
            try {
                $fbp = self::pixelFbp((string) (session()->get('mkt__fbp') ?? ''));
            } catch (\Throwable $e) {
            }
        }
        if ($fbc === '') {
            try {
                $fbc = self::pixelFbc((string) (session()->get('mkt__fbc') ?? ''));
            } catch (\Throwable $e) {
            }
        }

        return ['fbp' => $fbp, 'fbc' => $fbc];
    }

    /**
     * Cookie first, then posted, then fbclid → fbc.
     */
    protected static function exactFbc(string $posted): string
    {
        foreach ([self::rawCookie('_fbc'), $posted] as $candidate) {
            $ok = self::pixelFbc($candidate);
            if ($ok !== '') {
                return $ok;
            }
        }
        $clid = self::rawFbclid();
        if ($clid !== '') {
            return 'fb.1.' . (int) round(microtime(true) * 1000) . '.' . $clid;
        }

        return '';
    }

    protected static function rawFbclid(): string
    {
        $q = (string) ($_GET['fbclid'] ?? '');
        if ($q === '' && isset($_SERVER['QUERY_STRING'])) {
            if (preg_match('/(?:^|&)fbclid=([^&]+)/', (string) $_SERVER['QUERY_STRING'], $m)) {
                $q = rawurldecode($m[1]);
            }
        }
        $q = trim($q);
        if ($q === '' || strlen($q) > 512) {
            return '';
        }
        if (! preg_match('/^[A-Za-z0-9._~-]+$/', $q)) {
            return '';
        }

        return $q;
    }

    protected static function pixelFbc(string $value): string
    {
        $value = trim($value);
        if ($value === '' || strlen($value) > 2048) {
            return '';
        }
        // Accept Pixel cookie / hidden-field copy only. Do not rewrite the click-id segment.
        if (! preg_match('/^fb\.\d+\.\d+\.\S+$/', $value)) {
            return '';
        }

        return $value;
    }

    protected static function exactFbp(string $posted): string
    {
        foreach ([self::rawCookie('_fbp'), $posted] as $v) {
            $v = trim($v);
            $ok = self::pixelFbp($v);
            if ($ok !== '') {
                return $ok;
            }
        }

        return '';
    }

    protected static function pixelFbp(string $value): string
    {
        $value = trim($value);
        if ($value !== '' && preg_match('/^fb\.\d+\.\d+\.\d+$/', $value)) {
            return $value;
        }

        return '';
    }

    /**
     * Read Set-Cookie payload as the browser sent it. PHP's $_COOKIE url-decodes and can
     * change fbclid characters, which Meta then reports as a modified fbc.
     */
    protected static function rawCookie(string $name): string
    {
        $header = (string) ($_SERVER['HTTP_COOKIE'] ?? '');
        if ($header !== '' && preg_match('/(?:^|;\s*)' . preg_quote($name, '/') . '=([^;]*)/', $header, $m)) {
            return $m[1];
        }

        return isset($_COOKIE[$name]) ? (string) $_COOKIE[$name] : '';
    }

    /**
     * @param mixed $a
     * @param mixed $b
     * @param mixed $c
     */
    protected static function firstMoney($a = null, $b = null, $c = null): float
    {
        foreach ([$a, $b, $c] as $v) {
            $n = self::moneyOrNull($v);
            if ($n !== null) {
                return $n;
            }
        }

        return 0.0;
    }

    /**
     * @param mixed $value
     */
    protected static function moneyOrNull($value): ?float
    {
        if ($value === null || $value === '') {
            return null;
        }
        if (is_string($value)) {
            $value = str_replace([',', ' '], '', $value);
        }
        if (! is_numeric($value)) {
            return null;
        }
        $n = round((float) $value, 2);

        return $n > 0 ? $n : null;
    }

    protected static function orderEventValue(array $order, array $mapped, string $eventName): float
    {
        $goods   = self::firstMoney($mapped['value'] ?? null, $order['total_amount'] ?? null);
        $ship    = round((float) ($order['shipping_amount'] ?? 0), 2);
        $gift    = round((float) ($order['gift_wrap_amount'] ?? 0), 2);
        $disc    = round((float) ($order['discount_amount'] ?? $order['coupon_discount'] ?? 0), 2);
        $wallet  = round((float) ($order['wallet_amount_used'] ?? 0), 2);
        $payable = round((float) ($order['final_payable'] ?? 0), 2);
        $value   = round(max(0, $goods - $disc) + max(0, $ship) + max(0, $gift), 2);
        if ($value <= 0) {
            $value = round($payable + $wallet, 2);
        }
        if ($value <= 0) {
            $value = $payable > 0 ? $payable : $goods;
        }
        if ($eventName === 'Refund') {
            $refund = self::firstMoney($order['final_payable'] ?? null, $order['total_amount'] ?? null, $value);
            $value  = $refund > 0 ? $refund : $value;
        }

        return round(max(0, $value), 2);
    }

    protected static function sourceUrl(): string
    {
        $https = (! empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
            || (string) ($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https';
        $host = (string) ($_SERVER['HTTP_HOST'] ?? 'solqam.com');
        $uri  = (string) ($_SERVER['REQUEST_URI'] ?? '/');

        return ($https ? 'https' : 'http') . '://' . $host . $uri;
    }

    public static function captureClickIds(): void
    {
        try {
            $posted = '';
            $postedFbc = '';
            $postedFbp = '';
            $req = function_exists('service') ? service('request') : null;
            if ($req) {
                $postedFbc = (string) ($req->getPost('fbc') ?? $req->getGet('fbc') ?? '');
                $postedFbp = (string) ($req->getPost('fbp') ?? $req->getGet('fbp') ?? '');
            }
            $fbc = self::exactFbc($postedFbc);
            $fbp = self::exactFbp($postedFbp);
            if ($fbc === '') {
                $fbc = self::pixelFbc((string) (session()->get('mkt__fbc') ?? ''));
            }
            if ($fbp === '') {
                $fbp = self::pixelFbp((string) (session()->get('mkt__fbp') ?? ''));
            }
            if ($fbc !== '' && self::pixelFbc(self::rawCookie('_fbc')) === '') {
                self::persistPixelCookie('_fbc', $fbc);
            }
            if ($fbp === '') {
                $fbp = 'fb.1.' . (int) round(microtime(true) * 1000) . '.' . random_int(1000000000, 2147483646);
            }
            if ($fbp !== '' && self::pixelFbp(self::rawCookie('_fbp')) === '') {
                self::persistPixelCookie('_fbp', $fbp);
            }
            self::visitorExternalId();
        } catch (\Throwable $e) {
        }
    }

    protected static function persistPixelCookie(string $name, string $value): void
    {
        $value = trim($value);
        if ($value === '') {
            return;
        }
        $_COOKIE[$name] = $value;
        try {
            session()->set('mkt_' . $name, $value);
        } catch (\Throwable $e) {
        }
        if (headers_sent()) {
            return;
        }
        $https = (! empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
            || (string) ($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https';
        setcookie($name, $value, [
            'expires'  => time() + 90 * 86400,
            'path'     => '/',
            'secure'   => $https,
            'httponly' => false,
            'samesite' => 'Lax',
        ]);
    }

    protected static function tiktokUser(array $data): array
    {
        $id    = self::identityFromContext($data);
        $email = self::hashEmail((string) ($id['email'] ?? ''));
        $phone = self::hashPhone((string) ($id['phone'] ?? ''));
        $uid   = trim((string) ($id['external_id'] ?? ''));
        if ($uid === '0') {
            $uid = '';
        }

        return array_filter([
            'email'        => $email,
            'phone'        => $phone,
            'external_id'  => $uid,
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

    /**
     * Meta rejects private / placeholder IPs and then flags client_ip_address as missing.
     *
     * @param array<string, mixed> $user
     * @return array<string, mixed>
     */
    protected static function withClientNetwork(array $user): array
    {
        $ip = self::clientIp();
        if ($ip !== '') {
            $user['client_ip_address'] = $ip;
        }
        $ua = substr((string) ($_SERVER['HTTP_USER_AGENT'] ?? ''), 0, 512);
        if ($ua !== '') {
            $user['client_user_agent'] = $ua;
        }

        return $user;
    }

    protected static function clientIp(): string
    {
        $candidates = [];
        foreach ([
            'HTTP_CF_CONNECTING_IP',
            'HTTP_TRUE_CLIENT_IP',
            'HTTP_X_REAL_IP',
            'HTTP_X_FORWARDED_FOR',
            'HTTP_X_FORWARDED',
            'HTTP_FORWARDED',
            'REMOTE_ADDR',
        ] as $key) {
            $raw = (string) ($_SERVER[$key] ?? '');
            if ($raw === '') {
                continue;
            }
            if (preg_match_all('/for=(?:"?\[?)([^\]";,\s]+)/i', $raw, $fwd)) {
                foreach ($fwd[1] as $hit) {
                    $candidates[] = $hit;
                }
            }
            foreach (explode(',', $raw) as $part) {
                $part = trim($part, " \t\"[]");
                if (stripos($part, 'for=') === 0) {
                    $part = trim(substr($part, 4), " \t\"[]");
                }
                if ($part !== '') {
                    $candidates[] = $part;
                }
            }
        }

        $publicFlags = FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE;
        foreach ($candidates as $ip) {
            $ip = trim((string) $ip);
            if (preg_match('/^(\d{1,3}(?:\.\d{1,3}){3}):\d+$/', $ip, $m)) {
                $ip = $m[1];
            }
            if (filter_var($ip, FILTER_VALIDATE_IP, $publicFlags)) {
                return $ip;
            }
        }

        return '';
    }

    protected static function cookie(string $name): string
    {
        $v = (string) ($_COOKIE[$name] ?? '');
        if ($v !== '') {
            return $v;
        }
        try {
            $req = service('request');
            $c = (string) ($req->getCookie($name) ?? '');
            if ($c !== '') {
                return $c;
            }
        } catch (\Throwable $e) {
        }

        return (string) (session()->get('mkt' . $name) ?? session()->get('mkt_' . ltrim($name, '_')) ?? '');
    }

    protected static function hasConsent(): bool
    {
        return (string) ($_COOKIE['solqam_ads_consent'] ?? '1') !== '0';
    }

    /**
     * Crawlers (Google, Meta share preview, SEO bots) hit PHP product/search pages
     * and inflate CAPI ViewContent/Search. Browser pixel never runs for them.
     */
    protected static function isMachineTraffic(): bool
    {
        $method = strtoupper((string) ($_SERVER['REQUEST_METHOD'] ?? 'GET'));
        if (in_array($method, ['HEAD', 'OPTIONS', 'TRACE'], true)) {
            return true;
        }

        $purpose = strtolower((string) ($_SERVER['HTTP_PURPOSE'] ?? $_SERVER['HTTP_SEC_PURPOSE'] ?? $_SERVER['HTTP_X_PURPOSE'] ?? ''));
        if (str_contains($purpose, 'prefetch') || str_contains($purpose, 'preview')) {
            return true;
        }
        $mode = strtolower((string) ($_SERVER['HTTP_SEC_FETCH_MODE'] ?? ''));
        if ($mode === 'prefetch' || $mode === 'nested-navigate') {
            return true;
        }

        $ua = strtolower((string) ($_SERVER['HTTP_USER_AGENT'] ?? ''));
        if ($ua === '' || strlen($ua) < 12) {
            return true;
        }

        $needles = [
            'googlebot', 'adsbot-google', 'mediapartners-google', 'storebot-google',
            'bingbot', 'yandex', 'baiduspider', 'duckduckbot', 'applebot', 'petalbot',
            'bytespider', 'facebookexternalhit', 'facebot', 'meta-externalagent',
            'meta-externalfetcher', 'facebookcatalog', 'twitterbot', 'linkedinbot',
            'slackbot', 'discordbot', 'telegrambot', 'pinterestbot',
            'semrush', 'ahrefs', 'mj12bot', 'dotbot', 'screaming frog',
            'gptbot', 'claudebot', 'anthropic-ai', 'perplexitybot', 'ccbot',
            'feedfetcher', 'ia_archiver', 'archive.org_bot',
            'lighthouse', 'pagespeed', 'gtmetrix', 'pingdom', 'uptimerobot',
            'statuscake', 'headlesschrome', 'phantomjs', 'selenium',
            'puppeteer', 'playwright', 'curl/', 'wget/', 'python-requests', 'python-urllib',
            'go-http-client', 'okhttp', 'libwww-perl', 'scrapy',
            'spider', 'crawler',
        ];
        foreach ($needles as $n) {
            if (str_contains($ua, $n)) {
                return true;
            }
        }

        if (! str_contains($ua, 'mozilla') && ! str_contains($ua, 'opera mini')) {
            return true;
        }

        return false;
    }

    protected static function isDuplicateBrowse(string $eventName, array $payload, array $data): bool
    {
        if (! in_array($eventName, ['ViewContent', 'Search', 'ViewCart'], true)) {
            return false;
        }
        $key = $eventName . ':' . implode(',', $payload['content_ids'] ?? []) . ':' . ($payload['search_string'] ?? '');
        if ($eventName === 'ViewCart') {
            $key = 'ViewCart:' . current_url();
        }
        $last = session()->get('mkt_browse_dedupe');
        if (is_array($last) && ($last['k'] ?? '') === $key && (int) ($last['t'] ?? 0) > time() - 20) {
            return true;
        }
        session()->set('mkt_browse_dedupe', ['k' => $key, 't' => time()]);

        return false;
    }
}
