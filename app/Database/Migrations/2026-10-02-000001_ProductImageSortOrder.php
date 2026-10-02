<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class ProductImageSortOrder extends Migration
{
    public function up()
    {
        if ($this->db->fieldExists('sort_order', 'product_images')) {
            return;
        }

        $this->forge->addColumn('product_images', [
            'sort_order' => [
                'type'       => 'INT',
                'constraint' => 11,
                'default'    => 0,
                'after'      => 'is_primary',
            ],
        ]);
    }

    public function down()
    {
        if ($this->db->fieldExists('sort_order', 'product_images')) {
            $this->forge->dropColumn('product_images', 'sort_order');
        }
    }
}
