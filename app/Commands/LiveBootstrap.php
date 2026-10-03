<?php

namespace App\Commands;

use App\Services\Platform\SchemaHeal;
use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;
use Config\Database;
use Throwable;

/**
 * Live / empty-server bootstrap: migrate + seed (admin, categories, zones, mall SKUs).
 *
 *   php spark live:bootstrap
 *   php spark live:bootstrap --wipe   (drops all tables then re-seeds — never on a DB with real orders)
 */
class LiveBootstrap extends BaseCommand
{
    protected $group       = 'Solqam';
    protected $name        = 'live:bootstrap';
    protected $description = 'Migrate schema and seed live catalog (admin, categories, shipping, mall products).';
    protected $usage       = 'live:bootstrap [--wipe]';
    protected $options     = [
        '--wipe' => 'Drop all tables via migrate:refresh then seed. Destroys data.',
    ];

    public function run(array $params)
    {
        $wipe = CLI::getOption('wipe') !== null || in_array('--wipe', $params, true);

        if ($wipe) {
            CLI::write('Wiping database (migrate:refresh -f) then seeding...', 'yellow');
            $this->call('migrate:refresh', ['f' => null]);
        } else {
            CLI::write('Running pending migrations...', 'green');
            $this->call('migrate');
        }

        try {
            Database::seeder()->call('DatabaseSeeder');
            CLI::write('DatabaseSeeder OK', 'green');
        } catch (Throwable $e) {
            CLI::error('Seed failed: ' . $e->getMessage());

            return EXIT_ERROR;
        }

        try {
            SchemaHeal::run();
            CLI::write('SchemaHeal OK', 'green');
        } catch (Throwable $e) {
            CLI::error('SchemaHeal: ' . $e->getMessage());
        }

        $db    = Database::connect();
        $admin = $db->table('users')->where('role', 'admin')->get()->getRowArray();
        $cats  = $db->tableExists('categories') ? $db->table('categories')->countAllResults() : 0;
        $zones = $db->tableExists('shipping_zones') ? $db->table('shipping_zones')->countAllResults() : 0;
        $skus  = $db->tableExists('products') ? $db->table('products')->countAllResults() : 0;

        CLI::newLine();
        CLI::write('=== Solqam live checklist ===', 'cyan');
        CLI::write('Admin user: ' . ($admin['email'] ?? '(missing)'));
        CLI::write("Categories: {$cats}  Shipping zones: {$zones}  Products: {$skus}");
        CLI::newLine();
        CLI::write('1. cPanel → Domains: document root = public_html (root .htaccess already rewrites into public/).');
        CLI::write('2. Live .env: CI_ENVIRONMENT=production, app.baseURL=https://solqam.com/, MySQL name/user/password from cPanel.');
        CLI::write('3. writable/ must be 775 (cache, logs, session, uploads).');
        CLI::write('4. On the server, after upload: php spark live:bootstrap');
        CLI::write('5. Open https://solqam.com/  then /login  then /admin  then /shop');
        CLI::write('6. cPanel MultiPHP: this app needs PHP 8.2+ (live was on 7.2 and will not boot).');
        CLI::write('7. Domain document root must be the project public/ folder (or public_html with CI4 files, not an empty listing).');
        CLI::write('8. After files + MySQL .env are on the server: php spark live:bootstrap');

        return EXIT_SUCCESS;
    }
}
