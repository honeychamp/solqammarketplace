<?php

namespace App\Services\Payment;

use Config\Payments;

class EasyPaisaAdapter implements PaymentGatewayInterface
{
    protected Payments $config;

    public function __construct()
    {
        $this->config = config('Payments');
    }

    public function getGatewayName(): string
    {
        return 'easypaisa';
    }

    public function initiatePayment(array $orderData): array
    {
        $orderRef = (string) ($orderData['order_number'] ?? ('SOL' . time()));
        $amount   = number_format((float) ($orderData['amount'] ?? 0), 1, '.', '');
        $postBack = site_url('payments/easypaisa/return');

        $fields = [
            'storeId'           => $this->config->easypaisaStoreId,
            'amount'            => $amount,
            'postBackURL'       => $postBack,
            'orderRefNum'       => $orderRef,
            'expiryDate'        => date('Ymd His', strtotime('+2 hours')),
            'autoRedirect'      => '1',
            'paymentMethod'     => 'MA_PAYMENT',
        ];
        if ($this->config->easypaisaAccountNum !== '') {
            $fields['accountNum'] = $this->config->easypaisaAccountNum;
        }
        $fields['merchantHashedReq'] = $this->makeHash($fields);

        return [
            'success'               => true,
            'awaiting_confirmation' => true,
            'transaction_ref'       => $orderRef,
            'redirect_url'          => $this->config->easypaisaEndpoint(),
            'http_method'           => 'POST',
            'fields'                => $fields,
            'message'               => 'Customer must complete EasyPaisa. Order stays pending until EasyPaisa postback.',
            'payload'               => $fields + ['order_id' => $orderData['order_id'] ?? null],
            'configured'            => $this->config->easypaisaReady(),
        ];
    }

    public function verifyPayment(array $payload): array
    {
        $ref    = (string) ($payload['orderRefNumber'] ?? $payload['orderRefNum'] ?? $payload['orderId'] ?? '');
        $status = (string) ($payload['status'] ?? $payload['responseCode'] ?? $payload['desc'] ?? '');
        $ok     = in_array($status, ['000', '0000', 'SUCCESS', 'success', 'PAID', 'paid'], true);
        $amount = (float) ($payload['transactionAmount'] ?? $payload['amount'] ?? 0);

        return [
            'success'         => $ok,
            'transaction_ref' => $ref,
            'paid_amount'     => $ok ? $amount : 0.0,
            'message'         => (string) ($payload['desc'] ?? $payload['responseMessage'] ?? ($ok ? 'EasyPaisa confirmed.' : 'EasyPaisa not confirmed.')),
            'order_id'        => isset($payload['order_id']) ? (int) $payload['order_id'] : null,
            'order_number'    => $ref !== '' ? $ref : null,
        ];
    }

    protected function makeHash(array $fields): string
    {
        $key = $this->config->easypaisaHashKey;
        $map = $fields['storeId'] . $fields['amount'] . $fields['postBackURL'] . $fields['orderRefNum'];

        return hash_hmac('sha256', $map, $key);
    }
}
