<?php

namespace App\Services\Marketing;

use App\Models\MarketingEventLogModel;

class MetaTrackingService
{
    public function sendCapi(array $settings, string $eventName, string $eventId, array $custom, array $user, string $sourceUrl): void
    {
        $pixel = trim((string) ($settings['meta_pixel_id'] ?? ''));
        $token = trim((string) ($settings['meta_capi_token'] ?? ''));
        if ($pixel === '' || $token === '') {
            return;
        }

        $payload = [
            'data' => [[
                'event_name'       => $eventName,
                'event_time'       => time(),
                'event_source_url' => $sourceUrl,
                'action_source'    => 'website',
                'event_id'         => $eventId,
                'user_data'        => $user,
                'custom_data'      => $custom,
            ]],
        ];
        $test = trim((string) ($settings['meta_test_event_code'] ?? ''));
        if ((int) ($settings['test_mode'] ?? 0) === 1 && $test !== '') {
            $payload['test_event_code'] = $test;
        }

        $url = 'https://graph.facebook.com/v21.0/' . rawurlencode($pixel) . '/events?access_token=' . rawurlencode($token);
        $res = MarketingHttp::postJson($url, $payload);
        (new MarketingEventLogModel())->record(
            'meta_capi',
            $eventName,
            $eventId,
            $this->redact($payload),
            $res['body'] ?: $res['error'],
            $res['ok'] ? 'sent' : 'error',
            $res['code']
        );
    }

    protected function redact(array $payload): array
    {
        return $payload;
    }
}
