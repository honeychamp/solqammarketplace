<?php

namespace App\Services\Platform;

use Config\Database;

class SettingService
{
    /** @var array<string, string> */
    protected static array $cache = [];

    protected static bool $hydrated = false;

    public static function get(string $key, string $default = ''): string
    {
        self::hydrate();
        if (array_key_exists($key, self::$cache)) {
            return self::$cache[$key];
        }

        return $default;
    }

    protected static function hydrate(): void
    {
        if (self::$hydrated) {
            return;
        }
        self::$hydrated = true;
        try {
            $hit = cache()->get('sf_platform_settings');
            if (is_array($hit)) {
                self::$cache = $hit;

                return;
            }
            $rows = Database::connect()->table('platform_settings')->get()->getResultArray();
            foreach ($rows as $row) {
                self::$cache[(string) $row['setting_key']] = (string) $row['setting_value'];
            }
            cache()->save('sf_platform_settings', self::$cache, 180);
        } catch (\Throwable $e) {
        }
    }

    public static function set(string $key, string $value): void
    {
        self::$cache[$key] = $value;
        self::$hydrated = true;
        try {
            cache()->delete('sf_platform_settings');
        } catch (\Throwable $e) {
        }
        $db = Database::connect();
        $now = date('Y-m-d H:i:s');
        $exists = $db->table('platform_settings')->where('setting_key', $key)->countAllResults();
        if ($exists) {
            $db->table('platform_settings')->where('setting_key', $key)->update(['setting_value' => $value, 'updated_at' => $now]);
        } else {
            $db->table('platform_settings')->insert(['setting_key' => $key, 'setting_value' => $value, 'updated_at' => $now]);
        }
    }

    public static function int(string $key, int $default = 0): int
    {
        return (int) self::get($key, (string) $default);
    }

    /**
     * JazzCash / EasyPaisa / bank where sellers send COD commission + cashback.
     *
     * @return array{jazzcash_name:string,jazzcash_number:string,easypaisa_name:string,easypaisa_number:string,bank_name:string,bank_title:string,bank_account:string}
     */
    public static function collectionAccounts(): array
    {
        return [
            'jazzcash_name'    => self::get('collect_jazzcash_name'),
            'jazzcash_number'  => self::get('collect_jazzcash_number'),
            'easypaisa_name'   => self::get('collect_easypaisa_name'),
            'easypaisa_number' => self::get('collect_easypaisa_number'),
            'bank_name'        => self::get('collect_bank_name'),
            'bank_title'       => self::get('collect_bank_title'),
            'bank_account'     => self::get('collect_bank_account'),
        ];
    }

    public static function hasCollectionAccounts(): bool
    {
        $a = self::collectionAccounts();

        return $a['jazzcash_number'] !== '' || $a['easypaisa_number'] !== '' || $a['bank_account'] !== '';
    }
}
