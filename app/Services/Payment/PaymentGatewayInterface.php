<?php

namespace App\Services\Payment;

interface PaymentGatewayInterface
{
    /**
     * Build a hosted checkout request. Never marks the order paid.
     *
     * @return array{
     *   success: bool,
     *   awaiting_confirmation: bool,
     *   transaction_ref: string,
     *   redirect_url: ?string,
     *   http_method: string,
     *   fields: array,
     *   message: string,
     *   payload: array,
     *   configured: bool
     * }
     */
    public function initiatePayment(array $orderData): array;

    /**
     * Verify JazzCash / EasyPaisa return or IPN payload.
     *
     * @return array{success: bool, transaction_ref: string, paid_amount: float, message: string, order_id: ?int, order_number: ?string}
     */
    public function verifyPayment(array $payload): array;

    public function getGatewayName(): string;
}
