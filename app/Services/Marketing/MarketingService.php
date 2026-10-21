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
            $payload['currency'] = $payload['currency'] ?? $currency;

            if (self::isDuplicateBrowse($eventName, $payload, $data)) {
                return;
            }

            $isPageView = $eventName === 'PageView';
            if ($isPageView && (int) ($settings['server_pageview'] ?? 1) !== 1) {
                return;
            }
            if (! $isPageView) {
                self::queueBrowser($eventName, $payload, $eventId);
                (new MarketingEventLogModel())->record('browser', $eventName, $eventId, $payload, 'queued', 'queued');
            }

            $browserOnly = in_array($eventName, ['ViewContent', 'Search', 'ViewCart'], true);
            if ($browserOnly) {
                return;
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
            if (! self::enabled() || is_cli() || self::isMachineTraffic()) {
                return;
            }
            self::captureClickIds();
            $method = strtoupper((string) ($_SERVER['REQUEST_METHOD'] ?? 'GET'));
            if ($method !== 'GET') {
                return;
            }
            $uri = (string) uri_string();
            if ($uri !== '' && (str_starts_with($uri, 'admin') || str_starts_with($uri, 'seller') || str_starts_with($uri, 'api') || str_starts_with($uri, 'feed'))) {
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
            $pid   = (int) ($item['product_id'] ?? $item['id'] ?? 0);
            $vid   = (int) ($item['variant_id'] ?? 0);
            $id    = function_exists('catalog_content_id') ? catalog_content_id($pid, $vid ?: null) : (string) $pid;
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
                'variant_id'   => $item['variant_id'] ?? 0,
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
        $email = self::hashEmail((string) ($data['email'] ?? session()->get('user.email') ?? ''));
        $phone = self::hashPhone((string) ($data['phone'] ?? session()->get('user.phone') ?? ''));
        $uid   = trim((string) ($data['external_id'] ?? session()->get('user.id') ?? ''));
        $ext   = ($uid !== '' && $uid !== '0') ? self::sha256Hex($uid) : '';
        $ip    = self::clientIp();
        $ua    = substr((string) ($_SERVER['HTTP_USER_AGENT'] ?? ''), 0, 512);
        $ids   = self::facebookClickIds($data);

        $out = array_filter([
            'em'                 => $email !== '' ? [$email] : null,
            'ph'                 => $phone !== '' ? [$phone] : null,
            'external_id'        => $ext !== '' ? [$ext] : null,
            'client_ip_address'  => ($ip !== '' && $ip !== '0.0.0.0') ? $ip : null,
            'client_user_agent'  => $ua !== '' ? $ua : null,
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
        $fbp = trim((string) ($data['fbp'] ?? ''));
        if ($fbp === '') {
            $fbp = self::cookie('_fbp') ?: (string) (session()->get('mkt_fbp') ?? '');
        }
        $fbc = trim((string) ($data['fbc'] ?? ''));
        if ($fbc === '') {
            $fbc = self::cookie('_fbc') ?: (string) (session()->get('mkt_fbc') ?? '');
        }
        if ($fbc === '') {
            $fbclid = trim((string) ($data['fbclid'] ?? session()->get('mkt_fbclid') ?? $_GET['fbclid'] ?? ''));
            if ($fbclid !== '') {
                $fbc = 'fb.1.' . (int) round(microtime(true) * 1000) . '.' . $fbclid;
            }
        }

        return [
            'fbp' => $fbp,
            'fbc' => $fbc,
        ];
    }

    public static function captureClickIds(): void
    {
        try {
            $req = function_exists('service') ? service('request') : null;
            $pick = static function (string $name) use ($req): string {
                $v = (string) ($_COOKIE[$name] ?? '');
                if ($v !== '') {
                    return $v;
                }
                if ($req) {
                    $c = (string) ($req->getCookie($name) ?? '');
                    if ($c !== '') {
                        return $c;
                    }
                    $p = (string) ($req->getPost($name) ?? $req->getPost(ltrim($name, '_')) ?? '');
                    if ($p !== '') {
                        return $p;
                    }
                }

                return '';
            };
            $fbp = $pick('_fbp') ?: $pick('fbp');
            $fbc = $pick('_fbc') ?: $pick('fbc');
            $fbclid = trim((string) ($_GET['fbclid'] ?? ($req ? $req->getGet('fbclid') : '') ?? ''));
            if ($fbp !== '') {
                session()->set('mkt_fbp', $fbp);
            }
            if ($fbc !== '') {
                session()->set('mkt_fbc', $fbc);
            }
            if ($fbclid !== '') {
                session()->set('mkt_fbclid', $fbclid);
            }
        } catch (\Throwable $e) {
        }
    }

    protected static function tiktokUser(array $data): array
    {
        $email = self::hashEmail((string) ($data['email'] ?? session()->get('user.email') ?? ''));
        $phone = self::hashPhone((string) ($data['phone'] ?? session()->get('user.phone') ?? ''));
        $uid   = trim((string) ($data['external_id'] ?? session()->get('user.id') ?? ''));
        $ext   = ($uid !== '' && $uid !== '0') ? self::sha256Hex($uid) : '';

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
