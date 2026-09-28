<?php

namespace App\Services\Sms;

class JazzCmtSmsGateway implements SmsGatewayInterface
{
    public function send(string $phone, string $message): bool
    {
        $cfg = config('Sms');
        if ($cfg->jazzcmtUsername === '' || $cfg->jazzcmtPassword === '') {
            log_message('error', 'Jazz SMS: username/password missing');

            return false;
        }
        $to  = pk_msisdn($phone);
        $url = $cfg->jazzcmtUrl . '?' . http_build_query([
            'Username' => $cfg->jazzcmtUsername,
            'Password' => $cfg->jazzcmtPassword,
            'From'     => $cfg->jazzcmtMask !== '' ? $cfg->jazzcmtMask : $cfg->sender,
            'To'       => $to,
            'Message'  => $message,
        ]);

        $client = service('curlrequest');
        $res = $client->get($url, ['http_errors' => false, 'timeout' => 20]);
        $body = strtolower((string) $res->getBody());
        $ok = $res->getStatusCode() < 400 && ! str_contains($body, 'error') && ! str_contains($body, 'fail');
        if (! $ok) {
            log_message('error', 'Jazz CMT SMS failed: HTTP ' . $res->getStatusCode() . ' ' . $body);
        }

        return $ok;
    }

    public function driver(): string
    {
        return 'jazzcmt';
    }
}
