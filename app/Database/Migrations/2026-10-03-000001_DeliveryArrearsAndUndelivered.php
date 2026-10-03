<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class DeliveryArrearsAndUndelivered extends Migration
{
    public function up()
    {
        try {
            $this->db->query("ALTER TABLE `orders` MODIFY `status` ENUM('placed','confirmed','shipped','delivered','cancelled','returned','undelivered') NOT NULL DEFAULT 'placed'");
        } catch (\Throwable $e) {
            // Column may already include undelivered, or status is VARCHAR.
        }

        if ($this->db->tableExists('orders') && ! $this->db->fieldExists('delivery_arrears', 'orders')) {
            $this->forge->addColumn('orders', [
                'delivery_arrears' => [
                    'type'       => 'DECIMAL',
                    'constraint' => '10,2',
                    'default'    => '0.00',
                    'null'       => false,
                    'after'      => 'shipping_amount',
                ],
            ]);
        }
    }

    public function down()
    {
        if ($this->db->tableExists('orders') && $this->db->fieldExists('delivery_arrears', 'orders')) {
            $this->forge->dropColumn('orders', 'delivery_arrears');
        }
    }
}
