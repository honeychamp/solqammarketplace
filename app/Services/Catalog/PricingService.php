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

    public function getActiveSale(?string $type = 'flash'): ?array
    {
        return $this->flashSaleModel->getActive($type);
    }

    public function getFlashPriceMap(): array
    {
        $campaigns = $this->flashSaleModel->getLiveCampaigns();
        if ($campaigns === []) {
            return [];
        }
        $ids = array_column($campaigns, 'id');
        $items = $this->flashItemModel
            ->whereIn('flash_sale_id', $ids)
            ->where('status', 'approved')
            ->findAll();
        $map = [];
        foreach ($items as $item) {
            $pid = (int) $item['product_id'];
            $price = (float) $item['sale_price'];
            if (! isset($map[$pid]) || $price < $map[$pid]) {
                $map[$pid] = $price;
            }
        }

        return $map;
    }

    public function resolveUnitPrice(array $product, ?array $variant = null): float
    {
        $base = $variant && ! empty($variant['price'])
            ? (float) $variant['price']
            : (float) $product['price'];

        $flashMap = $this->getFlashPriceMap();
        $productId = (int) $product['id'];
        if (isset($flashMap[$productId])) {
            return min($base, $flashMap[$productId]);
        }

        return $base;
    }

    public function getCampaignProducts(string $type = 'flash', int $limit = 8): array
    {
        $sale = $this->flashSaleModel->getActive($type);
        if (! $sale) {
            return [];
        }

        $db = \Config\Database::connect();
        $cashback = $db->fieldExists('cashback_percent', 'products')
            ? 'products.cashback_percent'
            : '0 as cashback_percent';
        $sold = $db->fieldExists('sold_count', 'products')
            ? 'products.sold_count'
            : '0 as sold_count';
        $items = $this->flashItemModel
            ->select("flash_sale_items.*, products.name, products.price as original_price, products.stock, {$sold}, {$cashback}, seller_profiles.store_name, (SELECT image_path FROM product_images WHERE product_images.product_id = products.id ORDER BY is_primary DESC, id ASC LIMIT 1) as primary_image")
            ->join('products', 'products.id = flash_sale_items.product_id')
            ->join('seller_profiles', 'seller_profiles.user_id = products.seller_id', 'left')
            ->where('flash_sale_items.flash_sale_id', $sale['id'])
            ->where('flash_sale_items.status', 'approved')
            ->where('products.status', 'active')
            ->groupStart()
                ->where('seller_profiles.approval_status', 'approved')
                ->orWhere('seller_profiles.id', null)
            ->groupEnd()
            ->findAll($limit);

        foreach ($items as &$item) {
            $orig = (float) $item['original_price'];
            $salePrice = (float) $item['sale_price'];
            $item['discount_pct'] = $orig > 0 ? round((($orig - $salePrice) / $orig) * 100) : 0;
        }
        unset($item);

        return $items;
    }

    public function getFlashProducts(int $limit = 8): array
    {
        return $this->getCampaignProducts('flash', $limit);
    }
}
