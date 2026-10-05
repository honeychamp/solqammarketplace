<?php

namespace App\Models;

use CodeIgniter\Model;

class BannerModel extends Model
{
    protected $table         = 'banners';
    protected $primaryKey    = 'id';
    protected $returnType    = 'array';
    protected $allowedFields = ['title', 'subtitle', 'badge_text', 'button_text', 'image_path', 'link_url', 'placement', 'sort_order', 'is_active', 'starts_at', 'ends_at'];
    protected $useTimestamps = true;

    public function forPlacement(string $placement): array
    {
        $now = date('Y-m-d H:i:s');
        $rows = $this->where('is_active', 1)
            ->where('placement', $placement)
            ->orderBy('sort_order', 'ASC')
            ->findAll();

        return array_values(array_filter($rows, static function ($b) use ($now) {
            $starts = self::usableDate($b['starts_at'] ?? null);
            $ends   = self::usableDate($b['ends_at'] ?? null);
            if ($starts !== null && $starts > $now) {
                return false;
            }
            if ($ends !== null && $ends < $now) {
                return false;
            }

            return true;
        }));
    }

    protected static function usableDate($value): ?string
    {
        $value = trim((string) $value);
        if ($value === '' || str_starts_with($value, '0000-00-00')) {
            return null;
        }

        return $value;
    }
}
