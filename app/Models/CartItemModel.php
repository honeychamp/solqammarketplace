<?php

namespace App\Models;

use CodeIgniter\Model;

class CartItemModel extends Model
{
    protected $table            = 'cart_items';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $protectFields    = true;
    protected $allowedFields    = [
        'cart_id',
        'product_id',
        'variant_id',
        'quantity',
        'unit_price',
    ];

    protected $useTimestamps = true;
    protected $dateFormat    = 'datetime';
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';

    public function getItemsWithProducts(int $cartId): array
    {
        if ($cartId <= 0) {
            return [];
        }

        try {
            $cashback = $this->db->fieldExists('cashback_percent', 'products')
                ? 'products.cashback_percent'
                : '0 as cashback_percent';
            $imageSql = $this->db->tableExists('product_images')
                ? '(SELECT image_path FROM product_images WHERE product_images.product_id = products.id ORDER BY is_primary DESC, id ASC LIMIT 1) as primary_image'
                : 'NULL as primary_image';
            $variantJoin = $this->db->tableExists('product_variants');

            $builder = $this->select("cart_items.*, products.name as product_name, products.price as current_price, products.stock as product_stock, products.seller_id, products.category_id, {$cashback}, seller_profiles.store_name, " . ($variantJoin ? 'product_variants.color as variant_color, product_variants.size as variant_size, product_variants.stock as variant_stock, ' : 'NULL as variant_color, NULL as variant_size, NULL as variant_stock, ') . $imageSql)
                ->join('products', 'products.id = cart_items.product_id')
                ->join('seller_profiles', 'seller_profiles.user_id = products.seller_id', 'left');
            if ($variantJoin) {
                $builder->join('product_variants', 'product_variants.id = cart_items.variant_id', 'left');
            }
            $items = $builder->where('cart_items.cart_id', $cartId)->findAll();
        } catch (\Throwable $e) {
            log_message('error', 'Cart items: ' . $e->getMessage());

            return [];
        }

        foreach ($items as &$item) {
            $item['stock_available'] = !empty($item['variant_id'])
                ? (int) ($item['variant_stock'] ?? 0)
                : (int) ($item['product_stock'] ?? 0);
            $parts = array_filter([$item['variant_color'] ?? null, $item['variant_size'] ?? null]);
            $item['variant_label'] = $parts ? implode(' / ', $parts) : null;
        }
        unset($item);

        return $items;
    }
}
