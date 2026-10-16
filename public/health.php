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

$root = dirname(__DIR__);

echo PHP_EOL . 'PHP syntax (all app/*.php):' . PHP_EOL;
$parseHits = 0;
$checked   = 0;
$appDir    = $root . DIRECTORY_SEPARATOR . 'app';
if (is_dir($appDir)) {
    $rii = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($appDir, FilesystemIterator::SKIP_DOTS));
    foreach ($rii as $file) {
        if (! $file->isFile() || strtolower($file->getExtension()) !== 'php') {
            continue;
        }
        $checked++;
        $path = $file->getPathname();
        $rel  = str_replace('\\', '/', substr($path, strlen($root) + 1));
        $src  = @file_get_contents($path);
        if (! is_string($src) || trim($src) === '') {
            echo 'EMPTY  ' . $rel . PHP_EOL;
            $ok = false;
            $parseHits++;
            continue;
        }
        try {
            token_get_all($src, TOKEN_PARSE);
        } catch (\ParseError $e) {
            $ok = false;
            $parseHits++;
            echo 'BROKEN ' . $rel . ' line ' . $e->getLine() . ': ' . $e->getMessage() . PHP_EOL;
        }
    }
}
echo 'Checked ' . $checked . ' PHP files. Broken: ' . $parseHits . PHP_EOL;

$cacheDir = $writable . DIRECTORY_SEPARATOR . 'cache';
$cleared  = 0;
if (is_dir($cacheDir)) {
    foreach ((glob($cacheDir . DIRECTORY_SEPARATOR . '*') ?: []) as $cf) {
        if (! is_file($cf)) {
            continue;
        }
        $bn = basename($cf);
        if (str_starts_with($bn, 'schema_heal') || $bn === 'index.html' || $bn === '.htaccess') {
            continue;
        }
        if (@unlink($cf)) {
            $cleared++;
        }
    }
}
echo 'Cleared writable/cache files: ' . $cleared . PHP_EOL;

$logDir = $writable . DIRECTORY_SEPARATOR . 'logs';
$logs   = is_dir($logDir) ? glob($logDir . DIRECTORY_SEPARATOR . 'log-*.log') : [];
if ($logs) {
    rsort($logs);
    $latest = $logs[0];
    echo PHP_EOL . 'Latest log: ' . basename($latest) . PHP_EOL;
    $lines = @file($latest) ?: [];
    $lastCrit = -1;
    foreach ($lines as $i => $line) {
        if (stripos($line, 'ParseError') !== false || stripos($line, 'CRITICAL') !== false) {
            $lastCrit = $i;
        }
    }
    if ($lastCrit >= 0) {
        $slice = array_slice($lines, $lastCrit, 12);
        foreach ($slice as $line) {
            $line = preg_replace('/password[^\s]*/i', 'password=***', trim($line)) ?? trim($line);
            if ($line !== '') {
                echo $line . PHP_EOL;
            }
        }
    } else {
        echo 'No CRITICAL/ParseError in latest log.' . PHP_EOL;
    }
} else {
    echo PHP_EOL . 'No writable/logs/*.log yet.' . PHP_EOL;
}

echo PHP_EOL . ($ok ? 'READY' : 'FIX THE MISS/FAIL LINES') . PHP_EOL;
echo 'PHP Selector: pick 8.2 or 8.3, enable the extensions above, save, then wait 1–2 minutes.' . PHP_EOL;
