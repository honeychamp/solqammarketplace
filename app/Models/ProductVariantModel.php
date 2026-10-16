<?php

namespace App\Models;

use CodeIgniter\Model;

class ProductVariantModel extends Model
{
    protected $table            = 'product_variants';
    protected $primaryKey       = 'id';
    protected $returnType       = 'array';
    protected $protectFields    = true;
    protected $allowedFields    = ['product_id', 'sku', 'color', 'size', 'price', 'stock', 'image'];
    protected $useTimestamps    = true;

    public function forProduct(int $productId): array
    {
        $rows = $this->where('product_id', $productId)->orderBy('id', 'ASC')->findAll();
        foreach ($rows as &$row) {
            $row['id']    = (int) ($row['id'] ?? 0);
            $row['stock'] = (int) ($row['stock'] ?? 0);
            $row['price'] = isset($row['price']) && $row['price'] !== '' && $row['price'] !== null
                ? (float) $row['price']
                : null;
            $img = trim((string) ($row['image'] ?? ''));
            $row['image'] = $img !== '' && function_exists('media_url') ? media_url($img) : $img;
        }
        unset($row);

        return $rows;
    }

    public function label(array $variant): string
    {
        $parts = array_filter([$variant['color'] ?? null, $variant['size'] ?? null]);
        return $parts ? implode(' / ', $parts) : 'Standard';
    }
}
