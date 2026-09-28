<?php

namespace App\Services\Catalog;

use App\Models\FlashSaleItemModel;
use App\Models\FlashSaleModel;
use App\Models\ProductModel;
use App\Models\ProductVariantModel;

class PricingService
{
    protected FlashSaleModel $flashSaleModel;
    protected FlashSaleItemModel $flashItemModel;
    protected ProductVariantModel $variantModel;

    public function __construct()
    {
        $this->flashSaleModel = new FlashSaleModel();
        $this->flashItemModel = new FlashSaleItemModel();
        $this->variantModel   = new ProductVariantModel();
    }

    public function getActiveSale(): ?array
    {
        return $this->flashSaleModel->getActive();
    }

    public function getFlashPriceMap(): array
    {
        $sale = $this->getActiveSale();
        if (!$sale) {
            return [];
        }
        $items = $this->flashItemModel->where('flash_sale_id', $sale['id'])->findAll();
        $map = [];
        foreach ($items as $item) {
            $map[(int) $item['product_id']] = (float) $item['sale_price'];
        }
        return $map;
    }

    public function resolveUnitPrice(array $product, ?array $variant = null): float
    {
        $base = $variant && !empty($variant['price'])
            ? (float) $variant['price']
            : (float) $product['price'];

        $flashMap = $this->getFlashPriceMap();
        $productId = (int) $product['id'];
        if (isset($flashMap[$productId])) {
            return min($base, $flashMap[$productId]);
        }

        return $base;
    }

    public function getFlashProducts(int $limit = 8): array
    {
        $sale = $this->getActiveSale();
        if (!$sale) {
            return [];
        }

        $productModel = new ProductModel();
        $items = $this->flashItemModel
            ->select('flash_sale_items.*, products.name, products.price as original_price, products.stock, products.sold_count, products.cashback_percent, seller_profiles.store_name, (SELECT image_path FROM product_images WHERE product_images.product_id = products.id ORDER BY is_primary DESC, id ASC LIMIT 1) as primary_image')
            ->join('products', 'products.id = flash_sale_items.product_id')
            ->join('seller_profiles', 'seller_profiles.user_id = products.seller_id', 'left')
            ->where('flash_sale_items.flash_sale_id', $sale['id'])
            ->where('products.status', 'active')
            ->findAll($limit);

        foreach ($items as &$item) {
            $orig = (float) $item['original_price'];
            $salePrice = (float) $item['sale_price'];
            $item['discount_pct'] = $orig > 0 ? round((($orig - $salePrice) / $orig) * 100) : 0;
        }
        unset($item);

        return $items;
    }
}
