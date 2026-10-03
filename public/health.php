<?php

/**
 * Live host probe (no CI4). Delete this file after go-live.
 * Open: https://solqam.com/health.php
 */
header('Content-Type: text/plain; charset=UTF-8');
header('X-Robots-Tag: noindex');

$need = ['intl', 'mbstring', 'mysqli', 'json', 'curl', 'fileinfo', 'openssl'];
$ok   = true;

echo 'PHP ' . PHP_VERSION . PHP_EOL;
echo 'SAPI ' . PHP_SAPI . PHP_EOL;
echo 'zlib.output_compression=' . var_export(ini_get('zlib.output_compression'), true) . PHP_EOL;
echo PHP_EOL . 'Extensions:' . PHP_EOL;
foreach ($need as $ext) {
    $has = extension_loaded($ext);
    $ok  = $ok && $has;
    echo ($has ? 'OK  ' : 'MISS') . '  ' . $ext . PHP_EOL;
}

$writable = dirname(__DIR__) . DIRECTORY_SEPARATOR . 'writable';
$wOk      = is_dir($writable) && is_writable($writable);
$ok       = $ok && $wOk;
echo PHP_EOL . ($wOk ? 'OK  ' : 'FAIL') . '  writable/ is writable' . PHP_EOL;

$envFile = dirname(__DIR__) . DIRECTORY_SEPARATOR . '.env';
echo (is_file($envFile) ? 'OK  ' : 'FAIL') . '  .env present' . PHP_EOL;

if (is_file($envFile)) {
    $env = @file_get_contents($envFile) ?: '';
    $get = static function (string $key) use ($env): string {
        if (! preg_match('/^[ \\t]*' . preg_quote($key, '/') . '[ \\t]*=[ \\t]*(.*)$/m', $env, $m)) {
            return '';
        }
        $v = trim($m[1]);
        $v = trim($v, "\"'");

        return $v;
    };
    $host = $get('database.default.hostname') ?: 'localhost';
    $name = $get('database.default.database');
    $user = $get('database.default.username');
    $pass = $get('database.default.password');
    echo 'DB name set: ' . ($name !== '' ? 'yes' : 'NO') . PHP_EOL;
    echo 'DB user set: ' . ($user !== '' ? 'yes' : 'NO') . PHP_EOL;
    echo 'DB user looks local-root: ' . ($user === 'root' ? 'YES (live cPanel must use the MySQL user, not root)' : 'no') . PHP_EOL;

    if ($name !== '' && extension_loaded('mysqli')) {
        mysqli_report(MYSQLI_REPORT_OFF);
        $link = @mysqli_connect($host, $user, $pass, $name);
        if ($link) {
            echo 'OK  MySQL connect' . PHP_EOL;
            $tables = @mysqli_query($link, "SHOW TABLES LIKE 'users'");
            echo (($tables && mysqli_num_rows($tables) > 0) ? 'OK  ' : 'MISS') . '  users table (run php spark live:bootstrap)' . PHP_EOL;
            mysqli_close($link);
        } else {
            $ok = false;
            echo 'FAIL MySQL connect: ' . mysqli_connect_error() . PHP_EOL;
        }
    }
}

echo PHP_EOL . ($ok ? 'READY' : 'FIX THE MISS/FAIL LINES') . PHP_EOL;
echo 'PHP Selector: pick 8.2 or 8.3, enable the extensions above, save, then wait 1–2 minutes.' . PHP_EOL;
