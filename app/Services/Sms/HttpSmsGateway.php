<?php

namespace App\Services\Sms;

/**
 * Any HTTP SMS API. Placeholders: {to} {message} {from} {sender}
 *
 * GET example:
 *   sms.http.url = https://api.example.com/send?key=KEY&to={to}&text={message}
 *
 * POST JSON:
 *   sms.http.method = POST
 *   sms.http.contentType = application/json
 *   sms.http.body = {"mobile":"{to}","msg":"{message}","mask":"{from}"}
 *   sms.http.headers = {"Authorization":"Bearer TOKEN"}
 */
class HttpSmsGateway implements SmsGatewayInterface
{
    public function send(string $phone, string $message): bool
    {
        $cfg = config('Sms');
        $to  = pk_msisdn($phone);
        if ($cfg->httpUrl === '') {
            log_message('error', 'HTTP SMS: sms.http.url is empty');

            return false;
        }

        $mapRaw = [
            '{to}'      => $to,
            '{message}' => $message,
            '{from}'    => $cfg->sender,
            '{sender}'  => $cfg->sender,
        ];
        $mapEnc = [
            '{to}'      => rawurlencode($to),
            '{message}' => rawurlencode($message),
            '{from}'    => rawurlencode($cfg->sender),
            '{sender}'  => rawurlencode($cfg->sender),
        ];

        $url = str_replace(array_keys($mapEnc), array_values($mapEnc), $cfg->httpUrl);
        $method = strtoupper($cfg->httpMethod) === 'POST' ? 'POST' : 'GET';
        $headers = $this->parseHeaders($cfg->httpHeaders);

        $opts = [
            'http_errors' => false,
            'timeout'     => 20,
            'headers'     => $headers,
        ];

        $client = service('curlrequest');
        if ($method === 'POST') {
            $body = str_replace(array_keys($mapRaw), array_values($mapRaw), $cfg->httpBody);
            $ctype = strtolower($cfg->httpContentType);
            if ($ctype === '' && $body !== '' && str_starts_with(ltrim($body), '{')) {
                $ctype = 'application/json';
            }
            if ($ctype !== '') {
                $opts['headers']['Content-Type'] = $cfg->httpContentType !== '' ? $cfg->httpContentType : $ctype;
            }
            if ($body !== '') {
                if (str_contains($ctype, 'json')) {
                    $opts['body'] = $body;
                } else {
                    $opts['form_params'] = $this->queryToArray($body);
                }
            }
            $res = $client->post($url, $opts);
        } else {
            $res = $client->get($url, $opts);
        }

        $ok = $res->getStatusCode() >= 200 && $res->getStatusCode() < 300;
        if (! $ok) {
            log_message('error', 'HTTP SMS failed: HTTP ' . $res->getStatusCode() . ' ' . $res->getBody());
        }

        return $ok;
    }

    public function driver(): string
    {
        return 'http';
    }

    /**
     * @return array<string, string>
     */
    protected function parseHeaders(string $raw): array
    {
        $raw = trim($raw);
        if ($raw === '') {
            return [];
        }
        $decoded = json_decode($raw, true);
        if (! is_array($decoded)) {
            return [];
        }
        $out = [];
        foreach ($decoded as $k => $v) {
            $out[(string) $k] = (string) $v;
        }

        return $out;
    }

    /**
     * @return array<string, string>
     */
    protected function queryToArray(string $body): array
    {
        if (str_contains($body, '=')) {
            parse_str($body, $parsed);

            return array_map('strval', $parsed);
        }

        return ['message' => $body];
    }
}
