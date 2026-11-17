<?php

namespace App\Services\Card;

class SolqamGeoCatalog
{
    protected static ?bool $packed = null;

    public static function seed(?\CodeIgniter\Database\BaseConnection $db = null): void
    {
        $db = $db ?? \Config\Database::connect();
        if (! $db->tableExists('solqam_divisions') || ! $db->tableExists('solqam_geo_cities')) {
            return;
        }
        $now = date('Y-m-d H:i:s');
        self::syncDivisionsAndCities($db, $now);
        if ($db->tableExists('solqam_merchant_categories')
            && (int) $db->table('solqam_merchant_categories')->countAllResults() === 0
        ) {
            foreach (SolqamPakistanGeoSeed::categories() as $row) {
                $db->table('solqam_merchant_categories')->insert([
                    'code'       => $row[0],
                    'name'       => $row[1],
                    'is_active'  => 1,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            }
        }
        if ($db->tableExists('solqam_golden_numbers')
            && (int) $db->table('solqam_golden_numbers')->countAllResults() === 0
        ) {
            foreach (SolqamPakistanGeoSeed::golden() as $row) {
                $db->table('solqam_golden_numbers')->insert([
                    'member_type' => $row[0],
                    'serial'      => $row[1],
                    'status'      => 'reserved',
                    'note'        => 'Seeded favourite number',
                    'created_at'  => $now,
                    'updated_at'  => $now,
                ]);
            }
        }
    }

    /**
     * Insert missing divisions/cities. Never changes an existing city code.
     */
    protected static function syncDivisionsAndCities($db, string $now): void
    {
        foreach (SolqamPakistanGeoSeed::divisions() as $row) {
            $divCode  = self::pad((string) $row[0], 2);
            $existing = $db->table('solqam_divisions')->where('code', $divCode)->get()->getRowArray();
            if ($existing) {
                $divId = (int) $existing['id'];
                $db->table('solqam_divisions')->where('id', $divId)->update([
                    'name'          => $row[1],
                    'province_name' => $row[2],
                    'is_active'     => 1,
                    'updated_at'    => $now,
                ]);
            } else {
                try {
                    $db->table('solqam_divisions')->insert([
                        'code'          => $divCode,
                        'name'          => $row[1],
                        'province_name' => $row[2],
                        'is_active'     => 1,
                        'created_at'    => $now,
                        'updated_at'    => $now,
                    ]);
                    $divId = (int) $db->insertID();
                } catch (\Throwable $e) {
                    continue;
                }
            }
            $usedCodes = [];
            $usedNames = [];
            foreach ($db->table('solqam_geo_cities')->where('division_id', $divId)->get()->getResultArray() as $cityRow) {
                $usedCodes[(string) $cityRow['code']] = true;
                $usedNames[strtolower(trim((string) $cityRow['name']))] = true;
            }
            foreach ($row[3] as $city) {
                $cityName = trim((string) $city[1]);
                $nameKey  = strtolower($cityName);
                if ($cityName === '' || isset($usedNames[$nameKey])) {
                    continue;
                }
                $cityCode = self::pad((string) $city[0], 2);
                if ($cityCode === '' || isset($usedCodes[$cityCode])) {
                    $n = 1;
                    while ($n <= 99 && isset($usedCodes[str_pad((string) $n, 2, '0', STR_PAD_LEFT)])) {
                        $n++;
                    }
                    if ($n > 99) {
                        continue;
                    }
                    $cityCode = str_pad((string) $n, 2, '0', STR_PAD_LEFT);
                }
                try {
                    $db->table('solqam_geo_cities')->insert([
                        'division_id' => $divId,
                        'code'        => $cityCode,
                        'name'        => $cityName,
                        'is_active'   => 1,
                        'created_at'  => $now,
                        'updated_at'  => $now,
                    ]);
                    $usedCodes[$cityCode] = true;
                    $usedNames[$nameKey]  = true;
                } catch (\Throwable $e) {
                }
            }
        }
    }

    public static function divisions(bool $activeOnly = true): array
    {
        self::ensurePacked();
        $pack = self::divisionsFromPack();
        try {
            $b = db_connect()->table('solqam_divisions');
            if ($activeOnly) {
                $b->where('is_active', 1);
            }
            $rows = $b->orderBy('code', 'ASC')->get()->getResultArray();
            if (count($rows) >= count($pack)) {
                return $rows;
            }
        } catch (\Throwable $e) {
        }

        return $pack;
    }

    public static function citiesForDivision(string $divisionCode, bool $activeOnly = true): array
    {
        self::ensurePacked();
        $pack = self::citiesFromPack($divisionCode);
        $div  = self::divisionByCode($divisionCode);
        if ($div) {
            try {
                $b = db_connect()->table('solqam_geo_cities')->where('division_id', (int) $div['id']);
                if ($activeOnly) {
                    $b->where('is_active', 1);
                }
                $rows = $b->orderBy('code', 'ASC')->get()->getResultArray();
                if (count($rows) >= count($pack) && $pack !== []) {
                    return $rows;
                }
            } catch (\Throwable $e) {
            }
        }

        return $pack;
    }

    protected static function ensurePacked(): void
    {
        if (self::$packed === true) {
            return;
        }
        try {
            $n = (int) db_connect()->table('solqam_geo_cities')->countAllResults();
            if ($n >= 500) {
                self::$packed = true;

                return;
            }
            self::seed();
            self::$packed = true;
        } catch (\Throwable $e) {
            self::$packed = false;
        }
    }

    /**
     * @return list<array{code:string,name:string,province_name:string}>
     */
    protected static function divisionsFromPack(): array
    {
        $out = [];
        foreach (SolqamPakistanGeoSeed::divisions() as $row) {
            $out[] = [
                'code'          => $row[0],
                'name'          => $row[1],
                'province_name' => $row[2],
                'is_active'     => 1,
            ];
        }

        return $out;
    }

    /**
     * @return list<array{code:string,name:string}>
     */
    protected static function citiesFromPack(string $divisionCode): array
    {
        $code = self::pad($divisionCode, 2);
        foreach (SolqamPakistanGeoSeed::divisions() as $row) {
            if ($row[0] !== $code) {
                continue;
            }
            $out = [];
            foreach ($row[3] as $city) {
                $out[] = [
                    'code'      => $city[0],
                    'name'      => $city[1],
                    'is_active' => 1,
                ];
            }

            return $out;
        }

        return [];
    }

    public static function citiesGrouped(): array
    {
        $out = [];
        foreach (self::divisions() as $div) {
            $out[$div['code']] = array_map(static fn ($c) => [
                'code' => $c['code'],
                'name' => $c['name'],
            ], self::citiesForDivision($div['code']));
        }

        return $out;
    }

    public static function divisionByCode(string $code): ?array
    {
        $code = self::pad($code, 2);
        if ($code === '') {
            return null;
        }
        try {
            $row = db_connect()->table('solqam_divisions')->where('code', $code)->get()->getRowArray();
            if ($row) {
                return $row;
            }
        } catch (\Throwable $e) {
        }
        foreach (self::divisionsFromPack() as $row) {
            if ($row['code'] === $code) {
                return $row;
            }
        }

        return null;
    }

    public static function cityByCodes(string $divisionCode, string $cityCode): ?array
    {
        self::ensurePacked();
        $div = self::divisionByCode($divisionCode);
        $cityCode = self::pad($cityCode, 2);
        if (! $div || $cityCode === '') {
            return null;
        }
        try {
            if (! empty($div['id'])) {
                $city = db_connect()->table('solqam_geo_cities')
                    ->where('division_id', (int) $div['id'])
                    ->where('code', $cityCode)
                    ->get()->getRowArray();
                if ($city) {
                    return [
                        'division_id'    => (int) $div['id'],
                        'division_code'  => $div['code'],
                        'division_name'  => $div['name'],
                        'province_name'  => $div['province_name'],
                        'city_code'      => $city['code'],
                        'city_name'      => $city['name'],
                    ];
                }
            }
        } catch (\Throwable $e) {
        }
        foreach (self::citiesFromPack($divisionCode) as $city) {
            if ($city['code'] !== $cityCode) {
                continue;
            }

            return [
                'division_id'    => (int) ($div['id'] ?? 0),
                'division_code'  => $div['code'],
                'division_name'  => $div['name'],
                'province_name'  => $div['province_name'] ?? '',
                'city_code'      => $city['code'],
                'city_name'      => $city['name'],
            ];
        }

        return null;
    }

    public static function merchantCategories(bool $activeOnly = true): array
    {
        try {
            $b = db_connect()->table('solqam_merchant_categories');
            if ($activeOnly) {
                $b->where('is_active', 1);
            }

            return $b->orderBy('code', 'ASC')->get()->getResultArray();
        } catch (\Throwable $e) {
            return [];
        }
    }

    public static function merchantCategory(string $codeOrName): ?array
    {
        $raw = trim($codeOrName);
        if ($raw === '') {
            return null;
        }
        $digits = self::pad($raw, 3);
        try {
            $b = db_connect()->table('solqam_merchant_categories');
            if (preg_match('/^\d{3}$/', $digits)) {
                $row = $b->where('code', $digits)->get()->getRowArray();
                if ($row) {
                    return $row;
                }
            }
            $row = db_connect()->table('solqam_merchant_categories')->where('name', $raw)->get()->getRowArray();
            if ($row) {
                return $row;
            }
            $rows = self::merchantCategories(false);
            foreach ($rows as $cand) {
                $n = strtolower((string) $cand['name']);
                $q = strtolower($raw);
                if ($n === $q || str_contains($n, $q) || str_contains($q, $n)) {
                    return $cand;
                }
            }

            return null;
        } catch (\Throwable $e) {
            return null;
        }
    }

    public static function resolveMerchantCategory(string $codeOrName): array
    {
        $hit = self::merchantCategory($codeOrName);
        if ($hit) {
            return ['code' => $hit['code'], 'name' => $hit['name']];
        }

        return ['code' => '010', 'name' => 'General Store'];
    }

    public static function nextCode(string $table, string $column, int $width): string
    {
        try {
            $row = db_connect()->table($table)->selectMax($column, 'm')->get()->getRowArray();
            $n   = (int) preg_replace('/\D+/', '', (string) ($row['m'] ?? '0'));

            return str_pad((string) ($n + 1), $width, '0', STR_PAD_LEFT);
        } catch (\Throwable $e) {
            return str_pad('1', $width, '0', STR_PAD_LEFT);
        }
    }

    public static function nextCityCode(int $divisionId): string
    {
        try {
            $row = db_connect()->table('solqam_geo_cities')->selectMax('code', 'm')->where('division_id', $divisionId)->get()->getRowArray();
            $n   = (int) preg_replace('/\D+/', '', (string) ($row['m'] ?? '0'));

            return str_pad((string) ($n + 1), 2, '0', STR_PAD_LEFT);
        } catch (\Throwable $e) {
            return '01';
        }
    }

    public static function isGoldenSerial(string $type, string $serial): bool
    {
        $type   = self::memberType($type);
        $width  = $type === 'M' ? 7 : 8;
        $serial = str_pad(preg_replace('/\D+/', '', $serial) ?? '', $width, '0', STR_PAD_LEFT);
        try {
            $row = db_connect()->table('solqam_golden_numbers')
                ->where('member_type', $type)
                ->where('serial', $serial)
                ->get()->getRowArray();

            return (bool) $row;
        } catch (\Throwable $e) {
            return false;
        }
    }

    public static function nextSerial(string $type, int $width): string
    {
        $type = self::memberType($type);
        $used = self::usedSerials($type, $width);
        try {
            $gold = db_connect()->table('solqam_golden_numbers')->where('member_type', $type)->get()->getResultArray();
            foreach ($gold as $g) {
                $used[str_pad((string) $g['serial'], $width, '0', STR_PAD_LEFT)] = true;
            }
        } catch (\Throwable $e) {
        }
        $max = (10 ** $width) - 1;
        for ($n = 1; $n <= $max; $n++) {
            $serial = str_pad((string) $n, $width, '0', STR_PAD_LEFT);
            if (! isset($used[$serial])) {
                return $serial;
            }
        }

        throw new \RuntimeException('No free Solqam Card serials left.');
    }

    public static function memberType(string $type): string
    {
        $t = strtoupper($type);
        if ($t === 'D') {
            return 'M';
        }

        return in_array($t, ['C', 'M', 'S'], true) ? $t : 'C';
    }

    /**
     * @return array<string,bool>
     */
    protected static function usedSerials(string $type, int $width): array
    {
        $used = [];
        try {
            $rows = db_connect()->table('solqam_cards')->select('pan')->get()->getResultArray();
            foreach ($rows as $row) {
                $parts = SolqamCardNumber::decode((string) ($row['pan'] ?? ''));
                $t     = self::memberType((string) ($parts['type'] ?? ''));
                if ($t !== $type) {
                    continue;
                }
                $serial = str_pad(preg_replace('/\D+/', '', (string) ($parts['unique'] ?? '')) ?? '', $width, '0', STR_PAD_LEFT);
                if ($serial !== str_repeat('0', $width)) {
                    $used[$serial] = true;
                }
            }
        } catch (\Throwable $e) {
        }

        return $used;
    }

    public static function pad(string $value, int $width): string
    {
        $n = preg_replace('/\D+/', '', $value) ?? '';
        if ($n === '') {
            return '';
        }

        return str_pad(substr($n, -$width), $width, '0', STR_PAD_LEFT);
    }
}
