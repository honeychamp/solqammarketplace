<?php

namespace App\Services\Card;

/**
 * Card IDs
 * Customer: 01-01-C-12345678  (compact 0101C12345678)
 * Merchant: 01-01-M-1234567-123 (compact 0101M1234567123)
 * Legacy v1: K0000C00000001 still accepted.
 */
class SolqamCardNumber
{
    public const PATTERN_V1   = '/^[A-Z]\d{4}[A-Z]\d{8}$/';
    public const PATTERN_V2_C = '/^\d{4}[CS]\d{8}$/';
    public const PATTERN_V2_M = '/^\d{4}M\d{10}$/';

    public const PROVINCES = [
        'K' => 'KPK',
        'P' => 'Punjab',
        'S' => 'Sindh',
        'B' => 'Balochistan',
        'I' => 'Islamabad',
        'G' => 'Gilgit-Baltistan',
        'A' => 'AJK',
    ];

    public const TYPES = [
        'C' => 'Customer',
        'M' => 'Merchant',
        'D' => 'Merchant',
        'S' => 'Seller',
    ];

    /** @var array<string, array{code:string, province:string}> */
    public const CITIES = [
        'abbottabad'     => ['code' => '0000', 'province' => 'K'],
        'peshawar'       => ['code' => '0001', 'province' => 'K'],
        'lahore'         => ['code' => '0100', 'province' => 'P'],
        'karachi'        => ['code' => '0200', 'province' => 'S'],
        'islamabad'      => ['code' => '0400', 'province' => 'I'],
    ];

    public static function isValid(string $pan): bool
    {
        $n = self::compact($pan);

        return $n !== '' && (
            (bool) preg_match(self::PATTERN_V1, $n)
            || (bool) preg_match(self::PATTERN_V2_C, $n)
            || (bool) preg_match(self::PATTERN_V2_M, $n)
        );
    }

    public static function compact(string $raw): string
    {
        return strtoupper(preg_replace('/[^A-Z0-9]/', '', $raw) ?? '');
    }

    public static function normalize(string $raw): string
    {
        $raw = self::compact($raw);
        if (preg_match('/\d{4}M\d{10}/', $raw, $m)) {
            return $m[0];
        }
        if (preg_match('/\d{4}[CS]\d{8}/', $raw, $m)) {
            return $m[0];
        }
        if (preg_match('/[A-Z]\d{4}[A-Z]\d{8}/', $raw, $m)) {
            return $m[0];
        }

        return '';
    }

    public static function isV2(string $pan): bool
    {
        $n = self::normalize($pan);

        return $n !== '' && ctype_digit($n[0]);
    }

    public static function display(string $pan): string
    {
        $n = self::normalize($pan);
        if ($n === '') {
            return strtoupper(trim($pan));
        }
        if (! ctype_digit($n[0])) {
            return $n;
        }
        $div  = substr($n, 0, 2);
        $city = substr($n, 2, 2);
        $type = $n[4];
        if ($type === 'M') {
            return $div . '-' . $city . '-M-' . substr($n, 5, 7) . '-' . substr($n, 12, 3);
        }

        return $div . '-' . $city . '-' . $type . '-' . substr($n, 5, 8);
    }

    public static function composeV2(string $division, string $city, string $type, string $serial, string $category = ''): string
    {
        $div  = SolqamGeoCatalog::pad($division, 2) ?: '01';
        $city = SolqamGeoCatalog::pad($city, 2) ?: '01';
        $type = SolqamGeoCatalog::memberType($type);
        if ($type === 'M') {
            $serial = str_pad(substr(preg_replace('/\D+/', '', $serial) ?? '1', -7), 7, '0', STR_PAD_LEFT);
            $cat    = SolqamGeoCatalog::pad($category, 3) ?: '010';

            return $div . $city . 'M' . $serial . $cat;
        }
        $serial = str_pad(substr(preg_replace('/\D+/', '', $serial) ?? '1', -8), 8, '0', STR_PAD_LEFT);

        return $div . $city . $type . $serial;
    }

    public static function mint(int $cardId, string $city, string $province, string $type): string
    {
        return self::mintFromGeo([
            'city'     => $city,
            'province' => $province,
        ], $type, null);
    }

    public static function mintFromGeo(array $user, string $type, ?array $shop, ?string $forcedSerial = null): string
    {
        $type = SolqamGeoCatalog::memberType($type);
        $geo  = self::resolveUserGeo($user);
        if ($type === 'M') {
            $serial = $forcedSerial !== null
                ? str_pad(substr(preg_replace('/\D+/', '', $forcedSerial) ?? '1', -7), 7, '0', STR_PAD_LEFT)
                : SolqamGeoCatalog::nextSerial('M', 7);
            $cat = SolqamGeoCatalog::resolveMerchantCategory((string) ($shop['category_code'] ?? $shop['category'] ?? '010'));

            return self::composeV2($geo['division_code'], $geo['city_code'], 'M', $serial, $cat['code']);
        }
        $serial = $forcedSerial !== null
            ? str_pad(substr(preg_replace('/\D+/', '', $forcedSerial) ?? '1', -8), 8, '0', STR_PAD_LEFT)
            : SolqamGeoCatalog::nextSerial($type, 8);

        return self::composeV2($geo['division_code'], $geo['city_code'], $type, $serial);
    }

