<?php

namespace App\Services\Platform;

/**
 * Adds missing marketplace columns on live DBs that were imported
 * from an older schema (checkout / inbound / cashback).
 */
class SchemaHeal
{
    public const VERSION = 18;

    public static function run(): void
    {
        static $ran = false;
        if ($ran) {
            return;
        }
        $ran = true;

        $marker = WRITEPATH . 'cache/schema_heal_v' . self::VERSION;
        if (is_file($marker)) {
            return;
        }

        try {
            $db    = \Config\Database::connect();
            $forge = \Config\Database::forge();

            self::addColumn($db, $forge, 'products', 'cashback_percent', [
                'type'       => 'DECIMAL',
                'constraint' => '5,2',
                'default'    => '0.00',
                'null'       => false,
            ]);
            self::addColumn($db, $forge, 'products', 'sold_count', [
                'type'       => 'INT',
                'constraint' => 11,
                'default'    => 0,
                'null'       => true,
            ]);
            self::addColumn($db, $forge, 'orders', 'cashback_amount', [
                'type'       => 'DECIMAL',
                'constraint' => '12,2',
                'default'    => '0.00',
                'null'       => false,
            ]);
            self::addColumn($db, $forge, 'order_items', 'cashback_percent', [
                'type'       => 'DECIMAL',
                'constraint' => '5,2',
                'default'    => '0.00',
                'null'       => false,
            ]);
            self::addColumn($db, $forge, 'order_items', 'cashback_amount', [
                'type'       => 'DECIMAL',
                'constraint' => '12,2',
                'default'    => '0.00',
                'null'       => false,
            ]);
            self::addColumn($db, $forge, 'order_items', 'fulfillment_status', [
                'type'       => 'VARCHAR',
                'constraint' => 30,
                'default'    => 'placed',
                'null'       => true,
            ]);
            if ($db->tableExists('shipments')) {
                self::addColumn($db, $forge, 'shipments', 'fulfill_by', [
                    'type'       => 'VARCHAR',
                    'constraint' => 20,
                    'default'    => 'seller',
                    'null'       => false,
                ]);
                self::addColumn($db, $forge, 'shipments', 'handoff_status', [
                    'type'       => 'VARCHAR',
                    'constraint' => 40,
                    'null'       => true,
                ]);
            }
            self::addColumn($db, $forge, 'orders', 'delivery_arrears', [
                'type'       => 'DECIMAL',
                'constraint' => '10,2',
                'default'    => '0.00',
                'null'       => false,
            ]);
            try {
                $db->query("ALTER TABLE `orders` MODIFY `status` ENUM('placed','confirmed','shipped','delivered','cancelled','returned','undelivered') NOT NULL DEFAULT 'placed'");
            } catch (\Throwable $e) {
                // status may already be VARCHAR or include undelivered
            }
            self::addColumn($db, $forge, 'orders', 'pay_later_wallet', [
                'type'       => 'DECIMAL',
                'constraint' => '10,2',
                'default'    => '0.00',
                'null'       => false,
            ]);
            self::addColumn($db, $forge, 'orders', 'pay_later_cleared', [
                'type'       => 'TINYINT',
                'constraint' => 1,
                'default'    => 0,
                'null'       => false,
            ]);
            self::addColumn($db, $forge, 'categories', 'parent_id', [
                'type'       => 'INT',
                'constraint' => 11,
                'unsigned'   => true,
                'null'       => true,
            ]);
            self::addColumn($db, $forge, 'categories', 'sort_order', [
                'type'       => 'INT',
                'constraint' => 11,
                'default'    => 0,
                'null'       => false,
            ]);
            self::addColumn($db, $forge, 'categories', 'icon', [
                'type'       => 'VARCHAR',
                'constraint' => 80,
                'null'       => true,
            ]);
            self::addColumn($db, $forge, 'categories', 'commission_percent', [
                'type'       => 'DECIMAL',
                'constraint' => '5,2',
                'null'       => true,
            ]);
            self::addColumn($db, $forge, 'categories', 'image', [
                'type'       => 'VARCHAR',
                'constraint' => 255,
                'null'       => true,
            ]);
            self::addColumn($db, $forge, 'categories', 'is_active', [
                'type'       => 'TINYINT',
                'constraint' => 1,
                'default'    => 1,
                'null'       => false,
            ]);
            self::addColumn($db, $forge, 'categories', 'created_at', [
                'type' => 'DATETIME',
                'null' => true,
            ]);
            self::addColumn($db, $forge, 'categories', 'updated_at', [
                'type' => 'DATETIME',
                'null' => true,
            ]);
            if ($db->tableExists('product_images')) {
                self::addColumn($db, $forge, 'product_images', 'sort_order', [
                    'type'       => 'INT',
                    'constraint' => 11,
                    'default'    => 0,
                    'null'       => false,
                ]);
                self::addColumn($db, $forge, 'product_images', 'media_type', [
                    'type'       => 'VARCHAR',
                    'constraint' => 20,
                    'default'    => 'image',
                    'null'       => true,
                ]);
            }
            if ($db->tableExists('product_variants')) {
                self::addColumn($db, $forge, 'product_variants', 'image', [
                    'type'       => 'VARCHAR',
                    'constraint' => 255,
                    'null'       => true,
                ]);
            }
            if ($db->tableExists('users')) {
                self::addColumn($db, $forge, 'users', 'google_id', [
                    'type'       => 'VARCHAR',
                    'constraint' => 80,
                    'null'       => true,
                ]);
                self::addColumn($db, $forge, 'users', 'facebook_id', [
                    'type'       => 'VARCHAR',
                    'constraint' => 80,
                    'null'       => true,
                ]);
                self::addColumn($db, $forge, 'users', 'oauth_provider', [
                    'type'       => 'VARCHAR',
                    'constraint' => 20,
                    'null'       => true,
                ]);
                self::addColumn($db, $forge, 'users', 'avatar_url', [
                    'type'       => 'VARCHAR',
                    'constraint' => 255,
                    'null'       => true,
                ]);
                try {
                    $db->query('ALTER TABLE `users` MODIFY `phone` VARCHAR(30) NULL');
                } catch (\Throwable $e) {
                    // already nullable
                }
            }

            self::ensureAdminMallStores($db);
            self::healBannersTable($db, $forge);
            self::ensureSpeedIndexes($db);
            self::ensureMarketingTables($db, $forge);
            self::widenMarketingSecrets($db);
            self::ensureSellerCodDues($db, $forge);
            self::addColumn($db, $forge, 'seller_cod_dues', 'paid_amount', [
                'type'       => 'DECIMAL',
                'constraint' => '12,2',
                'default'    => '0.00',
                'null'       => false,
            ]);

            if (! is_dir(WRITEPATH . 'cache')) {
                @mkdir(WRITEPATH . 'cache', 0755, true);
            }
            @file_put_contents($marker, (string) time());
        } catch (\Throwable $e) {
            log_message('error', 'SchemaHeal: ' . $e->getMessage());
        }
    }

