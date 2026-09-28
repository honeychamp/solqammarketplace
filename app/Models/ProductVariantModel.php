<?php

namespace App\Models;

use CodeIgniter\Model;

class ProductVariantModel extends Model
{
    protected $table            = 'product_variants';
    protected $primaryKey       = 'id';
    protected $returnType       = 'array';
    protected $protectFields    = true;
    protected $allowedFields    = ['product_id', 'sku', 'color', 'size', 'price', 'stock'];
    protected $useTimestamps    = true;

    public function forProduct(int $productId): array
    {
        return $this->where('product_id', $productId)->orderBy('id', 'ASC')->findAll();
    }

    public function label(array $variant): string
    {
        $parts = array_filter([$variant['color'] ?? null, $variant['size'] ?? null]);
        return $parts ? implode(' / ', $parts) : 'Standard';
    }
}
