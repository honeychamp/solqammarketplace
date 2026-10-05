<?php

namespace App\Services\Platform;

/**
 * Adds missing marketplace columns on live DBs that were imported
 * from an older schema (checkout / inbound / cashback).
 */
class SchemaHeal
{
    public const VERSION = 7;

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
            self::addColumn($db, $forge, 'categories', 'commission_percent', [
                'type'       => 'DECIMAL',
                'constraint' => '5,2',
                'null'       => true,
            ]);
            if ($db->tableExists('product_images')) {
                self::addColumn($db, $forge, 'product_images', 'sort_order', [
                    'type'       => 'INT',
                    'constraint' => 11,
                    'default'    => 0,
                    'null'       => false,
                ]);
            }

            self::ensureAdminMallStores($db);
            self::healBannersTable($db, $forge);

            if (! is_dir(WRITEPATH . 'cache')) {
                @mkdir(WRITEPATH . 'cache', 0755, true);
            }
            @file_put_contents($marker, (string) time());
        } catch (\Throwable $e) {
            log_message('error', 'SchemaHeal: ' . $e->getMessage());
        }
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
}
