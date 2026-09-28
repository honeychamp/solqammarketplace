<?php

namespace App\Models;

use CodeIgniter\Model;

class WishlistModel extends Model
{
    protected $table         = 'wishlists';
    protected $primaryKey    = 'id';
    protected $returnType    = 'array';
    protected $allowedFields = ['user_id', 'product_id'];
    protected $useTimestamps = true;
    protected $updatedField  = '';

    public function toggle(int $userId, int $productId): bool
    {
        $row = $this->where('user_id', $userId)->where('product_id', $productId)->first();
        if ($row) {
            $this->delete($row['id']);
            return false;
        }
        $this->insert(['user_id' => $userId, 'product_id' => $productId]);
        return true;
    }

    public function isSaved(int $userId, int $productId): bool
    {
        return (bool) $this->where('user_id', $userId)->where('product_id', $productId)->first();
    }

    public function getUserWishlist(int $userId): array
    {
        return $this->select('wishlists.*, products.name, products.price, products.slug, products.stock, products.status, products.cashback_percent, seller_profiles.store_name, (SELECT image_path FROM product_images WHERE product_images.product_id = products.id ORDER BY is_primary DESC, id ASC LIMIT 1) as primary_image')
            ->join('products', 'products.id = wishlists.product_id')
            ->join('seller_profiles', 'seller_profiles.user_id = products.seller_id', 'left')
            ->where('wishlists.user_id', $userId)
            ->orderBy('wishlists.id', 'DESC')
            ->findAll();
    }
}
