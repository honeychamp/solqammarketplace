<?php

namespace Config;

use CodeIgniter\Config\BaseConfig;

class Payments extends BaseConfig
{
    public bool $live = false;

    public string $payfastMerchantId = '';
    public string $payfastSecuredKey = '';
    public string $payfastMerchantName = 'Solqam Market Place';
    public string $payfastTokenSandbox = 'https://ipguat.apps.net.pk/Ecommerce/api/Transaction/GetAccessToken';
    public string $payfastTokenLive = 'https://ipg.apps.net.pk/Ecommerce/api/Transaction/GetAccessToken';
    public string $payfastCheckoutSandbox = 'https://ipguat.apps.net.pk/Ecommerce/api/Transaction/PostTransaction';
    public string $payfastCheckoutLive = 'https://ipg.apps.net.pk/Ecommerce/api/Transaction/PostTransaction';

    public function __construct()
    {
        parent::__construct();
        $this->live = filter_var(env('payment.live', false), FILTER_VALIDATE_BOOLEAN);
        $this->payfastMerchantId = (string) env('payfast.merchantId', '');
        $this->payfastSecuredKey = (string) env('payfast.securedKey', '');
        $this->payfastMerchantName = (string) env('payfast.merchantName', $this->payfastMerchantName);
        $this->payfastTokenSandbox = (string) env('payfast.tokenSandboxUrl', $this->payfastTokenSandbox);
        $this->payfastTokenLive = (string) env('payfast.tokenLiveUrl', $this->payfastTokenLive);
        $this->payfastCheckoutSandbox = (string) env('payfast.checkoutSandboxUrl', $this->payfastCheckoutSandbox);
        $this->payfastCheckoutLive = (string) env('payfast.checkoutLiveUrl', $this->payfastCheckoutLive);
    }

    public function payfastReady(): bool
    {
        return $this->payfastMerchantId !== '' && $this->payfastSecuredKey !== '';
    }

    public function payfastTokenUrl(): string
    {
        return $this->live ? $this->payfastTokenLive : $this->payfastTokenSandbox;
    }

    public function payfastCheckoutUrl(): string
    {
        return $this->live ? $this->payfastCheckoutLive : $this->payfastCheckoutSandbox;
    }
}
