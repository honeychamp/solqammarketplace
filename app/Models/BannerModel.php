<?php

namespace App\Models;

use CodeIgniter\Model;

class BannerModel extends Model
{
    protected $table         = 'banners';
    protected $primaryKey    = 'id';
    protected $returnType    = 'array';
    protected $allowedFields = ['title', 'subtitle', 'image_path', 'link_url', 'placement', 'sort_order', 'is_active'];
    protected $useTimestamps = true;

    public function forPlacement(string $placement): array
    {
        return $this->where('is_active', 1)
            ->where('placement', $placement)
            ->orderBy('sort_order', 'ASC')
            ->findAll();
    }
}
