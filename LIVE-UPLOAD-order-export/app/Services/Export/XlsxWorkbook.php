<?php

namespace App\Services\Export;

use RuntimeException;

/**
 * Minimal .xlsx writer (WPS / Excel). Numbers stay numeric so SUM works.
 */
class XlsxWorkbook
{
    /** @var array<int, array{name:string,headers:list<string>,groups:?list<array{label:string,span:int}>,rows:list<list<array{v:mixed,t:string}>>,firstOnlyHint:bool}> */
    protected array $sheets = [];

    /**
     * @param list<string> $headers
     * @param list<array{label:string,span:int}>|null $groups
     * @param list<list<array{v:mixed,t:string}>> $rows
     */
    public function addSheet(string $name, array $headers, array $rows, ?array $groups = null): void
    {
        $this->sheets[] = [
            'name'    => $name,
            'headers' => $headers,
            'groups'  => $groups,
            'rows'    => $rows,
        ];
    }

    public function save(string $path): void
    {
        $sheetCount = count($this->sheets);
        $files = [
            '[Content_Types].xml' => $this->contentTypes($sheetCount),
            '_rels/.rels' => '<?xml version="1.0" encoding="UTF-8"?>'
                . '<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">'
                . '<Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="xl/workbook.xml"/>'
                . '</Relationships>',
            'xl/_rels/workbook.xml.rels' => $this->workbookRels($sheetCount),
            'xl/workbook.xml' => $this->workbook(),
            'xl/styles.xml' => $this->styles(),
        ];
        foreach ($this->sheets as $i => $sheet) {
            $files['xl/worksheets/sheet' . ($i + 1) . '.xml'] = $this->sheetXml($sheet);
        }

        $dir = dirname($path);
        if ($dir !== '' && $dir !== '.' && ! is_dir($dir)) {
            @mkdir($dir, 0755, true);
        }

        // Never use ZipArchive — live PHP often has the class missing or broken.
        $this->saveStoredZip($path, $files);
    }

    /**
     * Uncompressed ZIP (method 0) so .xlsx still builds when ZipArchive is missing.
     *
     * @param array<string,string> $files
     */
    protected function saveStoredZip(string $path, array $files): void
    {
        $offset  = 0;
        $local   = '';
        $central = '';
        $count   = 0;
        foreach ($files as $name => $contents) {
            $name     = str_replace('\\', '/', $name);
            $crc      = pack('V', crc32($contents));
            $size     = pack('V', strlen($contents));
            $nlen     = pack('v', strlen($name));
            $header   = "\x50\x4b\x03\x04\x14\x00\x00\x00\x00\x00\x00\x00\x00\x00"
                . $crc . $size . $size . $nlen . "\x00\x00" . $name;
            $local   .= $header . $contents;
            $central .= "\x50\x4b\x01\x02\x14\x00\x14\x00\x00\x00\x00\x00\x00\x00\x00\x00"
                . $crc . $size . $size . $nlen . "\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00"
                . pack('V', $offset) . $name;
            $offset  += strlen($header) + strlen($contents);
            $count++;
        }
        $eocd = "\x50\x4b\x05\x06\x00\x00\x00\x00"
            . pack('v', $count) . pack('v', $count)
            . pack('V', strlen($central)) . pack('V', strlen($local))
            . "\x00\x00";
        if (file_put_contents($path, $local . $central . $eocd) === false) {
            throw new RuntimeException('Could not write Excel file.');
        }
    }

    protected function contentTypes(int $n): string
    {
        $xml = '<?xml version="1.0" encoding="UTF-8"?>'
            . '<Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types">'
            . '<Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/>'
            . '<Default Extension="xml" ContentType="application/xml"/>'
            . '<Override PartName="/xl/workbook.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet.main+xml"/>'
            . '<Override PartName="/xl/styles.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.styles+xml"/>';
        for ($i = 1; $i <= $n; $i++) {
            $xml .= '<Override PartName="/xl/worksheets/sheet' . $i . '.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml"/>';
        }

        return $xml . '</Types>';
    }

