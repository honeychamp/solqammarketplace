<?php

namespace App\Services\Auth;

use Config\Database;

class RateLimitService
{
    public static function hit(string $action, int $max = 8, int $minutes = 15): bool
    {
        $ip = service('request')->getIPAddress();
        $db = Database::connect();
        $since = date('Y-m-d H:i:s', time() - ($minutes * 60));
        try {
            $row = $db->table('auth_attempts')
                ->where('ip_address', $ip)
                ->where('action', $action)
                ->where('window_at >=', $since)
                ->orderBy('id', 'DESC')
                ->get()
                ->getRowArray();
            if (! $row) {
                $db->table('auth_attempts')->insert([
                    'ip_address' => $ip,
                    'action'     => $action,
                    'hits'       => 1,
                    'window_at'  => date('Y-m-d H:i:s'),
                ]);
                return true;
            }
            if ((int) $row['hits'] >= $max) {
                return false;
            }
            $db->table('auth_attempts')->where('id', $row['id'])->update(['hits' => (int) $row['hits'] + 1]);
            return true;
        } catch (\Throwable $e) {
            return true;
        }
    }
}
