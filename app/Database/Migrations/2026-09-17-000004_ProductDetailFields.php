<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class ProductDetailFields extends Migration
{
    public function up()
    {
        $this->forge->addColumn('products', [
            'highlights' => [
                'type' => 'TEXT',
                'null' => true,
                'after' => 'description',
            ],
            'specifications' => [
                'type' => 'TEXT',
                'null' => true,
                'after' => 'highlights',
            ],
            'size_guide' => [
                'type' => 'TEXT',
                'null' => true,
                'after' => 'specifications',
            ],
            'warranty_info' => [
                'type'       => 'VARCHAR',
                'constraint' => 255,
                'null'       => true,
                'after'      => 'size_guide',
            ],
            'return_days' => [
                'type'       => 'INT',
                'constraint' => 11,
                'default'    => 7,
                'after'      => 'warranty_info',
            ],
        ]);
    }

    public function down()
    {
        $this->forge->dropColumn('products', [
            'highlights',
            'specifications',
            'size_guide',
            'warranty_info',
            'return_days',
        ]);
    }
}
