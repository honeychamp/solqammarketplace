<?php

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;

/**
 * Wipe marketplace rows, then restore only the .env admin + default commission.
 */
class CleanResetSeeder extends Seeder
{
    public function run()
    {
        $db = \Config\Database::connect();
        $db->query('SET FOREIGN_KEY_CHECKS = 0');

        $skip = ['migrations'];
        foreach ($db->listTables() as $table) {
            $name = preg_replace('/^' . preg_quote($db->getPrefix(), '/') . '/', '', $table);
            if (in_array($name, $skip, true)) {
                continue;
            }
            $db->query('TRUNCATE TABLE `' . str_replace('`', '', $table) . '`');
            echo "  Truncated: {$name}\n";
        }

        $db->query('SET FOREIGN_KEY_CHECKS = 1');

        $this->call('DatabaseSeeder');

        echo "Reset complete. Only admin from .env remains.\n";
    }
}
