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

        if (method_exists($this->db, 'resetDataCache')) {
            $this->db->resetDataCache();
        }

        if ($this->columnExists('categories', 'commission_percent')) {
            return;
        }

        try {
            $this->forge->addColumn('categories', [
                'commission_percent' => [
                    'type'       => 'DECIMAL',
                    'constraint' => '5,2',
                    'null'       => true,
                    'default'    => 10.00,
                    'after'      => 'icon',
                ],
            ]);
        } catch (\Throwable $e) {
            if (stripos($e->getMessage(), 'Duplicate column') !== false) {
                return;
            }

            throw $e;
        }

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

    protected function columnExists(string $table, string $column): bool
    {
        try {
            if ($this->db->fieldExists($column, $table)) {
                return true;
            }
        } catch (\Throwable $e) {
        }

        try {
            $fields = $this->db->getFieldNames($table) ?: [];
            foreach ($fields as $field) {
                if (strcasecmp((string) $field, $column) === 0) {
                    return true;
                }
            }
        } catch (\Throwable $e) {
        }

        return false;
    }

    public function down()
    {
    }
}
