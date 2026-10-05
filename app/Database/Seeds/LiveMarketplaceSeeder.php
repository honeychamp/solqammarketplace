<?php

namespace App\Database\Seeds;

use App\Services\Platform\SchemaHeal;
use CodeIgniter\Database\Seeder;

/**
 * Production-safe catalog bootstrap after migrate / migrate:refresh --seed.
 * Idempotent. Does not create fake buyers, sellers, or orders.
 */
class LiveMarketplaceSeeder extends Seeder
{
    public function run()
    {
        $db  = \Config\Database::connect();
        $now = date('Y-m-d H:i:s');

        $this->clearSchemaHealMarkers();
        SchemaHeal::run();

        $this->seedShipping($db, $now);
        $this->seedHeroBanner($db, $now);
        $this->seedMallProducts($db, $now);

        SchemaHeal::run();

        echo "Live marketplace seed complete (zones, banner, Solqam Mall products).\n";
    }

    protected function clearSchemaHealMarkers(): void
    {
        $dir = rtrim(WRITEPATH, '\\/') . DIRECTORY_SEPARATOR . 'cache';
        if (! is_dir($dir)) {
            return;
        }
        foreach (glob($dir . DIRECTORY_SEPARATOR . 'schema_heal_v*') ?: [] as $file) {
            @unlink($file);
        }
    }

    protected function seedShipping($db, string $now): void
    {
        if (! $db->tableExists('shipping_zones')) {
            return;
        }

        $zones = [
            ['city' => 'Lahore', 'province' => 'Punjab', 'rate' => 150, 'free_above' => 3000, 'eta_days' => '2-4'],
            ['city' => 'Karachi', 'province' => 'Sindh', 'rate' => 199, 'free_above' => 3500, 'eta_days' => '3-5'],
            ['city' => 'Islamabad', 'province' => 'Islamabad Capital Territory', 'rate' => 150, 'free_above' => 3000, 'eta_days' => '2-4'],
            ['city' => 'Rawalpindi', 'province' => 'Punjab', 'rate' => 150, 'free_above' => 3000, 'eta_days' => '2-4'],
            ['city' => 'Faisalabad', 'province' => 'Punjab', 'rate' => 180, 'free_above' => 3000, 'eta_days' => '3-5'],
            ['city' => 'Multan', 'province' => 'Punjab', 'rate' => 199, 'free_above' => 3500, 'eta_days' => '3-5'],
            ['city' => 'Peshawar', 'province' => 'Khyber Pakhtunkhwa', 'rate' => 220, 'free_above' => 4000, 'eta_days' => '4-6'],
            ['city' => 'Quetta', 'province' => 'Balochistan', 'rate' => 250, 'free_above' => 4000, 'eta_days' => '5-7'],
            ['city' => 'Gujranwala', 'province' => 'Punjab', 'rate' => 170, 'free_above' => 3000, 'eta_days' => '3-5'],
            ['city' => 'Sialkot', 'province' => 'Punjab', 'rate' => 170, 'free_above' => 3000, 'eta_days' => '3-5'],
        ];

        foreach ($zones as $z) {
            $exists = $db->table('shipping_zones')->where('city', $z['city'])->get()->getRowArray();
            if ($exists) {
                continue;
            }
            $z['created_at'] = $now;
            $z['updated_at'] = $now;
            $db->table('shipping_zones')->insert($z);
        }
    }

