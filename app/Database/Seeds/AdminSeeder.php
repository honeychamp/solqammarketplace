<?php

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;

/**
 * Admin login comes from .env (admin.email / admin.password).
 * Survives migrate:refresh --seed and CleanResetSeeder.
 */
class AdminSeeder extends Seeder
{
    public function run()
    {
        $db = \Config\Database::connect();

        $email = trim((string) env('admin.email', 'admin@solqam.pk'));
        $password = (string) env('admin.password', 'admin123');
        $name = trim((string) env('admin.name', 'Solqam Administrator'));
        $phone = trim((string) env('admin.phone', '03111222333'));
        if ($email === '' || ! filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $email = 'admin@solqam.pk';
        }
        if ($password === '') {
            $password = 'admin123';
        }
        if ($name === '') {
            $name = 'Solqam Administrator';
        }

        $now = date('Y-m-d H:i:s');
        $payload = [
            'name'          => $name,
            'email'         => $email,
            'phone'         => $phone !== '' ? $phone : '03111222333',
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

        echo "Admin ready: {$email} (password from .env admin.password)\n";
    }
}
