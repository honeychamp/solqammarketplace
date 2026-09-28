<?php

namespace App\Services\Sms;

class SmsGatewayFactory
{
    public static function make(): SmsGatewayInterface
    {
        $cfg = config('Sms');
        $driver = $cfg->resolvedDriver();

        if ($cfg->driver !== 'mock' && $driver === 'mock') {
            log_message('warning', 'SMS driver ' . $cfg->driver . ' selected but keys/URL missing — using mock archive (writable/smsbox).');
        }

        return match ($driver) {
            'jazzcmt' => new JazzCmtSmsGateway(),
            'twilio'  => new TwilioSmsGateway(),
            'http'    => new HttpSmsGateway(),
            default   => new MockSmsGateway(),
        };
    }
}