    protected static function ensureMarketingTables($db, $forge): void
    {
        if (! $db->tableExists('marketing_settings')) {
            $forge->addField([
                'id' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true],
                'enabled' => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 0],
                'test_mode' => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 0],
                'consent_required' => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 0],
                'server_pageview' => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 1],
                'currency' => ['type' => 'VARCHAR', 'constraint' => 8, 'default' => 'PKR'],
                'meta_pixel_id' => ['type' => 'VARCHAR', 'constraint' => 40, 'null' => true],
                'meta_capi_token' => ['type' => 'TEXT', 'null' => true],
                'meta_test_event_code' => ['type' => 'VARCHAR', 'constraint' => 80, 'null' => true],
                'tiktok_pixel_id' => ['type' => 'VARCHAR', 'constraint' => 80, 'null' => true],
                'tiktok_api_token' => ['type' => 'TEXT', 'null' => true],
                'tiktok_app_id' => ['type' => 'VARCHAR', 'constraint' => 80, 'null' => true],
                'gtm_id' => ['type' => 'VARCHAR', 'constraint' => 40, 'null' => true],
                'ga4_id' => ['type' => 'VARCHAR', 'constraint' => 40, 'null' => true],
                'google_ads_id' => ['type' => 'VARCHAR', 'constraint' => 40, 'null' => true],
                'google_ads_label' => ['type' => 'VARCHAR', 'constraint' => 80, 'null' => true],
                'created_at' => ['type' => 'DATETIME', 'null' => true],
                'updated_at' => ['type' => 'DATETIME', 'null' => true],
            ]);
            $forge->addKey('id', true);
            $forge->createTable('marketing_settings', true);
        }

        if (! $db->tableExists('marketing_event_logs')) {
            $forge->addField([
                'id' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true],
                'event_id' => ['type' => 'VARCHAR', 'constraint' => 80, 'null' => true],
                'platform' => ['type' => 'VARCHAR', 'constraint' => 30],
                'event_name' => ['type' => 'VARCHAR', 'constraint' => 80],
                'payload' => ['type' => 'LONGTEXT', 'null' => true],
                'response' => ['type' => 'LONGTEXT', 'null' => true],
                'http_code' => ['type' => 'INT', 'constraint' => 11, 'null' => true],
                'status' => ['type' => 'VARCHAR', 'constraint' => 20, 'default' => 'sent'],
                'created_at' => ['type' => 'DATETIME', 'null' => true],
            ]);
            $forge->addKey('id', true);
            $forge->addKey('event_id');
            $forge->addKey('platform');
            $forge->addKey('status');
            $forge->createTable('marketing_event_logs', true);
        }

        self::addColumn($db, $forge, 'orders', 'marketing_event_id', [
            'type'       => 'VARCHAR',
            'constraint' => 80,
            'null'       => true,
        ]);
    }

    protected static function widenMarketingSecrets($db): void
    {
        if (! $db->tableExists('marketing_settings')) {
            return;
        }
        foreach (['meta_capi_token', 'tiktok_api_token'] as $col) {
            try {
                $db->query('ALTER TABLE `marketing_settings` MODIFY `' . $col . '` TEXT NULL');
            } catch (\Throwable $e) {
                log_message('error', 'SchemaHeal widen ' . $col . ': ' . $e->getMessage());
            }
        }
        try {
            $db->query('ALTER TABLE `marketing_settings` MODIFY `tiktok_pixel_id` VARCHAR(120) NULL');
        } catch (\Throwable $e) {
            // already wide enough
        }
    }

    protected static function ensureSellerCodDues($db, $forge): void
    {
        if ($db->tableExists('seller_cod_dues')) {
            return;
        }
        $forge->addField([
            'id' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true],
            'seller_id' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true],
            'order_id' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true],
            'shipment_id' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => true],
            'goods_amount' => ['type' => 'DECIMAL', 'constraint' => '12,2', 'default' => '0.00'],
            'shipping_amount' => ['type' => 'DECIMAL', 'constraint' => '12,2', 'default' => '0.00'],
            'commission_amount' => ['type' => 'DECIMAL', 'constraint' => '12,2', 'default' => '0.00'],
            'cashback_amount' => ['type' => 'DECIMAL', 'constraint' => '12,2', 'default' => '0.00'],
            'due_amount' => ['type' => 'DECIMAL', 'constraint' => '12,2', 'default' => '0.00'],
            'paid_amount' => ['type' => 'DECIMAL', 'constraint' => '12,2', 'default' => '0.00'],
            'collected_cash' => ['type' => 'DECIMAL', 'constraint' => '12,2', 'default' => '0.00'],
            'payment_method' => ['type' => 'VARCHAR', 'constraint' => 30, 'null' => true],
            'fulfill_by' => ['type' => 'VARCHAR', 'constraint' => 20, 'default' => 'seller'],
            'status' => ['type' => 'VARCHAR', 'constraint' => 20, 'default' => 'unpaid'],
            'paid_at' => ['type' => 'DATETIME', 'null' => true],
            'paid_note' => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
            'created_at' => ['type' => 'DATETIME', 'null' => true],
            'updated_at' => ['type' => 'DATETIME', 'null' => true],
        ]);
        $forge->addKey('id', true);
        $forge->addKey('seller_id');
        $forge->addKey('order_id');
        $forge->addKey('status');
        $forge->createTable('seller_cod_dues', true);
    }

    protected static function addColumn($db, $forge, string $table, string $column, array $def): void
    {
        try {
            if (! $db->tableExists($table) || $db->fieldExists($column, $table)) {
                return;
            }
            $forge->addColumn($table, [$column => $def]);
        } catch (\Throwable $e) {
            log_message('error', "SchemaHeal {$table}.{$column}: " . $e->getMessage());
        }
    }

    protected static function healBannersTable($db, $forge): void
    {
        if (! $db->tableExists('banners')) {
            return;
        }
        self::addColumn($db, $forge, 'banners', 'badge_text', [
            'type'       => 'VARCHAR',
            'constraint' => 80,
            'null'       => true,
        ]);
        self::addColumn($db, $forge, 'banners', 'button_text', [
            'type'       => 'VARCHAR',
            'constraint' => 80,
            'null'       => true,
        ]);
        self::addColumn($db, $forge, 'banners', 'starts_at', [
            'type' => 'DATETIME',
            'null' => true,
        ]);
        self::addColumn($db, $forge, 'banners', 'ends_at', [
            'type' => 'DATETIME',
            'null' => true,
        ]);
        try {
            $db->query("UPDATE `banners` SET `starts_at` = NULL WHERE `starts_at` IS NOT NULL AND (`starts_at` = '0000-00-00 00:00:00' OR `starts_at` = '0000-00-00')");
            $db->query("UPDATE `banners` SET `ends_at` = NULL WHERE `ends_at` IS NOT NULL AND (`ends_at` = '0000-00-00 00:00:00' OR `ends_at` = '0000-00-00')");
        } catch (\Throwable $e) {
            log_message('error', 'SchemaHeal banners dates: ' . $e->getMessage());
        }
    }

    /**
     * Admin first-party products use users.id as seller_id. Without a
     * seller_profiles row, /store/{adminId} 404s on Visit Store.
     */
    protected static function ensureAdminMallStores($db): void
    {
        try {
            if (! $db->tableExists('seller_profiles') || ! $db->tableExists('users')) {
                return;
            }
            $admins = $db->table('users')->select('id, name')->where('role', 'admin')->get()->getResultArray();
            foreach ($admins as $admin) {
                $uid = (int) ($admin['id'] ?? 0);
                if ($uid < 1) {
                    continue;
                }
                $exists = $db->table('seller_profiles')->where('user_id', $uid)->countAllResults();
                if ($exists > 0) {
                    $db->table('seller_profiles')->where('user_id', $uid)->update([
                        'approval_status' => 'approved',
                    ]);
                    continue;
                }
                $now = date('Y-m-d H:i:s');
                $row = [
                    'user_id'          => $uid,
                    'store_name'       => 'Solqam Mall',
                    'business_name'    => $admin['name'] ?? 'Solqam',
                    'approval_status'  => 'approved',
                    'created_at'       => $now,
                    'updated_at'       => $now,
                ];
                $fields = $db->getFieldNames('seller_profiles');
                $insert = [];
                foreach ($row as $k => $v) {
                    if (in_array($k, $fields, true)) {
                        $insert[$k] = $v;
                    }
                }
                if ($insert !== []) {
                    $db->table('seller_profiles')->insert($insert);
                }
            }
        } catch (\Throwable $e) {
            log_message('error', 'SchemaHeal mall store: ' . $e->getMessage());
        }
    }

    protected static function ensureSpeedIndexes($db): void
    {
        $deleted = false;
        try {
            $deleted = $db->fieldExists('deleted_at', 'products');
        } catch (\Throwable $e) {
            $deleted = false;
        }

        self::addIndex($db, 'products', 'idx_products_status_id', $deleted ? '`status`, `deleted_at`, `id`' : '`status`, `id`');
        self::addIndex($db, 'products', 'idx_products_category_status', '`category_id`, `status`');
        self::addIndex($db, 'products', 'idx_products_seller', '`seller_id`');
        self::addIndex($db, 'product_images', 'idx_product_images_lookup', '`product_id`, `is_primary`, `id`');
        self::addIndex($db, 'seller_profiles', 'idx_seller_profiles_approval', '`approval_status`');
        self::addIndex($db, 'categories', 'idx_categories_nav', '`is_active`, `parent_id`, `sort_order`');
        self::addIndex($db, 'cart_items', 'idx_cart_items_cart', '`cart_id`');
        self::addIndex($db, 'wallet_transactions', 'idx_wallet_tx_wallet', '`wallet_id`');
        self::addIndex($db, 'orders', 'idx_orders_user_status', '`user_id`, `status`');
    }

    protected static function addIndex($db, string $table, string $name, string $columnsSql): void
    {
        try {
            if (! $db->tableExists($table)) {
                return;
            }
            $exists = $db->query('SHOW INDEX FROM `' . $table . '` WHERE Key_name = ' . $db->escape($name))->getResult();
            if ($exists !== []) {
                return;
            }
            $db->query('ALTER TABLE `' . $table . '` ADD INDEX `' . $name . '` (' . $columnsSql . ')');
        } catch (\Throwable $e) {
            // duplicate / missing column
        }
    }
}
