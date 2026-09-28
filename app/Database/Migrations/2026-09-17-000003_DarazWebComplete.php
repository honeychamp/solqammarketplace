<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class DarazWebComplete extends Migration
{
    public function up()
    {
        $this->forge->addColumn('reviews', [
            'image_path' => [
                'type'       => 'VARCHAR',
                'constraint' => 255,
                'null'       => true,
                'after'      => 'comment',
            ],
        ]);

        $this->forge->addColumn('products', [
            'is_sponsored' => [
                'type'       => 'TINYINT',
                'constraint' => 1,
                'default'    => 0,
                'after'      => 'is_mall',
            ],
        ]);

        $this->forge->addColumn('order_items', [
            'fulfillment_status' => [
                'type'       => 'VARCHAR',
                'constraint' => 30,
                'default'    => 'placed',
                'after'      => 'commission_amount',
            ],
        ]);

        $this->forge->addField([
            'id' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true],
            'order_id' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true],
            'seller_id' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true],
            'status' => ['type' => 'VARCHAR', 'constraint' => 30, 'default' => 'placed'],
            'tracking_number' => ['type' => 'VARCHAR', 'constraint' => 80, 'null' => true],
            'courier' => ['type' => 'VARCHAR', 'constraint' => 80, 'null' => true],
            'shipping_amount' => ['type' => 'DECIMAL', 'constraint' => '10,2', 'default' => '0.00'],
            'created_at' => ['type' => 'DATETIME', 'null' => true],
            'updated_at' => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addKey(['order_id', 'seller_id']);
        $this->forge->createTable('shipments', true);

        $this->forge->addField([
            'id' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true],
            'customer_id' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true],
            'seller_id' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true],
            'product_id' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => true],
            'last_message_at' => ['type' => 'DATETIME', 'null' => true],
            'created_at' => ['type' => 'DATETIME', 'null' => true],
            'updated_at' => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->createTable('conversations', true);

        $this->forge->addField([
            'id' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true],
            'conversation_id' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true],
            'sender_id' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true],
            'body' => ['type' => 'TEXT'],
            'created_at' => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addKey('conversation_id');
        $this->forge->createTable('messages', true);

        $this->forge->addField([
            'id' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true],
            'user_id' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true],
            'subject' => ['type' => 'VARCHAR', 'constraint' => 180],
            'message' => ['type' => 'TEXT'],
            'status' => ['type' => 'ENUM', 'constraint' => ['open', 'replied', 'closed'], 'default' => 'open'],
            'created_at' => ['type' => 'DATETIME', 'null' => true],
            'updated_at' => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->createTable('support_tickets', true);

        $this->forge->addField([
            'id' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true],
            'ticket_id' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true],
            'user_id' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true],
            'message' => ['type' => 'TEXT'],
            'created_at' => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->createTable('ticket_replies', true);
    }

    public function down()
    {
        $this->forge->dropTable('ticket_replies', true);
        $this->forge->dropTable('support_tickets', true);
        $this->forge->dropTable('messages', true);
        $this->forge->dropTable('conversations', true);
        $this->forge->dropTable('shipments', true);
    }
}