    protected function seedHeroBanner($db, string $now): void
    {
        if (! $db->tableExists('banners')) {
            return;
        }

        $exists = $db->table('banners')->where('placement', 'hero')->countAllResults();
        if ($exists > 0) {
            return;
        }

        $row = [
            'title'       => 'Shop genuine brands. Earn wallet cashback on every order.',
            'subtitle'    => 'Electronics, fashion and daily essentials from verified sellers — Cash on Delivery nationwide.',
            'link_url'    => '/shop',
            'placement'   => 'hero',
            'sort_order'  => 1,
            'is_active'   => 1,
            'created_at'  => $now,
            'updated_at'  => $now,
        ];
        $fields = $db->getFieldNames('banners');
        if (in_array('badge_text', $fields, true)) {
            $row['badge_text'] = 'Solqam Marketplace';
        }
        if (in_array('button_text', $fields, true)) {
            $row['button_text'] = 'Shop now';
        }

        $insert = [];
        foreach ($row as $k => $v) {
            if (in_array($k, $fields, true)) {
                $insert[$k] = $v;
            }
        }
        $db->table('banners')->insert($insert);
    }

    protected function seedMallProducts($db, string $now): void
    {
        if (! $db->tableExists('products') || ! $db->tableExists('users') || ! $db->tableExists('categories')) {
            return;
        }

        $admin = $db->table('users')->where('role', 'admin')->orderBy('id', 'ASC')->get()->getRowArray();
        if (! $admin) {
            return;
        }
        $adminId = (int) $admin['id'];

        $cat = $db->table('categories')->where('slug', 'earbuds')->get()->getRowArray()
            ?: $db->table('categories')->where('slug', 'electronics')->get()->getRowArray();
        if (! $cat) {
            return;
        }
        $catId = (int) $cat['id'];

        $products = [
            [
                'slug'             => 'solqam-mall-wireless-earbuds',
                'name'             => 'Solqam Mall Wireless Earbuds',
                'description'      => 'Solqam Mall wireless earbuds with a charging case. In-ear fit for calls and music. 7-day returns on Mall items.',
                'price'            => 2499.00,
                'stock'            => 50,
                'brand'            => 'Solqam Mall',
                'sku'              => 'MALL-EAR-001',
                'cashback_percent' => 5.00,
                'is_mall'          => 1,
            ],
            [
                'slug'             => 'solqam-mall-usb-c-cable',
                'name'             => 'Solqam Mall USB-C Cable',
                'description'      => 'Solqam Mall USB-C charging and data cable. Nylon braid, USB-C connector. 7-day returns on Mall items.',
                'price'            => 799.00,
                'stock'            => 80,
                'brand'            => 'Solqam Mall',
                'sku'              => 'MALL-CBL-001',
                'cashback_percent' => 3.00,
                'is_mall'          => 1,
            ],
        ];

        $fields = $db->getFieldNames('products');

        foreach ($products as $p) {
            $existing = $db->table('products')->where('slug', $p['slug'])->get()->getRowArray();
            if ($existing) {
                if (in_array('cashback_percent', $fields, true) && (float) $p['cashback_percent'] > 0) {
                    $db->table('products')->where('id', $existing['id'])->update([
                        'cashback_percent' => $p['cashback_percent'],
                        'updated_at'       => $now,
                    ]);
                }
                continue;
            }
            $row = [
                'seller_id'        => $adminId,
                'category_id'      => $catId,
                'name'             => $p['name'],
                'slug'             => $p['slug'],
                'description'      => $p['description'],
                'price'            => $p['price'],
                'stock'            => $p['stock'],
                'sku'              => $p['sku'],
                'brand'            => $p['brand'],
                'cashback_percent' => $p['cashback_percent'],
                'is_mall'          => $p['is_mall'],
                'status'           => 'active',
                'return_days'      => 7,
                'created_at'       => $now,
                'updated_at'       => $now,
            ];
            $insert = [];
            foreach ($row as $k => $v) {
                if (in_array($k, $fields, true)) {
                    $insert[$k] = $v;
                }
            }
            $db->table('products')->insert($insert);
            $newId = (int) $db->insertID();
            if ($newId > 0 && in_array('cashback_percent', $fields, true)) {
                $db->table('products')->where('id', $newId)->update([
                    'cashback_percent' => $p['cashback_percent'],
                ]);
            }
        }
    }
}
