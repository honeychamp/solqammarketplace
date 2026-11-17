<?php

namespace App\Models;

use CodeIgniter\Model;

class DukandarProfileModel extends Model
{
    protected $table         = 'dukandar_profiles';
    protected $primaryKey    = 'id';
    protected $returnType    = 'array';
    protected $protectFields = true;
    protected $allowedFields = [
        'user_id',
        'owner_name',
        'shop_name',
        'phone',
        'email',
        'city',
        'province',
        'whatsapp',
        'opening_hours',
        'merchant_code',
        'area',
        'address',
        'category',
        'category_code',
        'shop_image',
        'shop_image_in',
        'occupancy_type',
        'business_year',
        'lat',
        'lng',
        'approval_status',
        'rejection_reason',
        'accepts_card',
    ];
    protected $useTimestamps = true;
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';

    public function getByUserId(int $userId): ?array
    {
        if ($userId < 1) {
            return null;
        }

        return $this->where('user_id', $userId)->first();
    }

    public function publicDirectory(array $filters = []): array
    {
        $builder = $this->where('approval_status', 'approved')->where('accepts_card', 1);
        $q       = trim((string) ($filters['q'] ?? ''));
        $city    = trim((string) ($filters['city'] ?? ''));
        $cat     = trim((string) ($filters['category'] ?? ''));
        if ($q !== '') {
            $builder->groupStart()
                ->like('shop_name', $q)
                ->orLike('owner_name', $q)
                ->orLike('area', $q)
                ->orLike('address', $q)
                ->orLike('city', $q)
                ->groupEnd();
        }
        if ($city !== '') {
            $builder->like('city', $city);
        }
        if ($cat !== '') {
            $builder->where('category', $cat);
        }

        $rows = $builder->orderBy('shop_name', 'ASC')->findAll(200);
        $lat  = isset($filters['lat']) ? (float) $filters['lat'] : 0.0;
        $lng  = isset($filters['lng']) ? (float) $filters['lng'] : 0.0;
        if ($lat === 0.0 && $lng === 0.0) {
            return $rows;
        }
        foreach ($rows as &$row) {
            $slat = (float) ($row['lat'] ?? 0);
            $slng = (float) ($row['lng'] ?? 0);
            if ($slat === 0.0 && $slng === 0.0) {
                $row['distance_km'] = null;
                continue;
            }
            $row['distance_km'] = round(self::haversineKm($lat, $lng, $slat, $slng), 1);
        }
        unset($row);
        usort($rows, static function ($a, $b) {
            $da = $a['distance_km'];
            $db = $b['distance_km'];
            if ($da === null && $db === null) {
                return strcmp((string) $a['shop_name'], (string) $b['shop_name']);
            }
            if ($da === null) {
                return 1;
            }
            if ($db === null) {
                return -1;
            }

            return $da <=> $db;
        });

        return $rows;
    }

    protected static function haversineKm(float $lat1, float $lng1, float $lat2, float $lng2): float
    {
        $r    = 6371;
        $dLat = deg2rad($lat2 - $lat1);
        $dLng = deg2rad($lng2 - $lng1);
        $a    = sin($dLat / 2) ** 2
            + cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * sin($dLng / 2) ** 2;

        return $r * 2 * atan2(sqrt($a), sqrt(1 - $a));
    }
}
