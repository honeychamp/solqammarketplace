<?php

namespace App\Models;

use CodeIgniter\Model;

class FlashSaleModel extends Model
{
    protected $table         = 'flash_sales';
    protected $primaryKey    = 'id';
    protected $returnType    = 'array';
    protected $allowedFields = ['title', 'starts_at', 'ends_at', 'is_active'];
    protected $useTimestamps = true;

    public function getActive(): ?array
    {
        $now = date('Y-m-d H:i:s');
        return $this->where('is_active', 1)
            ->where('starts_at <=', $now)
            ->where('ends_at >=', $now)
            ->orderBy('id', 'DESC')
            ->first();
    }
}
