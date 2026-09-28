<?php

namespace App\Services\Sms;

class MockSmsGateway implements SmsGatewayInterface
{
    public function send(string $phone, string $message): bool
    {
        log_message('info', '[Solqam SMS mock] to ' . $phone . ': ' . $message);

        return true;
    }

    public function driver(): string
    {
        return 'mock';
    }
}
