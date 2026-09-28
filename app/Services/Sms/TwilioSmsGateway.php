<?php

namespace App\Services\Sms;

class TwilioSmsGateway implements SmsGatewayInterface
{
    public function send(string $phone, string $message): bool
    {
        $cfg = config('Sms');
        if ($cfg->twilioSid === '' || $cfg->twilioToken === '' || $cfg->twilioFrom === '') {
            log_message('error', 'Twilio SMS: sid/token/from missing');

            return false;
        }
        $to  = '+' . pk_msisdn($phone);
        $url = 'https://api.twilio.com/2010-04-01/Accounts/' . rawurlencode($cfg->twilioSid) . '/Messages.json';

        $client = service('curlrequest');
        $res = $client->post($url, [
            'auth'        => [$cfg->twilioSid, $cfg->twilioToken],
            'form_params' => [
                'To'   => $to,
                'From' => $cfg->twilioFrom,
                'Body' => $message,
            ],
            'http_errors' => false,
            'timeout'     => 20,
        ]);
        $ok = $res->getStatusCode() >= 200 && $res->getStatusCode() < 300;
        if (! $ok) {
            log_message('error', 'Twilio SMS failed: HTTP ' . $res->getStatusCode() . ' ' . $res->getBody());
        }

        return $ok;
    }

    public function driver(): string
    {
        return 'twilio';
    }
}
