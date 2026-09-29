<?php

namespace App\Services\Platform;

use Config\Database;

class SettingService
{
    public static function get(string $key, string $default = ''): string
    {
        try {
            $row = Database::connect()->table('platform_settings')->where('setting_key', $key)->get()->getRowArray();
            return $row ? (string) $row['setting_value'] : $default;
        } catch (\Throwable $e) {
            return $default;
        }
    }

    public static function set(string $key, string $value): void
    {
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
}
