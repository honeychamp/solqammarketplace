<?php

namespace App\Services\Marketing;

use App\Models\MarketingEventLogModel;

class TikTokTrackingService
{
    public function sendEventsApi(array $settings, string $eventName, string $eventId, array $properties, array $user, string $sourceUrl): void
    {
        $pixel = trim((string) ($settings['tiktok_pixel_id'] ?? ''));
        $token = trim((string) ($settings['tiktok_api_token'] ?? ''));
        if ($pixel === '' || $token === '') {
            return;
        }

        $tiktokEvent = $eventName === 'Purchase' ? 'CompletePayment' : $eventName;
        $payload     = [
            'event_source'    => 'web',
            'event_source_id' => $pixel,
            'data'            => [[
                'event'      => $tiktokEvent,
                'event_time' => time(),
                'event_id'   => $eventId,
                'user'       => $user,
                'properties' => $properties,
                'page'       => [
                    'url'      => $sourceUrl,
                    'referrer' => (string) ($_SERVER['HTTP_REFERER'] ?? ''),
                ],
            ]],
        ];

        $url = 'https://business-api.tiktok.com/open_api/v1.3/event/track/';
        $app = trim((string) ($settings['tiktok_app_id'] ?? ''));
        if ($app !== '') {
            $url .= '?app_id=' . rawurlencode($app);
        }

        $res = MarketingHttp::postJson($url, $payload, ['Access-Token: ' . $token]);
        (new MarketingEventLogModel())->record(
            'tiktok_api',
            $tiktokEvent,
            $eventId,
            $payload,
            $res['body'] ?: $res['error'],
            $res['ok'] ? 'sent' : 'error',
            $res['code']
        );
    }
}
