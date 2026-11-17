<?php

namespace App\Services\Card;

use Config\Database;

/**
 * Persistent 4-digit city codes per province, plus 8-digit card serials.
 */
class SolqamCityRegistry
{
    public static function allocate(string $city, string $province): array
    {
        $letter = SolqamCardNumber::provinceLetter($province);
        $name   = trim($city);
        if ($name === '') {
            $name = 'Abbottabad';
            $letter = $letter !== '' ? $letter : 'K';
        }
        $slug = SolqamCardNumber::slugCity($name);
        $db   = Database::connect();
        if (! $db->tableExists('solqam_city_codes')) {
            $fallback = SolqamCardNumber::resolveCity($name, $letter);

            return [
                'province' => $fallback['province'],
                'code'     => $fallback['code'],
                'name'     => $name,
                'slug'     => $slug,
            ];
        }
        $row = $db->table('solqam_city_codes')
            ->where('province_letter', $letter)
            ->where('slug', $slug)
            ->get()
            ->getRowArray();
        if ($row) {
            return [
                'province' => (string) $row['province_letter'],
                'code'     => (string) $row['city_code'],
                'name'     => (string) $row['city_name'],
                'slug'     => (string) $row['slug'],
            ];
        }
        $code = self::nextCityCode($db, $letter, $slug);
        $db->table('solqam_city_codes')->insert([
            'province_letter' => $letter,
            'slug'            => $slug,
            'city_name'       => SolqamCardNumber::prettyCity($slug) ?: $name,
            'city_code'       => $code,
            'created_at'      => date('Y-m-d H:i:s'),
        ]);

        return [
            'province' => $letter,
            'code'     => $code,
            'name'     => $name,
            'slug'     => $slug,
        ];
    }

    public static function nextSerial(int $hint = 0): string
    {
        $db  = Database::connect();
        $max = 0;
        if ($db->tableExists('solqam_cards')) {
            $rows = $db->table('solqam_cards')->select('pan')->get()->getResultArray();
            foreach ($rows as $row) {
                $pan = SolqamCardNumber::normalize((string) ($row['pan'] ?? ''));
                if ($pan === '') {
                    continue;
                }
                $max = max($max, (int) substr($pan, 6, 8));
            }
        }
        $n = max($max + 1, $hint > 0 ? $hint : 1);

        return str_pad((string) $n, 8, '0', STR_PAD_LEFT);
    }

    public static function seed($db = null): void
    {
        $db = $db ?? Database::connect();
        if (! $db->tableExists('solqam_city_codes')) {
            return;
        }
        $now = date('Y-m-d H:i:s');
        foreach (SolqamCardNumber::CITIES as $slug => $row) {
            $exists = $db->table('solqam_city_codes')
                ->where('province_letter', $row['province'])
                ->where('slug', $slug)
                ->countAllResults();
            if ($exists > 0) {
                continue;
            }
            try {
                $db->table('solqam_city_codes')->insert([
                    'province_letter' => $row['province'],
                    'slug'            => $slug,
                    'city_name'       => SolqamCardNumber::prettyCity($slug),
                    'city_code'       => $row['city_code'] ?? $row['code'],
                    'created_at'      => $now,
                ]);
            } catch (\Throwable $e) {
            }
        }
    }

    public static function nameFor(string $provinceLetter, string $cityCode): ?string
    {
        try {
            $db = Database::connect();
            if (! $db->tableExists('solqam_city_codes')) {
                return null;
            }
            $row = $db->table('solqam_city_codes')
                ->where('province_letter', $provinceLetter)
                ->where('city_code', $cityCode)
                ->get()
                ->getRowArray();

            return $row['city_name'] ?? null;
        } catch (\Throwable $e) {
            return null;
        }
    }

    protected static function nextCityCode($db, string $letter, string $slug): string
    {
        $known = SolqamCardNumber::CITIES[$slug] ?? null;
        if ($known && ($known['province'] ?? '') === $letter) {
            $taken = $db->table('solqam_city_codes')
                ->where('province_letter', $letter)
                ->where('city_code', $known['code'])
                ->countAllResults();
            if ($taken < 1) {
                return $known['code'];
            }
        }
        $row = $db->table('solqam_city_codes')
            ->selectMax('city_code', 'max_code')
            ->where('province_letter', $letter)
            ->get()
            ->getRowArray();
        $next = ((int) ($row['max_code'] ?? -1)) + 1;
        if ($next > 9999) {
            $next = 0;
        }

        return sprintf('%04d', $next);
    }
}
