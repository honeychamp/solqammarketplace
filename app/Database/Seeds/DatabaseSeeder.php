<?php

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run()
    {
        $this->call(AdminSeeder::class);

        $db = \Config\Database::connect();
        $commission = $db->table('commissions')->where('is_active', 1)->get()->getRowArray();
        if (! $commission) {
            $now = date('Y-m-d H:i:s');
            $db->table('commissions')->insert([
                'name'       => 'Standard Platform Commission',
                'percentage' => 10.00,
                'is_active'  => 1,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }
    }
}
