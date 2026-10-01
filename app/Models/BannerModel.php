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
            if (! empty($b['starts_at']) && $b['starts_at'] > $now) {
                return false;
            }
            if (! empty($b['ends_at']) && $b['ends_at'] < $now) {
                return false;
            }

            return true;
        }));
    }
}
