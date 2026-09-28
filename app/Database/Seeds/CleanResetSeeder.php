<?php

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;

/**
 * Wipes marketplace demo/transaction data.
 * Keeps platform structure: categories, one commission rule, one admin login.
 *
 * Admin: admin@solqam.pk / admin123
 */
class CleanResetSeeder extends Seeder
{
    public function run()
    {
        $db = \Config\Database::connect();
        $db->query('SET FOREIGN_KEY_CHECKS = 0');

        $skip = ['migrations'];
        foreach ($db->listTables() as $table) {
            $name = preg_replace('/^' . preg_quote($db->getPrefix(), '/') . '/', '', $table);
            if (in_array($name, $skip, true)) {
                continue;
            }
            $db->query('TRUNCATE TABLE `' . str_replace('`', '', $table) . '`');
            echo "  Truncated: {$name}\n";
        }

        $db->query('SET FOREIGN_KEY_CHECKS = 1');

        $now = date('Y-m-d H:i:s');

        $db->table('users')->insert([
            'name'          => 'Solqam Administrator',
            'email'         => 'admin@solqam.pk',
            'phone'         => '03111222333',
            'password_hash' => password_hash('admin123', PASSWORD_BCRYPT),
            'role'          => 'admin',
            'status'        => 'active',
            'is_verified'   => 1,
            'api_token'     => bin2hex(random_bytes(32)),
            'created_at'    => $now,
            'updated_at'    => $now,
        ]);
        $adminId = (int) $db->insertID();

        $db->table('wallets')->insert([
            'user_id'    => $adminId,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        $categories = [
            ['name' => 'Consumer Electronics', 'slug' => 'consumer-electronics', 'description' => 'Mobiles, laptops, audio, accessories', 'icon' => 'bi-phone'],
            ['name' => 'Fashion & Apparel', 'slug' => 'fashion-apparel', 'description' => 'Men, women, kids clothing', 'icon' => 'bi-handbag'],
            ['name' => 'Groceries & Essentials', 'slug' => 'groceries-essentials', 'description' => 'Daily household needs', 'icon' => 'bi-basket2'],
            ['name' => 'Home & Living', 'slug' => 'home-living', 'description' => 'Furniture, decor, kitchen', 'icon' => 'bi-lamp'],
            ['name' => 'Beauty & Personal Care', 'slug' => 'beauty-personal-care', 'description' => 'Skincare, cosmetics, grooming', 'icon' => 'bi-heart-pulse'],
            ['name' => 'Sports & Outdoor', 'slug' => 'sports-outdoor', 'description' => 'Fitness and outdoor gear', 'icon' => 'bi-bicycle'],
        ];
        foreach ($categories as $i => $cat) {
            $db->table('categories')->insert([
                'parent_id'   => null,
                'name'        => $cat['name'],
                'slug'        => $cat['slug'],
                'description' => $cat['description'],
                'icon'        => $cat['icon'],
                'is_active'   => 1,
                'sort_order'  => $i,
                'created_at'  => $now,
                'updated_at'  => $now,
            ]);
        }

        $db->table('commissions')->insert([
            'name'       => 'Standard Platform Commission',
            'percentage' => 10.00,
            'is_active'  => 1,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        echo "Reset complete. Admin: admin@solqam.pk / admin123. Catalog is empty.\n";
    }
}
