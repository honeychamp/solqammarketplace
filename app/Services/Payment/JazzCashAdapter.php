<?php

namespace App\Services\Payment;

use Config\Payments;

class JazzCashAdapter implements PaymentGatewayInterface
{
    protected Payments $config;
    protected string $txnType;

    public function __construct(string $txnType = 'MWALLET')
    {
        $this->config  = config('Payments');
        $this->txnType = $txnType;
    }

    public function getGatewayName(): string
    {
        return $this->txnType === $this->config->jazzcashCardTxnType ? 'card' : 'jazzcash';
    }

    public function initiatePayment(array $orderData): array
    {
        $txnRefNo = 'T' . date('YmdHis') . random_int(1000, 9999);
        $amount   = (float) ($orderData['amount'] ?? 0);
        $paisa    = (string) (int) round($amount * 100);

        $fields = [
            'pp_Version'          => '1.1',
            'pp_TxnType'          => $this->txnType,
            'pp_Language'         => 'EN',
            'pp_MerchantID'       => $this->config->jazzcashMerchantId,
            'pp_Password'         => $this->config->jazzcashPassword,
            'pp_TxnRefNo'         => $txnRefNo,
            'pp_Amount'           => $paisa,
            'pp_TxnCurrency'      => 'PKR',
            'pp_TxnDateTime'      => date('YmdHis'),
            'pp_BillReference'    => (string) ($orderData['order_number'] ?? ''),
            'pp_Description'      => 'Solqam order ' . ($orderData['order_number'] ?? ''),
            'pp_TxnExpiryDateTime'=> date('YmdHis', strtotime('+2 hours')),
            'pp_ReturnURL'        => site_url('payments/jazzcash/return'),
            'ppmpf_1'             => (string) ($orderData['order_id'] ?? ''),
            'ppmpf_2'             => (string) ($orderData['user_id'] ?? ''),
            'ppmpf_3'             => $this->getGatewayName(),
        ];

        $fields['pp_SecureHash'] = $this->makeHash($fields);

        return [
            'success'                => true,
            'awaiting_confirmation'  => true,
            'transaction_ref'        => $txnRefNo,
            'redirect_url'           => $this->config->jazzcashEndpoint(),
            'http_method'            => 'POST',
            'fields'                 => $fields,
            'message'                => 'Customer must complete JazzCash. Order stays pending until JazzCash return/IPN.',
            'payload'                => $fields,
            'configured'             => $this->config->jazzcashReady(),
        ];
    }

    public function verifyPayment(array $payload): array
    {
        $ref    = (string) ($payload['pp_TxnRefNo'] ?? '');
        $code   = (string) ($payload['pp_ResponseCode'] ?? '');
        $amount = isset($payload['pp_Amount']) ? ((float) $payload['pp_Amount'] / 100) : 0.0;
        $hashOk = $this->hashMatches($payload);

        if (! $hashOk && $this->config->jazzcashReady()) {
            return [
                'success'         => false,
                'transaction_ref' => $ref,
                'paid_amount'     => 0.0,
                'message'         => 'JazzCash secure hash mismatch.',
                'order_id'        => isset($payload['ppmpf_1']) ? (int) $payload['ppmpf_1'] : null,
                'order_number'    => $payload['pp_BillReference'] ?? null,
            ];
        }

        $ok = $code === '000';

        return [
            'success'         => $ok,
            'transaction_ref' => $ref,
            'paid_amount'     => $ok ? $amount : 0.0,
            'message'         => $payload['pp_ResponseMessage'] ?? ($ok ? 'JazzCash confirmed.' : 'JazzCash not confirmed.'),
            'order_id'        => isset($payload['ppmpf_1']) ? (int) $payload['ppmpf_1'] : null,
            'order_number'    => $payload['pp_BillReference'] ?? null,
        ];
    }

    protected function makeHash(array $fields): string
    {
        $salt = $this->config->jazzcashIntegritySalt;
        unset($fields['pp_SecureHash']);
        ksort($fields);
        $parts = [$salt];
        foreach ($fields as $value) {
            if ($value === null || $value === '') {
                continue;
            }
            $parts[] = $value;
        }

        return strtoupper(hash_hmac('sha256', implode('&', $parts), $salt));
    }

    protected function hashMatches(array $payload): bool
    {
        $given = (string) ($payload['pp_SecureHash'] ?? '');
        if ($given === '' || $this->config->jazzcashIntegritySalt === '') {
            return false;
        }
        $calc = $this->makeHash($payload);

        return hash_equals($calc, strtoupper($given));
    }
}
