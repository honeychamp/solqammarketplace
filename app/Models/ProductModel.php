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

    protected function initialize()
    {
        // Soft-deletes stay on. DESCRIBE on every ProductModel construct was extra DB.
    }

    protected function productsHas(string $field): bool
    {
        static $fields;
        if ($fields === null || ($field !== '' && ! isset($fields[$field]))) {
            try {
                $fields = array_flip($this->db->getFieldNames($this->table) ?: []);
            } catch (\Throwable $e) {
                $fields = $fields ?? [];
            }
        }

        return isset($fields[$field]);
    }

    public function persistCashback(int $productId, float $percent): void
    {
        if ($productId <= 0) {
            return;
        }
        $percent = max(0.0, min(100.0, round($percent, 2)));
        try {
            if (! $this->productsHas('cashback_percent')) {
                \App\Services\Platform\SchemaHeal::run();
                $this->productsHas('cashback_percent');
            }
            if (! $this->productsHas('cashback_percent')) {
                return;
            }
            $this->db->table($this->table)->where('id', $productId)->update([
                'cashback_percent' => $percent,
                'updated_at'       => date('Y-m-d H:i:s'),
            ]);
        } catch (\Throwable $e) {
            log_message('error', 'persistCashback: ' . $e->getMessage());
        }
    }

    protected function catalogImageSelect(): string
    {
        static $sql = null;
        if ($sql !== null) {
            return $sql;
        }
        try {
            if ($this->db->tableExists('product_images')) {
                return $sql = '(SELECT image_path FROM product_images WHERE product_images.product_id = products.id ORDER BY is_primary DESC, id ASC LIMIT 1) as primary_image';
            }
        } catch (\Throwable $e) {
            // ignore
        }

        return $sql = 'NULL as primary_image';
    }

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
            $product['images'] = $imageModel->forProduct($id);
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
        try {
            return $this->buildCatalogQuery($filters, false);
        } catch (\Throwable $e) {
            log_message('error', 'Product catalog: ' . $e->getMessage());

            return [];
        }
    }

    public function countCatalog(array $filters = []): int
    {
        try {
            return (int) $this->buildCatalogQuery($filters, true);
        } catch (\Throwable $e) {
            log_message('error', 'Product catalog count: ' . $e->getMessage());

            return 0;
        }
    }

    protected function buildCatalogQuery(array $filters, bool $countOnly)
    {
        $searchQ = trim(preg_replace('/\s+/u', ' ', (string) ($filters['search'] ?? '')) ?? '');
        $needCategoryJoin = ! $countOnly
            || $searchQ !== ''
            || (! empty($filters['category_slug']) && empty($filters['category_ids']));
        $builder = $this->select($countOnly ? 'products.id' : ('products.*, categories.name as category_name, categories.slug as category_slug, seller_profiles.store_name, ' . $this->catalogImageSelect()), false);
        if ($needCategoryJoin) {
            $builder->join('categories', 'categories.id = products.category_id', 'left');
        }
        $builder->join('seller_profiles', 'seller_profiles.user_id = products.seller_id', 'left')
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

        if ($searchQ !== '') {
            $this->applyCatalogSearch($builder, $searchQ);
        }

        if (!empty($filters['brand']) && $this->productsHas('brand')) {
            $builder->where('products.brand', $filters['brand']);
        }

        if (!empty($filters['mall']) && $this->productsHas('is_mall')) {
            $builder->where('products.is_mall', 1);
        }

        if (!empty($filters['sponsored']) && $this->productsHas('is_sponsored')) {
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

        if ($countOnly) {
            return (int) $builder->countAllResults();
        }

        $sort = $filters['sort'] ?? 'latest';
        if ($searchQ !== '' && $sort === 'latest') {
            $this->applySearchRelevance($builder, $searchQ);
            if ($this->productsHas('sold_count')) {
                $builder->orderBy('products.sold_count', 'DESC');
            }
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
                    if ($this->productsHas('sold_count')) {
                        $builder->orderBy('products.sold_count', 'DESC');
                    } else {
                        $builder->orderBy('products.id', 'DESC');
                    }
                    break;
                case 'rating':
                    $builder->orderBy('products.id', 'DESC');
                    break;
                default:
                    if ($this->productsHas('is_sponsored')) {
                        $builder->orderBy('products.is_sponsored', 'DESC');
                    }
                    $builder->orderBy('products.id', 'DESC');
                    break;
            }
        }

        $limit = $filters['limit'] ?? 24;
        $page = $filters['page'] ?? 1;
        $offset = ($page - 1) * $limit;

        $rows = $builder->findAll($limit, $offset);

        return $this->attachListingRatings($rows);
    }

    /**
     * @param list<array<string, mixed>> $rows
     * @return list<array<string, mixed>>
     */
    protected function attachListingRatings(array $rows): array
    {
        if ($rows === []) {
            return $rows;
        }
        try {
            if (! $this->db->tableExists('reviews')) {
                return $rows;
            }
            $ids = [];
            foreach ($rows as $row) {
                $id = (int) ($row['id'] ?? 0);
                if ($id > 0) {
                    $ids[] = $id;
                }
            }
            if ($ids === []) {
                return $rows;
            }
            $stats = $this->db->table('reviews')
                ->select('product_id, AVG(rating) AS avg_rating, COUNT(*) AS review_count')
                ->whereIn('product_id', $ids)
                ->where('is_approved', 1)
                ->groupBy('product_id')
                ->get()
                ->getResultArray();
            $map = [];
            foreach ($stats as $stat) {
                $map[(int) $stat['product_id']] = $stat;
            }
            foreach ($rows as &$row) {
                $id = (int) ($row['id'] ?? 0);
                $row['avg_rating'] = isset($map[$id]) ? (float) $map[$id]['avg_rating'] : null;
                $row['review_count'] = isset($map[$id]) ? (int) $map[$id]['review_count'] : 0;
            }
            unset($row);
        } catch (\Throwable $e) {
        }

        return $rows;
    }

    public function insertCatalog(array $data): int
    {
        $row = $this->onlyExistingColumns($data);
        $this->skipValidation(true);
        $id = $this->insert($row, true);
        if (! $id) {
            log_message('error', 'Product insert failed: ' . json_encode($this->errors()));

            return 0;
        }

        return (int) $id;
    }

    public function updateCatalog(int $id, array $data): bool
    {
        $row = $this->onlyExistingColumns($data);
        $this->skipValidation(true);

        return (bool) $this->update($id, $row);
    }

    protected function onlyExistingColumns(array $data): array
    {
        $out = [];
        foreach ($data as $key => $value) {
            if ($this->productsHas($key)) {
                $out[$key] = $value;
            }
        }

        return $out;
    }

    protected function applyCatalogSearch($builder, string $q): void
    {
        $tokens = preg_split('/[\s,\/]+/u', $q) ?: [];
        $tokens = array_values(array_unique(array_filter($tokens, static function ($t) {
            return is_string($t) && mb_strlen($t) >= 2;
        })));
        if ($tokens === []) {
            $tokens = [$q];
        }

        foreach ($tokens as $token) {
            $builder->groupStart();
            $this->matchSearchToken($builder, (string) $token);
            $builder->groupEnd();
        }
    }

    protected function matchSearchToken($builder, string $token): void
    {
        $first = true;
        foreach ($this->searchTokenAlts($token) as $alt) {
            $this->likeCatalogFields($builder, $alt, $first);
            $first = false;
        }

        $compact = strtolower((string) preg_replace('/[^a-z0-9]+/i', '', $token));
        if ($compact !== '' && strtolower($token) !== $compact) {
            $like = $this->db->escape('%' . $compact . '%');
            $builder->orWhere(
                "REPLACE(REPLACE(REPLACE(LOWER(products.name),' ',''),'-',''),'_','') LIKE {$like}",
                null,
                false
            );
        }
    }

    protected function likeCatalogFields($builder, string $alt, bool $first): void
    {
        if ($first) {
            $builder->like('products.name', $alt);
        } else {
            $builder->orLike('products.name', $alt);
        }
        $builder->orLike('products.sku', $alt)
            ->orLike('products.slug', $alt)
            ->orLike('categories.name', $alt)
            ->orLike('categories.slug', $alt)
            ->orLike('seller_profiles.store_name', $alt);
        if ($this->productsHas('brand')) {
            $builder->orLike('products.brand', $alt);
        }
        if (mb_strlen($alt) >= 3 && $this->productsHas('highlights')) {
            $builder->orLike('products.highlights', $alt);
        }
        $catIds = $this->categoryIdsMatchingToken($alt);
        if ($catIds !== []) {
            $builder->orWhereIn('products.category_id', $catIds);
        }
    }

    /**
     * @return list<int>
     */
    protected function categoryIdsMatchingToken(string $token): array
    {
        $needle = mb_strtolower(trim($token));
        if (mb_strlen($needle) < 2) {
            return [];
        }
        $index = $this->categorySearchIndex();
        $hit   = [];
        foreach ($index['rows'] as $id => $row) {
            $name = mb_strtolower((string) ($row['name'] ?? ''));
            $slug = mb_strtolower((string) ($row['slug'] ?? ''));
            if ($name === '' && $slug === '') {
                continue;
            }
            if (! str_contains($name, $needle) && ! str_contains($slug, $needle)) {
                continue;
            }
            foreach ($this->categoryDescendantIds($id, $index) as $did) {
                $hit[$did] = true;
            }
        }

        return array_map('intval', array_keys($hit));
    }

    /**
     * @return array{rows: array<int, array>, children: array<int, list<int>>}
     */
    protected function categorySearchIndex(): array
    {
        static $index;
        if ($index !== null) {
            return $index;
        }
        $rows     = [];
        $children = [];
        try {
            foreach ($this->db->table('categories')->select('id, parent_id, name, slug')->get()->getResultArray() as $row) {
                $id          = (int) ($row['id'] ?? 0);
                $rows[$id]   = $row;
                $pid         = (int) ($row['parent_id'] ?? 0);
                $children[$pid][] = $id;
            }
        } catch (\Throwable $e) {
            $rows     = [];
            $children = [];
        }
        $index = ['rows' => $rows, 'children' => $children];

        return $index;
    }

    /**
     * @param array{rows: array<int, array>, children: array<int, list<int>>} $index
     * @return list<int>
     */
    protected function categoryDescendantIds(int $categoryId, array $index): array
    {
        $ids   = [$categoryId];
        $queue = [$categoryId];
        $guard = 0;
        while ($queue !== [] && $guard++ < 2000) {
            $current = array_pop($queue);
            foreach ($index['children'][$current] ?? [] as $childId) {
                $ids[]   = (int) $childId;
                $queue[] = (int) $childId;
            }
        }

        return $ids;
    }

    /**
     * @return list<string>
     */
    protected function searchTokenAlts(string $token): array
    {
        $t = mb_strtolower($token);
        $alts = [$token];
        if (mb_strlen($t) > 3 && str_ends_with($t, 's') && ! str_ends_with($t, 'ss')) {
            $alts[] = mb_substr($token, 0, -1);
        }
        $map = [
            'phone'        => ['mobile', 'smartphone'],
            'phones'       => ['mobile', 'smartphone'],
            'smartphone'   => ['phone', 'mobile'],
            'smartphones'  => ['phone', 'mobile'],
            'mobile'       => ['phone', 'smartphone'],
            'mobiles'      => ['phone', 'smartphone'],
            'earbud'       => ['earbuds', 'earphone', 'earphones'],
            'earbuds'      => ['earphone', 'earphones'],
            'earphone'     => ['earbuds', 'earphones'],
            'earphones'    => ['earbuds', 'earphone'],
            'watch'        => ['watches', 'smartwatch'],
            'watches'      => ['watch', 'smartwatch'],
            'laptop'       => ['notebook', 'laptops'],
            'laptops'      => ['laptop', 'notebook'],
            'fashion'      => ['clothing', 'apparel', 'dress'],
        ];
        if (isset($map[$t])) {
            $alts = array_merge($alts, $map[$t]);
        }

        return array_values(array_unique($alts));
    }

    protected function applySearchRelevance($builder, string $q): void
    {
        $like  = $this->db->escapeLikeString($q);
        $start = $this->db->escape($like . '%');
        $any   = $this->db->escape('%' . $like . '%');
        $brand = $this->productsHas('brand')
            ? " WHEN products.brand LIKE {$any} ESCAPE '!' THEN 2"
            : '';
        $builder->orderBy(
            "CASE WHEN products.name LIKE {$start} ESCAPE '!' THEN 0 WHEN products.name LIKE {$any} ESCAPE '!' THEN 1{$brand} ELSE 3 END",
            '',
            false
        );
    }

    protected function applyPublicSellerScope($builder): void
    {
        static $hasApproval = null;
        if ($hasApproval === null) {
            try {
                $hasApproval = $this->db->fieldExists('approval_status', 'seller_profiles');
            } catch (\Throwable $e) {
                $hasApproval = false;
            }
        }
        if (! $hasApproval) {
            return;
        }

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
