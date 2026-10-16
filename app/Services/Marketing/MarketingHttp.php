<?php

namespace App\Services\Marketing;

class MarketingHttp
{
    public static function postJson(string $url, array $payload, array $headers = [], int $timeout = 4): array
    {
        $body = json_encode($payload);
        $headerLines = array_merge(['Content-Type: application/json', 'Accept: application/json'], $headers);

        $attempt = 0;
        $last    = ['ok' => false, 'code' => 0, 'body' => '', 'error' => 'not sent'];
        while ($attempt < 2) {
            $attempt++;
            $ch = curl_init($url);
            curl_setopt_array($ch, [
                CURLOPT_POST           => true,
                CURLOPT_POSTFIELDS     => $body,
                CURLOPT_HTTPHEADER     => $headerLines,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_TIMEOUT        => $timeout,
                CURLOPT_CONNECTTIMEOUT => 3,
                CURLOPT_SSL_VERIFYPEER => true,
            ]);
            $raw  = curl_exec($ch);
            $code = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $err  = curl_error($ch);
            curl_close($ch);

            $last = [
                'ok'    => $raw !== false && $code >= 200 && $code < 300,
                'code'  => $code,
                'body'  => is_string($raw) ? $raw : '',
                'error' => $err,
            ];
            if ($last['ok'] || ($code > 0 && $code < 500 && $code !== 429)) {
                break;
            }
            usleep(250000);
        }

        return $last;
    }
}
