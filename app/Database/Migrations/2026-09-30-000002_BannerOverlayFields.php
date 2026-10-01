<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class BannerOverlayFields extends Migration
{
    public function up()
    {
        if (! $this->db->tableExists('banners')) {
            return;
        }

        if (! $this->db->fieldExists('badge_text', 'banners')) {
            $this->forge->addColumn('banners', [
                'badge_text' => ['type' => 'VARCHAR', 'constraint' => 80, 'null' => true, 'after' => 'subtitle'],
            ]);
        }

        if (! $this->db->fieldExists('button_text', 'banners')) {
            $this->forge->addColumn('banners', [
                'button_text' => ['type' => 'VARCHAR', 'constraint' => 80, 'null' => true, 'after' => 'badge_text'],
            ]);
        }
    }

    public function down()
    {
        if ($this->db->tableExists('banners') && $this->db->fieldExists('badge_text', 'banners')) {
            $this->forge->dropColumn('banners', 'badge_text');
        }
        if ($this->db->tableExists('banners') && $this->db->fieldExists('button_text', 'banners')) {
            $this->forge->dropColumn('banners', 'button_text');
        }
    }
}
