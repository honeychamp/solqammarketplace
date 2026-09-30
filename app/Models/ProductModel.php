<?php

namespace App\Models;

use CodeIgniter\Model;

class ProductModel extends Model
{
    protected $table            = 'products';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $useSoftDeletes   = true;
    protected $protectFields    = true;
    protected $allowedFields    = [
        'seller_id',
        'category_id',
        'name',
        'slug',
        'description',
        'highlights',
        'specifications',
        'size_guide',
        'warranty_info',
        'return_days',
        'price',
        'compare_at_price',
        'stock',
        'sku',
        'brand',
        'cashback_percent',
        'sold_count',
        'is_mall',
        'is_sponsored',
        'status',
    ];

    protected $useTimestamps = true;
    protected $dateFormat    = 'datetime';
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';
    protected $deletedField  = 'deleted_at';

    public function getDetailedProduct(int $id): ?array
    {
        $product = $this->select('products.*, categories.name as category_name, categories.slug as category_slug, users.name as seller_name, seller_profiles.store_name, seller_profiles.approval_status as seller_approval')
            ->join('categories', 'categories.id = products.category_id', 'left')
            ->join('users', 'users.id = products.seller_id', 'left')
            ->join('seller_profiles', 'seller_profiles.user_id = products.seller_id', 'left')
            ->where('products.id', $id)
            ->first();

        if ($product && ! $this->isPublicSellerRow($product)) {
            return null;
        }

        if ($product) {
            $imageModel = new ProductImageModel();
            $product['images'] = $imageModel->where('product_id', $id)->findAll();
            $primary = $imageModel->where('product_id', $id)->where('is_primary', 1)->first();
            $product['primary_image'] = $primary['image_path'] ?? null;
            if (!$product['primary_image'] && !empty($product['images'])) {
                $product['primary_image'] = $product['images'][0]['image_path'];
            }
            $variantModel = new ProductVariantModel();
            $product['variants'] = $variantModel->forProduct($id);
        }

        return $product;
    }

    public function getCatalog(array $filters = []): array
    {
        $builder = $this->select('products.*, categories.name as category_name, categories.slug as category_slug, seller_profiles.store_name, (SELECT image_path FROM product_images WHERE product_images.product_id = products.id ORDER BY is_primary DESC, id ASC LIMIT 1) as primary_image')
            ->join('categories', 'categories.id = products.category_id', 'left')
            ->join('seller_profiles', 'seller_profiles.user_id = products.seller_id', 'left')
            ->where('products.status', 'active');
        $this->applyPublicSellerScope($builder);

        if (!empty($filters['category_ids']) && is_array($filters['category_ids'])) {
            $builder->whereIn('products.category_id', $filters['category_ids']);
        } elseif (!empty($filters['category_id'])) {
            $builder->where('products.category_id', $filters['category_id']);
        }

        if (!empty($filters['category_slug']) && empty($filters['category_ids'])) {
            $builder->where('categories.slug', $filters['category_slug']);
        }

        if (!empty($filters['search'])) {
            $q = $filters['search'];
            $builder->groupStart()
                ->like('products.name', $q)
                ->orLike('products.description', $q)
                ->orLike('products.brand', $q)
                ->orLike('products.sku', $q)
                ->orLike('seller_profiles.store_name', $q)
                ->groupEnd();
        }

        if (!empty($filters['brand'])) {
            $builder->where('products.brand', $filters['brand']);
        }

        if (!empty($filters['mall'])) {
            $builder->where('products.is_mall', 1);
        }

        if (!empty($filters['sponsored'])) {
            $builder->where('products.is_sponsored', 1);
        }

        if (!empty($filters['seller_id'])) {
            $builder->where('products.seller_id', $filters['seller_id']);
        }

        if (!empty($filters['min_price'])) {
            $builder->where('products.price >=', $filters['min_price']);
        }

        if (!empty($filters['max_price'])) {
            $builder->where('products.price <=', $filters['max_price']);
        }

        $sort = $filters['sort'] ?? 'latest';
        if (!empty($filters['search']) && $sort === 'latest') {
            $builder->orderBy('products.sold_count', 'DESC');
            $builder->orderBy('products.id', 'DESC');
        } else {
            switch ($sort) {
                case 'price_low':
                    $builder->orderBy('products.price', 'ASC');
                    break;
                case 'price_high':
                    $builder->orderBy('products.price', 'DESC');
                    break;
                case 'name_asc':
                    $builder->orderBy('products.name', 'ASC');
                    break;
                case 'best_selling':
                    $builder->orderBy('products.sold_count', 'DESC');
                    break;
                case 'rating':
                    $builder->orderBy('(SELECT AVG(rating) FROM reviews WHERE reviews.product_id = products.id)', 'DESC');
                    break;
                default:
                    $builder->orderBy('products.is_sponsored', 'DESC');
                    $builder->orderBy('products.id', 'DESC');
                    break;
            }
        }

        $limit = $filters['limit'] ?? 24;
        $page = $filters['page'] ?? 1;
        $offset = ($page - 1) * $limit;

        return $builder->findAll($limit, $offset);
    }

    public function countCatalog(array $filters = []): int
    {
        $builder = $this->select('products.id')
            ->join('categories', 'categories.id = products.category_id', 'left')
            ->join('seller_profiles', 'seller_profiles.user_id = products.seller_id', 'left')
            ->where('products.status', 'active');
        $this->applyPublicSellerScope($builder);

        if (!empty($filters['category_ids']) && is_array($filters['category_ids'])) {
            $builder->whereIn('products.category_id', $filters['category_ids']);
        } elseif (!empty($filters['category_id'])) {
            $builder->where('products.category_id', $filters['category_id']);
        }

        if (!empty($filters['category_slug']) && empty($filters['category_ids'])) {
            $builder->where('categories.slug', $filters['category_slug']);
        }

        if (!empty($filters['search'])) {
            $q = $filters['search'];
            $builder->groupStart()
                ->like('products.name', $q)
                ->orLike('products.description', $q)
                ->orLike('products.brand', $q)
                ->orLike('products.sku', $q)
                ->orLike('seller_profiles.store_name', $q)
                ->groupEnd();
        }

        if (!empty($filters['brand'])) {
            $builder->where('products.brand', $filters['brand']);
        }

        if (!empty($filters['mall'])) {
            $builder->where('products.is_mall', 1);
        }

        if (!empty($filters['sponsored'])) {
            $builder->where('products.is_sponsored', 1);
        }

        if (!empty($filters['seller_id'])) {
            $builder->where('products.seller_id', $filters['seller_id']);
        }

        if (!empty($filters['min_price'])) {
            $builder->where('products.price >=', $filters['min_price']);
        }

        if (!empty($filters['max_price'])) {
            $builder->where('products.price <=', $filters['max_price']);
        }

        return (int) $builder->countAllResults();
    }

    protected function applyPublicSellerScope($builder): void
    {
        $builder->groupStart()
            ->where('seller_profiles.approval_status', 'approved')
            ->orWhere('seller_profiles.id', null)
            ->groupEnd();
    }

    protected function isPublicSellerRow(array $product): bool
    {
        $status = $product['seller_approval'] ?? null;
        if ($status === null || $status === '') {
            return true;
        }

        return $status === 'approved';
    }
}
