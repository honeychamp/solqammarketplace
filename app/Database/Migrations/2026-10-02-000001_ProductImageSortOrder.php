<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class ProductImageSortOrder extends Migration
{
    public function up()
    {
        if (method_exists($this->db, 'resetDataCache')) {
            $this->db->resetDataCache();
        }
        if ($this->db->fieldExists('sort_order', 'product_images')) {
            return;
        }

        try {
            $this->forge->addColumn('product_images', [
                'sort_order' => [
                    'type'       => 'INT',
                    'constraint' => 11,
                    'default'    => 0,
                    'after'      => 'is_primary',
                ],
            ]);
        } catch (\Throwable $e) {
            if (stripos($e->getMessage(), 'Duplicate column') !== false) {
                return;
            }

            throw $e;
        }
    }

    public function down()
    {
        if ($this->db->fieldExists('sort_order', 'product_images')) {
            $this->forge->dropColumn('product_images', 'sort_order');
        }
    }
}
