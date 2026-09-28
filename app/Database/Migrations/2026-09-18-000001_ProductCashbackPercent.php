<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class ProductCashbackPercent extends Migration
{
    public function up()
    {
        if (! $this->db->fieldExists('cashback_percent', 'products')) {
            $this->forge->addColumn('products', [
                'cashback_percent' => [
                    'type'       => 'DECIMAL',
                    'constraint' => '5,2',
                    'default'    => 0.00,
                    'after'      => 'brand',
                ],
            ]);
        }

        if (! $this->db->fieldExists('cashback_percent', 'order_items')) {
            $this->forge->addColumn('order_items', [
                'cashback_percent' => [
                    'type'       => 'DECIMAL',
                    'constraint' => '5,2',
                    'default'    => 0.00,
                    'after'      => 'commission_amount',
                ],
            ]);
        }
    }

    public function down()
    {
        if ($this->db->fieldExists('cashback_percent', 'products')) {
            $this->forge->dropColumn('products', 'cashback_percent');
        }
        if ($this->db->fieldExists('cashback_percent', 'order_items')) {
            $this->forge->dropColumn('order_items', 'cashback_percent');
        }
    }
}
