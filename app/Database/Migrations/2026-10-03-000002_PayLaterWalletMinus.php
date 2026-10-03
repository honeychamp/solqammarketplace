<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class PayLaterWalletMinus extends Migration
{
    public function up()
    {
        if (! $this->db->tableExists('orders')) {
            return;
        }
        if (! $this->db->fieldExists('pay_later_wallet', 'orders')) {
            $this->forge->addColumn('orders', [
                'pay_later_wallet' => [
                    'type'       => 'DECIMAL',
                    'constraint' => '10,2',
                    'default'    => '0.00',
                    'null'       => false,
                    'after'      => 'wallet_amount_used',
                ],
            ]);
        }
        if (! $this->db->fieldExists('pay_later_cleared', 'orders')) {
            $this->forge->addColumn('orders', [
                'pay_later_cleared' => [
                    'type'       => 'TINYINT',
                    'constraint' => 1,
                    'default'    => 0,
                    'null'       => false,
                    'after'      => 'pay_later_wallet',
                ],
            ]);
        }
    }

    public function down()
    {
        if ($this->db->tableExists('orders') && $this->db->fieldExists('pay_later_cleared', 'orders')) {
            $this->forge->dropColumn('orders', 'pay_later_cleared');
        }
        if ($this->db->tableExists('orders') && $this->db->fieldExists('pay_later_wallet', 'orders')) {
            $this->forge->dropColumn('orders', 'pay_later_wallet');
        }
    }
}
