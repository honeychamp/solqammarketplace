<?php

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;

/**
 * Single platform admin. Credentials live in this seeder
 * (EMAIL / PASSWORD). Optional .env admin.* overrides them.
 */
class AdminSeeder extends Seeder
{
    public const EMAIL    = 'admin@solqam.pk';
    public const PASSWORD = 'admin123';
    public const NAME     = 'Solqam Administrator';
    public const PHONE    = '03111222333';

    public function run()
    {
        $db = \Config\Database::connect();

        $email    = trim((string) env('admin.email', self::EMAIL));
        $password = (string) env('admin.password', self::PASSWORD);
        $name     = trim((string) env('admin.name', self::NAME));
        $phone    = trim((string) env('admin.phone', self::PHONE));
        if ($phone === '') {
            $phone = self::PHONE;
        }
        if ($email === '' || ! filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $email = self::EMAIL;
        }
        if ($password === '') {
            $password = self::PASSWORD;
        }
        if ($name === '') {
            $name = self::NAME;
        }

        $now = date('Y-m-d H:i:s');
        $payload = [
            'name'          => $name,
            'email'         => $email,
            'phone'         => $phone,
            'password_hash' => password_hash($password, PASSWORD_BCRYPT),
            'role'          => 'admin',
            'status'        => 'active',
            'is_verified'   => 1,
            'updated_at'    => $now,
        ];

        $existing = $db->table('users')->where('role', 'admin')->get()->getRowArray();
        if (! $existing) {
            $existing = $db->table('users')->where('email', $email)->get()->getRowArray();
        }

        if (! $existing) {
            $payload['api_token']  = bin2hex(random_bytes(32));
            $payload['created_at'] = $now;
            $db->table('users')->insert($payload);
            $adminId = (int) $db->insertID();
        } else {
            $db->table('users')->where('id', $existing['id'])->update($payload);
            $adminId = (int) $existing['id'];
        }

        $wallet = $db->table('wallets')->where('user_id', $adminId)->get()->getRowArray();
        if (! $wallet) {
            $db->table('wallets')->insert([
                'user_id'    => $adminId,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }

        echo "Admin ready: {$email}\n";
    }
}
