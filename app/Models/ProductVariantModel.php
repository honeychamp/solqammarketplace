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

    protected function initialize()
    {
        try {
            if (! $this->db->fieldExists('created_at', $this->table)) {
                $this->useTimestamps = false;
            }
            if (! $this->db->fieldExists('image', $this->table)) {
                $this->allowedFields = array_values(array_diff($this->allowedFields, ['image']));
            }
        } catch (\Throwable $e) {
            $this->useTimestamps = false;
        }
    }

    public function firstIdsForProducts(array $productIds): array
    {
        $productIds = array_values(array_unique(array_filter(array_map('intval', $productIds))));
        if ($productIds === []) {
            return [];
        }
        $rows = $this->select('id, product_id')
            ->whereIn('product_id', $productIds)
            ->orderBy('id', 'ASC')
            ->findAll();
        $map = [];
        foreach ($rows as $row) {
            $pid = (int) ($row['product_id'] ?? 0);
            if ($pid > 0 && ! isset($map[$pid])) {
                $map[$pid] = (int) $row['id'];
            }
        }

        return $map;
    }

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
