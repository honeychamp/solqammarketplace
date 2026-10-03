<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CategoryParentIndex extends Migration
{
    public function up()
    {
        if (! $this->db->tableExists('categories')) {
            return;
        }

        $keys = $this->db->getIndexData('categories');
        foreach ($keys as $key) {
            if (strcasecmp((string) $key->name, 'parent_id') === 0) {
                return;
            }
        }

        try {
            $this->db->query('ALTER TABLE categories ADD INDEX parent_id (parent_id)');
        } catch (\Throwable $e) {
            if (stripos($e->getMessage(), 'Duplicate') === false) {
                throw $e;
            }
        }
    }

    public function down()
    {
        try {
            $this->db->query('ALTER TABLE categories DROP INDEX parent_id');
        } catch (\Throwable $e) {
        }
    }
}
