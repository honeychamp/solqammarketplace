<?php

namespace App\Services\Payment;

use Config\Payments;

/**
 * PayFast (gopayfast) hosted checkout.
 * JazzCash, EasyPaisa and cards are collected on PayFast's page — Solqam only stores method=payfast.
 * Tokens are requested only when merchant_id + secured_key are in .env.
 */
class PayFastAdapter implements PaymentGatewayInterface
{
    protected Payments $config;

    public function __construct()
    {
        $this->config = config('Payments');
    }

    public function getGatewayName(): string
    {
        return 'payfast';
    }

    public function initiatePayment(array $orderData): array
    {
        $basketId = (string) ($orderData['order_number'] ?? '');
        $amount   = number_format((float) ($orderData['amount'] ?? 0), 2, '.', '');
        $signature = $this->signature($amount, $basketId);

        $fields = [
            'MERCHANT_ID'             => $this->config->payfastMerchantId,
            'MERCHANT_NAME'           => $this->config->payfastMerchantName,
            'TOKEN'                   => '',
            'PROCCODE'                => '00',
            'TXNAMT'                  => $amount,
            'CUSTOMER_MOBILE_NO'      => (string) ($orderData['phone'] ?? ''),
            'CUSTOMER_EMAIL_ADDRESS'  => (string) ($orderData['email'] ?? ''),
            'SIGNATURE'               => $signature,
            'VERSION'                 => 'SOLQAM-PAYFAST-1.0',
            'TXNDESC'                 => 'Solqam order ' . $basketId,
            'SUCCESS_URL'             => site_url('payments/payfast/success'),
            'FAILURE_URL'             => site_url('payments/payfast/failure'),
            'BASKET_ID'               => $basketId,
            'ORDER_DATE'              => date('Y-m-d H:i:s'),
            'CHECKOUT_URL'            => site_url('payments/payfast/ipn'),
            'CURRENCY_CODE'           => 'PKR',
            'TRAN_TYPE'               => 'ECOMM_PURCHASE',
        ];

        $configured = $this->config->payfastReady();
        if ($configured) {
            $token = $this->requestAccessToken();
            if ($token === '') {
                throw new \RuntimeException('PayFast token nahi mila. Merchant ID / Secured Key check karein.');
            }
            $fields['TOKEN'] = $token;
        }

        return [
            'success'               => true,
            'awaiting_confirmation' => true,
            'transaction_ref'       => $basketId,
            'redirect_url'          => $this->config->payfastCheckoutUrl(),
            'http_method'           => 'POST',
            'fields'                => $fields,
            'message'               => 'Customer PayFast par JazzCash / EasyPaisa / card se pay karega. Paid tabhi jab IPN/return confirm ho.',
            'payload'               => $fields + ['order_id' => $orderData['order_id'] ?? null],
            'configured'            => $configured,
        ];
    }

    public function verifyPayment(array $payload): array
    {
        $basket = (string) ($payload['BASKET_ID'] ?? $payload['basket_id'] ?? $payload['order_no'] ?? $payload['ORDER_NO'] ?? '');
        $txn    = (string) ($payload['TRANSACTION_ID'] ?? $payload['transaction_id'] ?? $payload['err_code'] ?? $basket);
        $code   = (string) ($payload['err_code'] ?? $payload['status'] ?? $payload['STATUS'] ?? $payload['code'] ?? $payload['PROCCODE'] ?? '');
        $amount = (float) ($payload['TXNAMT'] ?? $payload['txnamt'] ?? $payload['amount'] ?? 0);
        $ok     = in_array($code, ['000', '00', '0', 'SUCCESS', 'success'], true);

        if ($ok && $this->config->payfastReady() && ! $this->signatureMatches($payload, $amount, $basket)) {
            $ok = false;
        }

        return [
            'success'         => $ok,
            'transaction_ref' => $txn !== '' ? $txn : $basket,
            'paid_amount'     => $ok ? $amount : 0.0,
            'message'         => (string) ($payload['err_msg'] ?? $payload['message'] ?? ($ok ? 'PayFast confirmed.' : 'PayFast payment not confirmed.')),
            'order_id'        => isset($payload['order_id']) ? (int) $payload['order_id'] : null,
            'order_number'    => $basket !== '' ? $basket : null,
        ];
    }

    protected function requestAccessToken(): string
    {
        $client = service('curlrequest');
        $res = $client->post($this->config->payfastTokenUrl(), [
            'http_errors' => false,
            'timeout'     => 25,
            'headers'     => ['Content-Type' => 'application/x-www-form-urlencoded'],
            'body'        => http_build_query([
                'merchant_id' => $this->config->payfastMerchantId,
                'secured_key' => $this->config->payfastSecuredKey,
                'grant_type'  => 'client_credentials',
            ]),
        ]);

        $body = json_decode((string) $res->getBody(), true);
        if (! is_array($body)) {
            log_message('error', 'PayFast token raw: ' . $res->getBody());

            return '';
        }

        return (string) ($body['token'] ?? $body['access_token'] ?? $body['ACCESS_TOKEN'] ?? '');
    }

    protected function signature(string $amount, string $basketId): string
    {
        return md5($this->config->payfastMerchantId . ':' . $this->config->payfastMerchantName . ':' . $amount . ':' . $basketId);
    }

    protected function signatureMatches(array $payload, float $amount, string $basket): bool
    {
        $given = (string) ($payload['SIGNATURE'] ?? $payload['signature'] ?? '');
        if ($given === '') {
            return true;
        }
        $amt = number_format($amount, 2, '.', '');
        $calc = $this->signature($amt, $basket);

        return hash_equals($calc, $given);
    }
}