    protected function workbookRels(int $n): string
    {
        $xml = '<?xml version="1.0" encoding="UTF-8"?>'
            . '<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">'
            . '<Relationship Id="rIdSt" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/styles" Target="styles.xml"/>';
        for ($i = 1; $i <= $n; $i++) {
            $xml .= '<Relationship Id="rId' . $i . '" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet" Target="worksheets/sheet' . $i . '.xml"/>';
        }

        return $xml . '</Relationships>';
    }

    protected function workbook(): string
    {
        $xml = '<?xml version="1.0" encoding="UTF-8"?>'
            . '<workbook xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main"'
            . ' xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships"><sheets>';
        foreach ($this->sheets as $i => $sheet) {
            $xml .= '<sheet name="' . $this->esc($sheet['name']) . '" sheetId="' . ($i + 1) . '" r:id="rId' . ($i + 1) . '"/>';
        }

        return $xml . '</sheets></workbook>';
    }

    protected function styles(): string
    {
        return '<?xml version="1.0" encoding="UTF-8"?>'
            . '<styleSheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">'
            . '<fonts count="2">'
            . '<font><sz val="10"/><name val="Calibri"/></font>'
            . '<font><b/><sz val="10"/><name val="Calibri"/><color rgb="FFFFFFFF"/></font>'
            . '</fonts>'
            . '<fills count="5">'
            . '<fill><patternFill patternType="none"/></fill>'
            . '<fill><patternFill patternType="gray125"/></fill>'
            . '<fill><patternFill patternType="solid"><fgColor rgb="FF0B30E6"/><bgColor indexed="64"/></patternFill></fill>'
            . '<fill><patternFill patternType="solid"><fgColor rgb="FFFEE2E2"/><bgColor indexed="64"/></patternFill></fill>'
            . '<fill><patternFill patternType="solid"><fgColor rgb="FFF1F5F9"/><bgColor indexed="64"/></patternFill></fill>'
            . '</fills>'
            . '<borders count="1"><border><left/><right/><top/><bottom/><diagonal/></border></borders>'
            . '<cellStyleXfs count="1"><xf/></cellStyleXfs>'
            . '<cellXfs count="6">'
            . '<xf numFmtId="0" fontId="0" fillId="0" borderId="0"/>'
            . '<xf numFmtId="0" fontId="1" fillId="2" borderId="0" applyFont="1" applyFill="1"/>'
            . '<xf numFmtId="4" fontId="0" fillId="0" borderId="0" applyNumberFormat="1"/>'
            . '<xf numFmtId="14" fontId="0" fillId="0" borderId="0" applyNumberFormat="1"/>'
            . '<xf numFmtId="0" fontId="0" fillId="3" borderId="0" applyFill="1"/>'
            . '<xf numFmtId="0" fontId="0" fillId="4" borderId="0" applyFill="1"/>'
            . '</cellXfs>'
            . '</styleSheet>';
    }

