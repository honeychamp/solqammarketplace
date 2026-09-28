<?php

namespace App\Services\Payment;

use InvalidArgumentException;

class GatewayFactory
{
    public static function make(string $method): PaymentGatewayInterface
    {
        return match ($method) {
            'payfast', 'jazzcash', 'easypaisa', 'card' => new PayFastAdapter(),
            default => throw new InvalidArgumentException('Unsupported payment gateway: ' . $method),
        };
    }
}