    public static function resolveUserGeo(array $user): array
    {
        $div  = (string) ($user['division_code'] ?? '');
        $city = (string) ($user['city_code'] ?? '');
        $hit  = SolqamGeoCatalog::cityByCodes($div, $city);
        if ($hit) {
            return $hit;
        }

        return [
            'division_code' => '01',
            'city_code'     => '01',
            'division_name' => 'Lahore Division',
            'city_name'     => 'Lahore',
            'province_name' => 'Punjab',
        ];
    }

    public static function decode(string $pan): array
    {
        $n = self::normalize($pan);
        if ($n === '') {
            return self::emptyParts();
        }
        if (ctype_digit($n[0])) {
            return self::decodeV2($n);
        }

        return self::decodeV1($n);
    }

    protected static function decodeV2(string $n): array
    {
        $div  = substr($n, 0, 2);
        $city = substr($n, 2, 2);
        $type = $n[4];
        $geo  = SolqamGeoCatalog::cityByCodes($div, $city);
        $catCode = '';
        $catName = '';
        if ($type === 'M') {
            $uniq    = substr($n, 5, 7);
            $catCode = substr($n, 12, 3);
            $cat     = SolqamGeoCatalog::merchantCategory($catCode);
            $catName = $cat['name'] ?? ('Category ' . $catCode);
        } else {
            $uniq = substr($n, 5, 8);
        }

        return [
            'pan'            => self::display($n),
            'compact'        => $n,
            'format'         => 'v2',
            'division_code'  => $div,
            'division_name'  => $geo['division_name'] ?? ('Division ' . $div),
            'province'       => $div,
            'province_name'  => $geo['province_name'] ?? ($geo['division_name'] ?? 'Pakistan'),
            'city_code'      => $city,
            'city_name'      => $geo['city_name'] ?? ('City ' . $city),
            'type'           => $type,
            'type_name'      => self::TYPES[$type] ?? 'Customer',
            'unique'         => $uniq,
            'category_code'  => $catCode,
            'category_name'  => $catName,
        ];
    }

    protected static function decodeV1(string $pan): array
    {
        $p    = $pan[0];
        $city = substr($pan, 1, 4);
        $t    = $pan[5];
        $uniq = substr($pan, 6, 8);

        return [
            'pan'            => $pan,
            'compact'        => $pan,
            'format'         => 'v1',
            'division_code'  => '',
            'division_name'  => self::PROVINCES[$p] ?? $p,
            'province'       => $p,
            'province_name'  => self::PROVINCES[$p] ?? $p,
            'city_code'      => $city,
            'city_name'      => class_exists(SolqamCityRegistry::class) ? (SolqamCityRegistry::nameFor($p, $city) ?? 'City ' . $city) : ('City ' . $city),
            'type'           => $t,
            'type_name'      => self::TYPES[$t] ?? 'Customer',
            'unique'         => $uniq,
            'category_code'  => '',
            'category_name'  => '',
        ];
    }

    protected static function emptyParts(): array
    {
        return [
            'pan'            => '',
            'compact'        => '',
            'format'         => 'v2',
            'division_code'  => '01',
            'division_name'  => 'Lahore Division',
            'province'       => '01',
            'province_name'  => 'Punjab',
            'city_code'      => '01',
            'city_name'      => 'Lahore',
            'type'           => 'C',
            'type_name'      => 'Customer',
            'unique'         => '00000002',
            'category_code'  => '',
            'category_name'  => '',
        ];
    }

    public static function compose(string $provinceLetter, string $cityCode, string $type, string $serial8): string
    {
        return self::mintFromGeo([
            'province' => $provinceLetter,
            'city'     => $cityCode,
        ], $type, null, $serial8);
    }

    public static function slugCity(string $city): string
    {
        return strtolower(preg_replace('/[^a-z0-9]+/i', '', $city) ?? '');
    }

    public static function prettyCity(string $slug): string
    {
        return ucwords($slug);
    }

    public static function citiesForProvince(string $letter): array
    {
        return [];
    }

    public static function resolveCity(string $city, string $province = ''): array
    {
        return ['code' => '01', 'province' => $province, 'name' => $city];
    }

    public static function provinceLetter(string $province): string
    {
        $p = strtolower(trim($province));
        foreach (self::PROVINCES as $letter => $name) {
            if (strtolower($name) === $p || $letter === strtoupper($p)) {
                return $letter;
            }
        }
        if (str_contains($p, 'punjab')) {
            return 'P';
        }
        if (str_contains($p, 'sindh') || str_contains($p, 'sind')) {
            return 'S';
        }
        if (str_contains($p, 'baloch')) {
            return 'B';
        }
        if (str_contains($p, 'islamabad') || $p === 'ict') {
            return 'I';
        }
        if (str_contains($p, 'gilgit') || $p === 'gb') {
            return 'G';
        }
        if (str_contains($p, 'kashmir') || $p === 'ajk') {
            return 'A';
        }
        if (str_contains($p, 'khyber') || $p === 'kpk') {
            return 'K';
        }

        return 'P';
    }

    public static function typeForUser(array $user, bool $isDukandar): string
    {
        if ($isDukandar) {
            return 'M';
        }
        $role = strtolower((string) ($user['role'] ?? 'customer'));
        if ($role === 'seller') {
            return 'S';
        }

        return 'C';
    }
}
