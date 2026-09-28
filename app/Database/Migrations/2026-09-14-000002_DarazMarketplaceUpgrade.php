<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class DarazMarketplaceUpgrade extends Migration
{
    public function up()
    {
        $this->forge->addColumn('categories', [
            'parent_id' => [
                'type'       => 'INT',
                'constraint' => 11,
                'unsigned'   => true,
                'null'       => true,
                'after'      => 'id',
            ],
            'sort_order' => [
                'type'       => 'INT',
                'constraint' => 11,
                'default'    => 0,
                'after'      => 'image',
            ],
            'icon' => [
                'type'       => 'VARCHAR',
                'constraint' => 80,
                'null'       => true,
                'after'      => 'sort_order',
            ],
        ]);

        $this->forge->addColumn('products', [
            'compare_at_price' => [
                'type'       => 'DECIMAL',
                'constraint' => '10,2',
                'null'       => true,
                'after'      => 'price',
            ],
            'brand' => [
                'type'       => 'VARCHAR',
                'constraint' => 120,
                'null'       => true,
                'after'      => 'sku',
            ],
            'sold_count' => [
                'type'       => 'INT',
                'constraint' => 11,
                'default'    => 0,
                'after'      => 'brand',
            ],
            'is_mall' => [
                'type'       => 'TINYINT',
                'constraint' => 1,
                'default'    => 0,
                'after'      => 'sold_count',
            ],
        ]);

        $this->forge->addColumn('cart_items', [
            'variant_id' => [
                'type'       => 'INT',
                'constraint' => 11,
                'unsigned'   => true,
                'null'       => true,
                'after'      => 'product_id',
            ],
        ]);

        $this->forge->addColumn('order_items', [
            'variant_id' => [
                'type'       => 'INT',
                'constraint' => 11,
                'unsigned'   => true,
                'null'       => true,
                'after'      => 'product_id',
            ],
            'variant_label' => [
                'type'       => 'VARCHAR',
                'constraint' => 150,
                'null'       => true,
                'after'      => 'product_name',
            ],
        ]);

        $this->forge->addColumn('orders', [
            'shipping_amount' => [
                'type'       => 'DECIMAL',
                'constraint' => '10,2',
                'default'    => '0.00',
                'after'      => 'discount_amount',
            ],
            'coupon_id' => [
                'type'       => 'INT',
                'constraint' => 11,
                'unsigned'   => true,
                'null'       => true,
                'after'      => 'shipping_amount',
            ],
            'coupon_code' => [
                'type'       => 'VARCHAR',
                'constraint' => 40,
                'null'       => true,
                'after'      => 'coupon_id',
            ],
            'coupon_discount' => [
                'type'       => 'DECIMAL',
                'constraint' => '10,2',
                'default'    => '0.00',
                'after'      => 'coupon_code',
            ],
            'tracking_number' => [
                'type'       => 'VARCHAR',
                'constraint' => 80,
                'null'       => true,
                'after'      => 'notes',
            ],
            'courier' => [
                'type'       => 'VARCHAR',
                'constraint' => 80,
                'null'       => true,
                'after'      => 'tracking_number',
            ],
        ]);

        $this->db->query("ALTER TABLE payments MODIFY payment_method VARCHAR(30) NOT NULL DEFAULT 'cod'");
        $this->db->query("ALTER TABLE wallet_transactions MODIFY reference_type VARCHAR(50) NOT NULL DEFAULT 'cashback'");

        $this->forge->addField([
            'id' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true],
            'product_id' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true],
            'sku' => ['type' => 'VARCHAR', 'constraint' => 100, 'null' => true],
            'color' => ['type' => 'VARCHAR', 'constraint' => 80, 'null' => true],
            'size' => ['type' => 'VARCHAR', 'constraint' => 40, 'null' => true],
            'price' => ['type' => 'DECIMAL', 'constraint' => '10,2', 'null' => true],
            'stock' => ['type' => 'INT', 'constraint' => 11, 'default' => 0],
            'created_at' => ['type' => 'DATETIME', 'null' => true],
            'updated_at' => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addForeignKey('product_id', 'products', 'id', 'CASCADE', 'CASCADE');
        $this->forge->createTable('product_variants', true);

        $this->forge->addField([
            'id' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true],
            'user_id' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true],
            'product_id' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true],
            'created_at' => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addUniqueKey(['user_id', 'product_id']);
        $this->forge->createTable('wishlists', true);

        $this->forge->addField([
            'id' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true],
            'code' => ['type' => 'VARCHAR', 'constraint' => 40],
            'type' => ['type' => 'ENUM', 'constraint' => ['percent', 'fixed'], 'default' => 'percent'],
            'value' => ['type' => 'DECIMAL', 'constraint' => '10,2', 'default' => '0.00'],
            'min_order' => ['type' => 'DECIMAL', 'constraint' => '10,2', 'default' => '0.00'],
            'max_uses' => ['type' => 'INT', 'constraint' => 11, 'null' => true],
            'used_count' => ['type' => 'INT', 'constraint' => 11, 'default' => 0],
            'starts_at' => ['type' => 'DATETIME', 'null' => true],
            'expires_at' => ['type' => 'DATETIME', 'null' => true],
            'is_active' => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 1],
            'created_at' => ['type' => 'DATETIME', 'null' => true],
            'updated_at' => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addUniqueKey('code');
        $this->forge->createTable('coupons', true);

        $this->forge->addField([
            'id' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true],
            'coupon_id' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true],
            'user_id' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true],
            'order_id' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => true],
            'created_at' => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->createTable('coupon_redemptions', true);

        $this->forge->addField([
            'id' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true],
            'title' => ['type' => 'VARCHAR', 'constraint' => 150],
            'starts_at' => ['type' => 'DATETIME'],
            'ends_at' => ['type' => 'DATETIME'],
            'is_active' => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 1],
            'created_at' => ['type' => 'DATETIME', 'null' => true],
            'updated_at' => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->createTable('flash_sales', true);

        $this->forge->addField([
            'id' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true],
            'flash_sale_id' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true],
            'product_id' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true],
            'sale_price' => ['type' => 'DECIMAL', 'constraint' => '10,2'],
            'created_at' => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->createTable('flash_sale_items', true);

        $this->forge->addField([
            'id' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true],
            'product_id' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true],
            'user_id' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true],
            'question' => ['type' => 'TEXT'],
            'answer' => ['type' => 'TEXT', 'null' => true],
            'answered_by' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => true],
            'created_at' => ['type' => 'DATETIME', 'null' => true],
            'answered_at' => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->createTable('product_questions', true);

        $this->forge->addField([
            'id' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true],
            'title' => ['type' => 'VARCHAR', 'constraint' => 180],
            'subtitle' => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
            'image_path' => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
            'link_url' => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
            'placement' => ['type' => 'VARCHAR', 'constraint' => 40, 'default' => 'hero'],
            'sort_order' => ['type' => 'INT', 'constraint' => 11, 'default' => 0],
            'is_active' => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 1],
            'created_at' => ['type' => 'DATETIME', 'null' => true],
            'updated_at' => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->createTable('banners', true);

        $this->forge->addField([
            'id' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true],
            'city' => ['type' => 'VARCHAR', 'constraint' => 100],
            'province' => ['type' => 'VARCHAR', 'constraint' => 100, 'null' => true],
            'rate' => ['type' => 'DECIMAL', 'constraint' => '10,2', 'default' => '199.00'],
            'free_above' => ['type' => 'DECIMAL', 'constraint' => '10,2', 'default' => '3000.00'],
            'eta_days' => ['type' => 'VARCHAR', 'constraint' => 40, 'default' => '2-4'],
            'created_at' => ['type' => 'DATETIME', 'null' => true],
            'updated_at' => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->createTable('shipping_zones', true);

        $this->forge->addField([
            'id' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true],
            'user_id' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true],
            'seller_id' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true],
            'created_at' => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addUniqueKey(['user_id', 'seller_id']);
        $this->forge->createTable('store_follows', true);

        $this->forge->addField([
            'id' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true],
            'seller_id' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true],
            'order_id' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => true],
            'amount' => ['type' => 'DECIMAL', 'constraint' => '10,2'],
            'status' => ['type' => 'ENUM', 'constraint' => ['pending', 'paid'], 'default' => 'pending'],
            'paid_at' => ['type' => 'DATETIME', 'null' => true],
            'created_at' => ['type' => 'DATETIME', 'null' => true],
            'updated_at' => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->createTable('seller_payouts', true);
    }

    public function down()
    {
        $this->forge->dropTable('seller_payouts', true);
        $this->forge->dropTable('store_follows', true);
        $this->forge->dropTable('shipping_zones', true);
        $this->forge->dropTable('banners', true);
        $this->forge->dropTable('product_questions', true);
        $this->forge->dropTable('flash_sale_items', true);
        $this->forge->dropTable('flash_sales', true);
        $this->forge->dropTable('coupon_redemptions', true);
        $this->forge->dropTable('coupons', true);
        $this->forge->dropTable('wishlists', true);
        $this->forge->dropTable('product_variants', true);
    }
}
