<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AdvanceMarketplace extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id'         => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
            'user_id'    => ['type' => 'INT', 'unsigned' => true, 'null' => true],
            'action'     => ['type' => 'VARCHAR', 'constraint' => 80],
            'entity'     => ['type' => 'VARCHAR', 'constraint' => 80, 'null' => true],
            'entity_id'  => ['type' => 'INT', 'unsigned' => true, 'null' => true],
            'meta'       => ['type' => 'TEXT', 'null' => true],
            'ip_address' => ['type' => 'VARCHAR', 'constraint' => 45, 'null' => true],
            'created_at' => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addKey('action');
        $this->forge->createTable('audit_logs', true);

        $this->forge->addField([
            'id'         => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
            'ip_address' => ['type' => 'VARCHAR', 'constraint' => 45],
            'action'     => ['type' => 'VARCHAR', 'constraint' => 40],
            'hits'       => ['type' => 'INT', 'unsigned' => true, 'default' => 1],
            'window_at'  => ['type' => 'DATETIME'],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addKey(['ip_address', 'action']);
        $this->forge->createTable('auth_attempts', true);

        $this->forge->addField([
            'setting_key'   => ['type' => 'VARCHAR', 'constraint' => 80],
            'setting_value' => ['type' => 'VARCHAR', 'constraint' => 255],
            'updated_at'    => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('setting_key', true);
        $this->forge->createTable('platform_settings', true);

        if ($this->db->fieldExists('photo_path', 'returns_refunds') === false) {
            $this->forge->addColumn('returns_refunds', [
                'photo_path' => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true, 'after' => 'customer_note'],
            ]);
        }

        if ($this->db->tableExists('banners') && ! $this->db->fieldExists('starts_at', 'banners')) {
            $this->forge->addColumn('banners', [
                'starts_at' => ['type' => 'DATETIME', 'null' => true],
                'ends_at'   => ['type' => 'DATETIME', 'null' => true],
            ]);
        }

        $now = date('Y-m-d H:i:s');
        $defaults = [
            'sla_confirm_hours' => '24',
            'sla_ship_hours'    => '72',
            'cod_max_open'      => '5',
            'auth_max_hits'     => '8',
        ];
        foreach ($defaults as $k => $v) {
            $this->db->table('platform_settings')->ignore(true)->insert([
                'setting_key'   => $k,
                'setting_value' => $v,
                'updated_at'    => $now,
            ]);
        }
    }

    public function down()
    {
        $this->forge->dropTable('audit_logs', true);
        $this->forge->dropTable('auth_attempts', true);
        $this->forge->dropTable('platform_settings', true);
    }
}
