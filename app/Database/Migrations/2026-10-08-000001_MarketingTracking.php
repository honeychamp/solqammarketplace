<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class MarketingTracking extends Migration
{
    public function up()
    {
        \App\Services\Platform\SchemaHeal::run();
    }

    public function down()
    {
        $this->forge->dropTable('marketing_event_logs', true);
        $this->forge->dropTable('marketing_settings', true);
    }
}
