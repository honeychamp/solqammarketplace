<?php

namespace App\Models;

use CodeIgniter\Model;

class MarketingSettingModel extends Model
{
    protected $table         = 'marketing_settings';
    protected $primaryKey    = 'id';
    protected $returnType    = 'array';
    protected $allowedFields = [
        'enabled',
        'test_mode',
        'consent_required',
        'server_pageview',
        'currency',
        'meta_pixel_id',
        'meta_capi_token',
        'meta_test_event_code',
        'tiktok_pixel_id',
        'tiktok_api_token',
        'tiktok_app_id',
        'gtm_id',
        'ga4_id',
        'google_ads_id',
        'google_ads_label',
    ];
    protected $useTimestamps = true;

    public const SECRET_FIELDS = ['meta_capi_token', 'tiktok_api_token'];

    public function current(): array
    {
        try {
            $row = $this->orderBy('id', 'DESC')->first();
        } catch (\Throwable $e) {
            return $this->defaults();
        }
        if (! $row) {
            return $this->defaults();
        }
        foreach (self::SECRET_FIELDS as $field) {
            $row[$field] = $this->reveal((string) ($row[$field] ?? ''));
        }

        return array_merge($this->defaults(), $row);
    }

    public function saveCurrent(array $data): void
    {
        $row = $this->orderBy('id', 'DESC')->first();
        $payload = [
            'enabled'              => ! empty($data['enabled']) ? 1 : 0,
            'test_mode'            => ! empty($data['test_mode']) ? 1 : 0,
            'consent_required'     => ! empty($data['consent_required']) ? 1 : 0,
            'server_pageview'      => ! empty($data['server_pageview']) ? 1 : 0,
            'currency'             => strtoupper(substr(preg_replace('/[^A-Za-z]/', '', (string) ($data['currency'] ?? 'PKR')) ?: 'PKR', 0, 8)),
            'meta_pixel_id'        => trim((string) ($data['meta_pixel_id'] ?? '')),
            'meta_test_event_code' => trim((string) ($data['meta_test_event_code'] ?? '')),
            'tiktok_pixel_id'      => trim((string) ($data['tiktok_pixel_id'] ?? '')),
            'tiktok_app_id'        => trim((string) ($data['tiktok_app_id'] ?? '')),
            'gtm_id'               => strtoupper(trim((string) ($data['gtm_id'] ?? ''))),
            'ga4_id'               => strtoupper(trim((string) ($data['ga4_id'] ?? ''))),
            'google_ads_id'        => strtoupper(trim((string) ($data['google_ads_id'] ?? ''))),
            'google_ads_label'     => trim((string) ($data['google_ads_label'] ?? '')),
        ];

        $existing = $row ? $this->current() : $this->defaults();
        foreach (self::SECRET_FIELDS as $field) {
            $incoming = self::decodePostedSecret((string) ($data[$field] ?? ''));
            $payload[$field] = $this->seal($incoming !== '' ? $incoming : (string) ($existing[$field] ?? ''));
        }

        if ($row) {
            $this->update((int) $row['id'], $payload);
        } else {
            $this->insert($payload);
        }
    }

    public function publicConfig(): array
    {
        $s = $this->current();

        return [
            'enabled'          => (int) ($s['enabled'] ?? 0) === 1,
            'test_mode'        => (int) ($s['test_mode'] ?? 0) === 1,
            'consent_required' => (int) ($s['consent_required'] ?? 0) === 1,
            'currency'         => $s['currency'] ?: 'PKR',
            'meta_pixel_id'    => (string) ($s['meta_pixel_id'] ?? ''),
            'tiktok_pixel_id'  => (string) ($s['tiktok_pixel_id'] ?? ''),
            'gtm_id'           => (string) ($s['gtm_id'] ?? ''),
            'ga4_id'           => (string) ($s['ga4_id'] ?? ''),
            'google_ads_id'    => (string) ($s['google_ads_id'] ?? ''),
            'google_ads_label' => (string) ($s['google_ads_label'] ?? ''),
        ];
    }

    public function defaults(): array
    {
        return [
            'id'                   => 0,
            'enabled'              => 0,
            'test_mode'            => 0,
            'consent_required'     => 0,
            'server_pageview'      => 1,
            'currency'             => 'PKR',
            'meta_pixel_id'        => '',
            'meta_capi_token'      => '',
            'meta_test_event_code' => '',
            'tiktok_pixel_id'      => '',
            'tiktok_api_token'     => '',
            'tiktok_app_id'        => '',
            'gtm_id'               => '',
            'ga4_id'               => '',
            'google_ads_id'        => '',
            'google_ads_label'     => '',
        ];
    }

    public static function decodePostedSecret(string $incoming): string
    {
        $incoming = trim($incoming);
        if ($incoming === '') {
            return '';
        }
        if (str_starts_with($incoming, 'rawb64:')) {
            $decoded = base64_decode(substr($incoming, 7), true);
            if (is_string($decoded) && $decoded !== '') {
                return trim($decoded);
            }
        }

        return $incoming;
    }

    public static function mask(?string $value): string
    {
        $value = trim((string) $value);
        if ($value === '') {
            return '';
        }
        $len = strlen($value);
        if ($len <= 8) {
            return str_repeat('•', $len);
        }

        return substr($value, 0, 4) . str_repeat('•', min(18, $len - 8)) . substr($value, -4);
    }

    protected function seal(string $plain): string
    {
        $plain = trim($plain);
        if ($plain === '') {
            return '';
        }
        try {
            $enc = \Config\Services::encrypter();
            if ((string) config('Encryption')->key === '') {
                throw new \RuntimeException('no key');
            }

            return 'enc:' . base64_encode($enc->encrypt($plain));
        } catch (\Throwable $e) {
            return 'b64:' . base64_encode($plain);
        }
    }

    protected function reveal(string $stored): string
    {
        $stored = trim($stored);
        if ($stored === '') {
            return '';
        }
        try {
            if (str_starts_with($stored, 'enc:')) {
                $enc = \Config\Services::encrypter();

                return (string) $enc->decrypt(base64_decode(substr($stored, 4), true) ?: '');
            }
            if (str_starts_with($stored, 'b64:')) {
                return (string) base64_decode(substr($stored, 4), true);
            }
        } catch (\Throwable $e) {
            return '';
        }

        return $stored;
    }
}
