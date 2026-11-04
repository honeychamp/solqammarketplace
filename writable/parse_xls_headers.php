<?php
$p = 'c:/Users/hp/OneDrive/Pictures/Solqam_Full_Orders_20261102_140335.xls';
$s = file_get_contents($p);
echo "len=" . strlen($s) . PHP_EOL;
if (preg_match_all('/<th[^>]*>(.*?)<\/th>/si', $s, $m)) {
    echo "TH count=" . count($m[1]) . PHP_EOL;
    foreach ($m[1] as $i => $h) {
        echo $i . "\t" . trim(html_entity_decode(strip_tags($h))) . PHP_EOL;
        if ($i > 120) {
            break;
        }
    }
}
if (! preg_match('/<th/i', $s)) {
    echo "no th\n";
    echo substr($s, 0, 2500);
}
