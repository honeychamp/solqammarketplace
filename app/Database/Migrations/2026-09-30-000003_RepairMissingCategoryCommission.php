<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class RepairMissingCategoryCommission extends Migration
{
    public function up()
    {
        if (! $this->db->tableExists('categories')) {
            return;
        }

        if ($this->db->fieldExists('commission_percent', 'categories')) {
            return;
        }

        $this->forge->addColumn('categories', [
            'commission_percent' => [
                'type'       => 'DECIMAL',
                'constraint' => '5,2',
                'null'       => true,
                'default'    => 10.00,
                'after'      => 'icon',
            ],
        ]);

        $fallback = 10.00;
        try {
            $rule = $this->db->table('commissions')->where('is_active', 1)->get()->getRowArray();
            if ($rule && isset($rule['percentage'])) {
                $fallback = max(0, min(50, (float) $rule['percentage']));
            }
        } catch (\Throwable $e) {
        }

        $this->db->table('categories')->set('commission_percent', $fallback)->update();
    }

    public function down()
    {
    }
}