    /**
     * @param array{name:string,headers:list<string>,groups:?list<array{label:string,span:int}>,rows:list<list<array{v:mixed,t:string}>>} $sheet
     */
    protected function sheetXml(array $sheet): string
    {
        $headers = $sheet['headers'];
        $groups  = $sheet['groups'];
        $colCount = max(1, count($headers));
        $hasGroups = is_array($groups) && $groups !== [];
        $headerRow = $hasGroups ? 2 : 1;
        $dataStart = $headerRow + 1;
        $lastRow = $headerRow + count($sheet['rows']);
        $lastCol = $this->col($colCount);

        $xml = '<?xml version="1.0" encoding="UTF-8"?>'
            . '<worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">'
            . '<sheetViews><sheetView tabSelected="1" workbookViewId="0">'
            . '<pane ySplit="' . $headerRow . '" xSplit="1" topLeftCell="B' . $dataStart . '" activePane="bottomRight" state="frozen"/>'
            . '</sheetView></sheetViews>'
            . '<cols>';
        for ($c = 1; $c <= $colCount; $c++) {
            $w = $c === 1 ? 18 : 16;
            $xml .= '<col min="' . $c . '" max="' . $c . '" width="' . $w . '" customWidth="1"/>';
        }
        $xml .= '</cols><sheetData>';

        $r = 1;
        if ($hasGroups) {
            $xml .= '<row r="1">';
            $c = 1;
            foreach ($groups as $g) {
                $xml .= $this->cell($c, 1, (string) $g['label'], 's', 1);
                $c += (int) $g['span'];
            }
            $xml .= '</row>';
            $r = 2;
        }

        $xml .= '<row r="' . $r . '">';
        foreach ($headers as $i => $h) {
            $xml .= $this->cell($i + 1, $r, $h, 's', 1);
        }
        $xml .= '</row>';

        $orderKey = null;
        $shade = false;
        foreach ($sheet['rows'] as $row) {
            $r++;
            $thisKey = (string) ($row[0]['v'] ?? '');
            if ($thisKey !== $orderKey) {
                $orderKey = $thisKey;
                $shade = ! $shade;
            }
            $fill = 0;
            $status = strtolower((string) ($row[59]['v'] ?? ''));
            $addrSt = strtolower((string) ($row[22]['v'] ?? ''));
            if (str_contains($status, 'cancel') || str_contains($status, 'return') || $addrSt === 'incomplete') {
                $fill = 4;
            } elseif ($shade) {
                $fill = 5;
            }
            $xml .= '<row r="' . $r . '">';
            foreach ($row as $i => $cell) {
                $style = $fill;
                $t = $cell['t'] ?? 's';
                if ($t === 'n' && $fill === 0) {
                    $style = 2;
                } elseif ($t === 'd' && $fill === 0) {
                    $style = 3;
                }
                $xml .= $this->cell($i + 1, $r, $cell['v'] ?? '', $t, $style);
            }
            $xml .= '</row>';
        }

        $xml .= '</sheetData>';
        if ($hasGroups) {
            $merges = '';
            $c = 1;
            $n = 0;
            foreach ($groups as $g) {
                $span = max(1, (int) $g['span']);
                if ($span > 1) {
                    $merges .= '<mergeCell ref="' . $this->col($c) . '1:' . $this->col($c + $span - 1) . '1"/>';
                    $n++;
                }
                $c += $span;
            }
            if ($n) {
                $xml .= '<mergeCells count="' . $n . '">' . $merges . '</mergeCells>';
            }
        }
        if ($lastRow >= $headerRow) {
            $xml .= '<autoFilter ref="A' . $headerRow . ':' . $lastCol . $lastRow . '"/>';
        }

        return $xml . '</worksheet>';
    }

    protected function cell(int $col, int $row, $value, string $type, int $style): string
    {
        $ref = $this->col($col) . $row;
        $s = $style > 0 ? ' s="' . $style . '"' : '';
        if ($type === 'n') {
            if ($value === '' || $value === null) {
                return '<c r="' . $ref . '"' . $s . '/>';
            }

            return '<c r="' . $ref . '"' . $s . ' t="n"><v>' . (is_numeric($value) ? (0 + $value) : 0) . '</v></c>';
        }
        if ($type === 'd') {
            if ($value === '' || $value === null) {
                return '<c r="' . $ref . '"' . $s . '/>';
            }
            $serial = is_numeric($value) ? $value : $this->dateSerial((string) $value);

            return '<c r="' . $ref . '"' . $s . '><v>' . $serial . '</v></c>';
        }
        $text = $this->esc((string) $value);

        return '<c r="' . $ref . '"' . $s . ' t="inlineStr"><is><t xml:space="preserve">' . $text . '</t></is></c>';
    }

    protected function dateSerial(string $ymd): string
    {
        $ts = strtotime($ymd);
        if ($ts === false) {
            return '0';
        }

        return (string) (25569 + ($ts / 86400));
    }

    protected function col(int $n): string
    {
        $s = '';
        while ($n > 0) {
            $n--;
            $s = chr(65 + ($n % 26)) . $s;
            $n = intdiv($n, 26);
        }

        return $s;
    }

    protected function esc(string $s): string
    {
        $s = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F]/', '', $s) ?? $s;

        return htmlspecialchars($s, ENT_QUOTES, 'UTF-8') ?: '';
    }
}
