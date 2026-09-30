<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class ShipmentFulfillBy extends Migration
{
    public function up()
    {
        $fields = $this->db->getFieldNames('shipments');
        if (! in_array('fulfill_by', $fields, true)) {
            $this->forge->addColumn('shipments', [
                'fulfill_by' => [
                    'type'       => 'VARCHAR',
                    'constraint' => 20,
                    'default'    => 'seller',
                    'after'      => 'seller_id',
                ],
            ]);
        }
        if (! in_array('handoff_status', $fields, true)) {
            $this->forge->addColumn('shipments', [
                'handoff_status' => [
                    'type'       => 'VARCHAR',
                    'constraint' => 30,
                    'null'       => true,
                    'after'      => 'fulfill_by',
                ],
            ]);
        }
    }

    public function down()
    {
        $fields = $this->db->getFieldNames('shipments');
        if (in_array('handoff_status', $fields, true)) {
            $this->forge->dropColumn('shipments', 'handoff_status');
        }
        if (in_array('fulfill_by', $fields, true)) {
            $this->forge->dropColumn('shipments', 'fulfill_by');
        }
    }
}
