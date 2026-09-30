<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class SellerCampaignJoin extends Migration
{
    public function up()
    {
        $db = $this->db;
        $sales = $db->getFieldNames('flash_sales');
        if (! in_array('campaign_type', $sales, true)) {
            $this->forge->addColumn('flash_sales', [
                'campaign_type' => [
                    'type'       => 'VARCHAR',
                    'constraint' => 20,
                    'default'    => 'flash',
                    'after'      => 'title',
                ],
            ]);
        }
        if (! in_array('seller_join', $sales, true)) {
            $this->forge->addColumn('flash_sales', [
                'seller_join' => [
                    'type'       => 'TINYINT',
                    'constraint' => 1,
                    'default'    => 1,
                    'after'      => 'is_active',
                ],
            ]);
        }
        if (! in_array('rules_note', $sales, true)) {
            $this->forge->addColumn('flash_sales', [
                'rules_note' => [
                    'type' => 'TEXT',
                    'null' => true,
                    'after' => 'seller_join',
                ],
            ]);
        }

        $items = $db->getFieldNames('flash_sale_items');
        if (! in_array('seller_id', $items, true)) {
            $this->forge->addColumn('flash_sale_items', [
                'seller_id' => [
                    'type'       => 'INT',
                    'constraint' => 11,
                    'unsigned'   => true,
                    'null'       => true,
                    'after'      => 'product_id',
                ],
            ]);
        }
        if (! in_array('status', $items, true)) {
            $this->forge->addColumn('flash_sale_items', [
                'status' => [
                    'type'       => 'VARCHAR',
                    'constraint' => 20,
                    'default'    => 'approved',
                    'after'      => 'sale_price',
                ],
            ]);
        }
        if (! in_array('source', $items, true)) {
            $this->forge->addColumn('flash_sale_items', [
                'source' => [
                    'type'       => 'VARCHAR',
                    'constraint' => 20,
                    'default'    => 'admin',
                    'after'      => 'status',
                ],
            ]);
        }
    }

    public function down()
    {
        $this->forge->dropColumn('flash_sales', ['campaign_type', 'seller_join', 'rules_note']);
        $this->forge->dropColumn('flash_sale_items', ['seller_id', 'status', 'source']);
    }
}
