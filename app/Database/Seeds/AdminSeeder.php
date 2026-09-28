<?php

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;

class AdminSeeder extends Seeder
{
    public function run()
    {
        $db = \Config\Database::connect();
        
        $existing = $db->table('users')->where('email', 'admin@solqam.pk')->orWhere('role', 'admin')->get()->getRowArray();
        
        if (!$existing) {
            $db->table('users')->insert([
                'name'          => 'Solqam Administrator',
                'email'         => 'admin@solqam.pk',
                'phone'         => '03111222333',
                'password_hash' => password_hash('admin123', PASSWORD_BCRYPT),
                'role'          => 'admin',
                'status'        => 'active',
                'is_verified'   => 1,
                'api_token'     => bin2hex(random_bytes(32)),
                'created_at'    => date('Y-m-d H:i:s'),
                'updated_at'    => date('Y-m-d H:i:s'),
            ]);
            $adminId = $db->insertID();

            // Ensure wallet exists
            $wallet = $db->table('wallets')->where('user_id', $adminId)->get()->getRowArray();
            if (!$wallet) {
                $db->table('wallets')->insert([
                    'user_id'    => $adminId,
                    'created_at' => date('Y-m-d H:i:s'),
                    'updated_at' => date('Y-m-d H:i:s'),
                ]);
            }
            echo "Admin user created successfully: admin@solqam.pk / admin123\n";
        } else {
            // Ensure password is admin123 and status is active
            $db->table('users')->where('id', $existing['id'])->update([
                'password_hash' => password_hash('admin123', PASSWORD_BCRYPT),
                'status'        => 'active',
                'is_verified'   => 1,
                'role'          => 'admin',
            ]);
            echo "Admin user updated: {$existing['email']} / admin123\n";
        }
    }
}
