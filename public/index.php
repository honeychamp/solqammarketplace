<?php

use CodeIgniter\Boot;
use Config\Paths;

/*
 *---------------------------------------------------------------
 * CHECK PHP VERSION
 *---------------------------------------------------------------
 */

$minPhpVersion = '8.2'; // If you update this, don't forget to update `spark`.
if (version_compare(PHP_VERSION, $minPhpVersion, '<')) {
    $message = sprintf(
        'Your PHP version must be %s or higher to run CodeIgniter. Current version: %s',
        $minPhpVersion,
        PHP_VERSION,
    );

    header('HTTP/1.1 503 Service Unavailable.', true, 503);
    echo $message;

    exit(1);
}

/*
 *---------------------------------------------------------------
 * SET THE CURRENT DIRECTORY
 *---------------------------------------------------------------
 */

// Path to the front controller (this file)
define('FCPATH', __DIR__ . DIRECTORY_SEPARATOR);

// Ensure the current directory is pointing to the front controller's directory
if (getcwd() . DIRECTORY_SEPARATOR !== FCPATH) {
    chdir(FCPATH);
}

/*
 *---------------------------------------------------------------
 * BOOTSTRAP THE APPLICATION
 *---------------------------------------------------------------
 * This process sets up the path constants, loads and registers
 * our autoloader, along with Composer's, loads our constants
 * and fires up an environment-specific bootstrapping.
 */

// LOAD OUR PATHS CONFIG FILE
// This is the line that might need to be changed, depending on your folder structure.
require FCPATH . '../app/Config/Paths.php';
// ^^^ Change this line if you move your application folder

$paths = new Paths();

// LOAD THE FRAMEWORK BOOTSTRAP FILE
require $paths->systemDirectory . '/Boot.php';

/*
 * Live .env sometimes has an empty, quoted, or Windows-CRLF CI_ENVIRONMENT
 * ("prod", "live", "production\r"). CI then cannot load
 * app/Config/Boot/{ENVIRONMENT}.php and shows:
 * "The application environment is not set correctly."
 */
$raw  = $_ENV['CI_ENVIRONMENT'] ?? $_SERVER['CI_ENVIRONMENT'] ?? getenv('CI_ENVIRONMENT') ?: 'production';
$env  = strtolower(trim((string) $raw, " \t\n\r\0\x0B\"'"));
$host = strtolower((string) preg_replace('/:\\d+$/', '', (string) ($_SERVER['HTTP_HOST'] ?? '')));
if (in_array($host, ['solqam.com', 'www.solqam.com'], true) || ! in_array($env, ['production', 'development', 'testing'], true)) {
    $env = 'production';
}
putenv('CI_ENVIRONMENT=' . $env);
$_ENV['CI_ENVIRONMENT']    = $env;
$_SERVER['CI_ENVIRONMENT'] = $env;
if (! defined('ENVIRONMENT')) {
    define('ENVIRONMENT', $env);
}

exit(Boot::bootWeb($paths));
