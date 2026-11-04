<?php
$dir = 'c:/xampp/htdocs/solqamtech/writable/user_export_xlsb/xl/';
foreach (['sharedStrings.bin', 'styles.bin', 'worksheets/sheet1.bin'] as $f) {
    $p = $dir . $f;
    $b = file_get_contents($p);
    echo "==== $f len=" . strlen($b) . " ====\n";
    preg_match_all('/[\x20-\x7e]{3,}/', $b, $m);
    $u = array_unique($m[0]);
    echo 'ascii ' . count($u) . "\n";
    echo implode("\n", array_slice($u, 0, 80)) . "\n";
    // utf16le strings
    $out = [];
    $len = strlen($b);
    $cur = '';
    for ($i = 0; $i + 1 < $len; $i += 2) {
        $c = ord($b[$i]);
        $h = ord($b[$i + 1]);
        if ($h === 0 && $c >= 32 && $c < 127) {
            $cur .= chr($c);
        } else {
            if (strlen($cur) >= 3) {
                $out[] = $cur;
            }
            $cur = '';
        }
    }
    if (strlen($cur) >= 3) {
        $out[] = $cur;
    }
    $out = array_values(array_unique($out));
    echo 'utf16 ' . count($out) . "\n";
    echo implode("\n", array_slice($out, 0, 100)) . "\n\n";
}
