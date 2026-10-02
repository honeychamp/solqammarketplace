<?php

namespace App\Models;

use CodeIgniter\Model;

class ProductImageModel extends Model
{
    protected $table            = 'product_images';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $protectFields    = true;
    protected $allowedFields    = [
        'product_id',
        'image_path',
        'is_primary',
        'sort_order',
    ];

    protected $useTimestamps = true;
    protected $dateFormat    = 'datetime';
    protected $createdField  = 'created_at';
    protected $updatedField  = '';

    protected function initialize()
    {
        try {
            if (! $this->db->fieldExists('sort_order', $this->table)) {
                $this->db->query('ALTER TABLE product_images ADD COLUMN sort_order INT NOT NULL DEFAULT 0 AFTER is_primary');
            }
        } catch (\Throwable $e) {
            // Live DB without ALTER privilege — inserts skip the column
        }
    }

    public function forProduct(int $productId): array
    {
        $builder = $this->where('product_id', $productId);
        try {
            if ($this->db->fieldExists('sort_order', $this->table)) {
                $builder->orderBy('is_primary', 'DESC')->orderBy('sort_order', 'ASC')->orderBy('id', 'ASC');
            } else {
                $builder->orderBy('is_primary', 'DESC')->orderBy('id', 'ASC');
            }
        } catch (\Throwable $e) {
            $builder->orderBy('is_primary', 'DESC')->orderBy('id', 'ASC');
        }

        return $builder->findAll();
    }
}
