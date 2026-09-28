<?php

namespace Config;

use CodeIgniter\Config\BaseConfig;

/**
 * Plug any SMS vendor later by changing sms.driver in .env.
 * mock | jazz | jazzcmt | jazzsms | twilio | http
 */
class Sms extends BaseConfig
{
    public bool $enabled = true;
    public string $driver = 'mock';
    public bool $demoBypass = false;
    public string $sender = 'SOLQAM';

    public string $jazzcmtUsername = '';
    public string $jazzcmtPassword = '';
    public string $jazzcmtMask = '';
    public string $jazzcmtUrl = 'https://connect.jazzcmt.com/sendsms_url.html';

    public string $twilioSid = '';
    public string $twilioToken = '';
    public string $twilioFrom = '';

    public string $httpUrl = '';
    public string $httpMethod = 'GET';
    public string $httpBody = '';
    public string $httpContentType = '';
    public string $httpHeaders = '';

    public function __construct()
    {
        parent::__construct();

        $this->enabled = filter_var(env('sms.enabled', true), FILTER_VALIDATE_BOOLEAN);
        $raw = strtolower(trim((string) env('sms.driver', 'mock')));
        $this->driver = match ($raw) {
            'jazz', 'jazzsms', 'jazz-cmt', 'jazzcmt' => 'jazzcmt',
            'twilio' => 'twilio',
            'http', 'custom', 'generic' => 'http',
            default => 'mock',
        };

        $this->demoBypass = filter_var(env('sms.demoBypass', false), FILTER_VALIDATE_BOOLEAN);
        $this->sender = (string) env('sms.sender', 'SOLQAM');
        $this->jazzcmtUsername = (string) env('sms.jazzcmt.username', '');
        $this->jazzcmtPassword = (string) env('sms.jazzcmt.password', '');
        $this->jazzcmtMask = (string) env('sms.jazzcmt.mask', $this->sender);
        $this->jazzcmtUrl = (string) env('sms.jazzcmt.url', $this->jazzcmtUrl);
        $this->twilioSid = (string) env('sms.twilio.sid', '');
        $this->twilioToken = (string) env('sms.twilio.token', '');
        $this->twilioFrom = (string) env('sms.twilio.from', '');
        $this->httpUrl = (string) env('sms.http.url', '');
        $this->httpMethod = strtoupper((string) env('sms.http.method', 'GET'));
        $this->httpBody = (string) env('sms.http.body', '');
        $this->httpContentType = (string) env('sms.http.contentType', '');
        $this->httpHeaders = (string) env('sms.http.headers', '');
    }

    public function resolvedDriver(): string
    {
        if (! $this->enabled) {
            return 'mock';
        }
        if ($this->driver !== 'mock' && ! $this->isConfigured()) {
            return 'mock';
        }

        return $this->driver;
    }

    public function isLive(): bool
    {
        return $this->resolvedDriver() !== 'mock';
    }

    public function isConfigured(): bool
    {
        return match ($this->driver) {
            'jazzcmt' => $this->jazzcmtUsername !== '' && $this->jazzcmtPassword !== '',
            'twilio'  => $this->twilioSid !== '' && $this->twilioToken !== '' && $this->twilioFrom !== '',
            'http'    => $this->httpUrl !== '',
            default   => true,
        };
    }
}
