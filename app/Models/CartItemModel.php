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
        $items = $this->select('cart_items.*, products.name as product_name, products.price as current_price, products.stock as product_stock, products.seller_id, products.cashback_percent, seller_profiles.store_name, product_variants.color as variant_color, product_variants.size as variant_size, product_variants.stock as variant_stock, (SELECT image_path FROM product_images WHERE product_images.product_id = products.id ORDER BY is_primary DESC, id ASC LIMIT 1) as primary_image')
            ->join('products', 'products.id = cart_items.product_id')
            ->join('seller_profiles', 'seller_profiles.user_id = products.seller_id', 'left')
            ->join('product_variants', 'product_variants.id = cart_items.variant_id', 'left')
            ->where('cart_items.cart_id', $cartId)
            ->findAll();

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
