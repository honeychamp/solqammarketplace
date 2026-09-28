<?php

namespace App\Services\Sms;

class SmsNotifier
{
    public static function send(string $phone, string $message, string $event = 'generic'): void
    {
        $phone = trim($phone);
        $message = trim($message);
        if ($phone === '' || $message === '') {
            return;
        }

        self::archive($phone, $message, $event);

        try {
            SmsGatewayFactory::make()->send($phone, $message);
        } catch (\Throwable $e) {
            log_message('error', 'SMS send error [' . $event . ']: ' . $e->getMessage());
        }
    }

    public static function otp(string $phone, string $code, string $purpose = 'signup'): void
    {
        self::send($phone, "Solqam code {$code}. Valid 10 minutes. Do not share.", 'otp_' . $purpose);
    }

    public static function orderPlaced(string $phone, string $orderNumber, string $method, float $payable, string $paymentStatus): void
    {
        if ($paymentStatus === 'paid') {
            $msg = "Solqam: Order {$orderNumber} placed. Payment confirmed. Payable Rs. " . number_format($payable, 0) . '.';
        } elseif (is_online_gateway($method)) {
            $msg = "Solqam: Order {$orderNumber} placed. Complete PayFast of Rs. " . number_format($payable, 0) . ' (JazzCash / EasyPaisa / card). Not paid yet.';
        } elseif ($method === 'pay_later') {
            $msg = "Solqam: Order {$orderNumber} placed on Pay later. Remaining Rs. " . number_format($payable, 0) . '.';
        } else {
            $msg = "Solqam: Order {$orderNumber} placed. Pay Rs. " . number_format($payable, 0) . ' COD on delivery.';
        }
        self::send($phone, $msg, 'order_placed');
    }

    public static function paymentConfirmed(string $phone, string $orderNumber, float $amount, string $method): void
    {
        self::send(
            $phone,
            "Solqam: Payment confirmed for order {$orderNumber}. Rs. " . number_format($amount, 0) . " via {$method}.",
            'payment_confirmed'
        );
    }

    public static function paymentFailed(string $phone, string $orderNumber): void
    {
        self::send($phone, "Solqam: Payment not confirmed for order {$orderNumber}. Open the order and try again.", 'payment_failed');
    }

    public static function orderConfirmed(string $phone, string $orderNumber): void
    {
        self::send($phone, "Solqam: Order {$orderNumber} confirmed. Seller is preparing your parcel.", 'order_confirmed');
    }

    public static function orderShipped(string $phone, string $orderNumber, string $courier = '', string $tracking = ''): void
    {
        $extra = '';
        if ($courier !== '' || $tracking !== '') {
            $extra = ' ' . trim($courier . ' ' . $tracking);
        }
        self::send($phone, "Solqam: Order {$orderNumber} shipped.{$extra}", 'order_shipped');
    }

    public static function orderDelivered(string $phone, string $orderNumber): void
    {
        self::send($phone, "Solqam: Order {$orderNumber} delivered. Thank you.", 'order_delivered');
    }

    public static function orderCancelled(string $phone, string $orderNumber): void
    {
        self::send($phone, "Solqam: Order {$orderNumber} cancelled. Wallet amounts are refunded if used.", 'order_cancelled');
    }

    public static function notifyOrderStatus(array $order, string $newStatus, array $extra = []): void
    {
        $phone = (string) ($extra['phone'] ?? '');
        $num = (string) ($order['order_number'] ?? '');
        if ($phone === '' || $num === '') {
            return;
        }

        match ($newStatus) {
            'confirmed' => self::orderConfirmed($phone, $num),
            'shipped'   => self::orderShipped($phone, $num, (string) ($extra['courier'] ?? $order['courier'] ?? ''), (string) ($extra['tracking_number'] ?? $order['tracking_number'] ?? '')),
            'delivered' => self::orderDelivered($phone, $num),
            'cancelled' => self::orderCancelled($phone, $num),
            default     => null,
        };
    }

    protected static function archive(string $phone, string $message, string $event): void
    {
        $dir = WRITEPATH . 'smsbox';
        if (! is_dir($dir)) {
            @mkdir($dir, 0775, true);
        }
        $driver = config('Sms')->resolvedDriver();
        $html = '<pre>' . htmlspecialchars(json_encode([
            'at'      => date('c'),
            'driver'  => $driver,
            'event'   => $event,
            'to'      => $phone,
            'msisdn'  => pk_msisdn($phone),
            'message' => $message,
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)) . '</pre>';
        @file_put_contents($dir . '/' . date('Ymd-His') . '-' . preg_replace('/\W+/', '_', $event) . '.html', $html);
    }
}
