<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class OrderCashbackAmount extends Migration
{
    public function up()
    {
        if (! $this->db->fieldExists('cashback_amount', 'orders')) {
            $this->forge->addColumn('orders', [
                'cashback_amount' => [
                    'type'       => 'DECIMAL',
                    'constraint' => '12,2',
                    'default'    => 0.00,
                    'after'      => 'commission_amount',
                ],
            ]);
        }

        if (! $this->db->fieldExists('cashback_amount', 'order_items')) {
            $this->forge->addColumn('order_items', [
                'cashback_amount' => [
                    'type'       => 'DECIMAL',
                    'constraint' => '12,2',
                    'default'    => 0.00,
                    'after'      => 'commission_amount',
                ],
            ]);
        }
    }

    public function down()
    {
        if ($this->db->fieldExists('cashback_amount', 'orders')) {
            $this->forge->dropColumn('orders', 'cashback_amount');
        }
        if ($this->db->fieldExists('cashback_amount', 'order_items')) {
            $this->forge->dropColumn('order_items', 'cashback_amount');
        }
    }
}
