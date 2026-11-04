<?php
$src = file_get_contents('c:/xampp/htdocs/solqamtech/app/Services/Export/OrderExportService.php');
preg_match("/public const HEADERS = \[(.*?)\];/s", $src, $m);
preg_match_all("/'([^']+)'/", $m[1], $h);
echo 'count=' . count($h[1]) . ' unique=' . count(array_unique($h[1])) . PHP_EOL;
echo implode("\n", $h[1]);
