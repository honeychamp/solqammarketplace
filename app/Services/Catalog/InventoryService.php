<?php

namespace App\Services\Catalog;

use App\Models\ProductModel;

class InventoryService
{
    public const LOW_STOCK = 5;

    /**
     * @param array{filter?:string,mine?:bool,admin_id?:int,q?:string} $opts
     * @return array{rows:list<array>,summary:array,filter:string,q:string,mine:bool}
     */
    public function build(?int $sellerId, array $opts = []): array
    {
        $filter = (string) ($opts['filter'] ?? 'all');
        if (! in_array($filter, ['all', 'oos', 'low', 'in'], true)) {
            $filter = 'all';
        }
        $mine     = ! empty($opts['mine']);
        $adminId  = (int) ($opts['admin_id'] ?? 0);
        $q        = trim((string) ($opts['q'] ?? ''));
        $db       = \Config\Database::connect();
        $model    = new ProductModel();
        $hasCost  = $this->hasColumn($db, 'products', 'product_cost');
        $hasSold  = $this->hasColumn($db, 'products', 'sold_count');

        $builder = $model
            ->select('products.*, categories.name as category_name, seller_profiles.store_name, (SELECT image_path FROM product_images WHERE product_images.product_id = products.id ORDER BY is_primary DESC, id ASC LIMIT 1) as primary_image')
            ->join('categories', 'categories.id = products.category_id', 'left')
            ->join('seller_profiles', 'seller_profiles.user_id = products.seller_id', 'left');

        if ($sellerId !== null && $sellerId > 0) {
            $builder->where('products.seller_id', $sellerId);
        } elseif ($mine && $adminId > 0) {
            $builder->where('products.seller_id', $adminId);
        }

        if ($q !== '') {
            $tokens = preg_split('/[\s,\/]+/u', $q) ?: [$q];
            $used   = false;
            foreach ($tokens as $token) {
                $token = trim((string) $token);
                if (mb_strlen($token) < 2) {
                    continue;
                }
                $used = true;
                $builder->groupStart()
                    ->like('products.name', $token)
                    ->orLike('products.sku', $token)
                    ->orLike('products.brand', $token)
                    ->orLike('categories.name', $token)
                    ->orLike('seller_profiles.store_name', $token)
                    ->groupEnd();
            }
            if (! $used) {
                $builder->groupStart()
                    ->like('products.name', $q)
                    ->orLike('products.sku', $q)
                    ->groupEnd();
            }
        }

        $all = $builder
            ->orderBy('products.stock', 'ASC')
            ->orderBy('products.id', 'DESC')
            ->findAll();

        $openMap     = $this->openOrderQty($db, $sellerId, $mine ? $adminId : null);
        $variantMap  = $this->variantsByProduct($db, array_map(static fn ($r) => (int) ($r['id'] ?? 0), $all));

        $summary = [
            'skus'         => 0,
            'units'        => 0,
            'oos'          => 0,
            'low'          => 0,
            'in_stock'     => 0,
            'inactive'     => 0,
            'sold'         => 0,
            'retail_value' => 0.0,
            'cost_value'   => 0.0,
            'open_qty'     => 0,
            'variant_skus' => 0,
        ];

        $rows = [];
        foreach ($all as $p) {
            $pid    = (int) ($p['id'] ?? 0);
            $stock  = (int) ($p['stock'] ?? 0);
            $price  = (float) ($p['price'] ?? 0);
            $cost   = $hasCost ? (float) ($p['product_cost'] ?? 0) : 0.0;
            $sold   = $hasSold ? (int) ($p['sold_count'] ?? 0) : 0;
            $open   = (int) ($openMap[$pid] ?? 0);
            $vars   = $variantMap[$pid] ?? [];
            $vStock = 0;
            foreach ($vars as $v) {
                $vStock += (int) ($v['stock'] ?? 0);
            }

            $band = 'in';
            if ($stock <= 0) {
                $band = 'oos';
            } elseif ($stock <= self::LOW_STOCK) {
                $band = 'low';
            }

            $summary['skus']++;
            $summary['units'] += $stock;
            $summary['sold'] += $sold;
            $summary['retail_value'] += $stock * $price;
            $summary['cost_value'] += $stock * $cost;
            $summary['open_qty'] += $open;
            $summary['variant_skus'] += count($vars);
            if ($band === 'oos') {
                $summary['oos']++;
            } elseif ($band === 'low') {
                $summary['low']++;
            } else {
                $summary['in_stock']++;
            }
            if (($p['status'] ?? '') !== 'active') {
                $summary['inactive']++;
            }

            if ($filter === 'oos' && $band !== 'oos') {
                continue;
            }
            if ($filter === 'low' && $band !== 'low') {
                continue;
            }
            if ($filter === 'in' && $band !== 'in') {
                continue;
            }

            $p['stock']           = $stock;
            $p['price']           = $price;
            $p['product_cost']    = $cost;
            $p['sold_count']      = $sold;
            $p['open_qty']        = $open;
            $p['variants']        = $vars;
            $p['variant_stock']   = $vStock;
            $p['retail_value']    = $stock * $price;
            $p['cost_value']      = $stock * $cost;
            $p['stock_band']      = $band;
            $p['weight']          = (float) ($p['weight'] ?? 0);
            $p['packing_cost']    = (float) ($p['packing_cost'] ?? 0);
            $rows[]               = $p;
        }

        return [
            'rows'    => $rows,
            'summary' => $summary,
            'filter'  => $filter,
            'q'       => $q,
            'mine'    => $mine,
        ];
    }

    /**
     * @param list<int> $productIds
     * @return array<int, list<array>>
     */
    protected function variantsByProduct($db, array $productIds): array
    {
        $productIds = array_values(array_unique(array_filter($productIds)));
        if ($productIds === [] || ! $this->tableExists($db, 'product_variants')) {
            return [];
        }
        try {
            $rows = $db->table('product_variants')
                ->whereIn('product_id', $productIds)
                ->orderBy('id', 'ASC')
                ->get()
                ->getResultArray();
        } catch (\Throwable $e) {
            return [];
        }
        $map = [];
        foreach ($rows as $row) {
            $pid = (int) ($row['product_id'] ?? 0);
            $map[$pid][] = $row;
        }

        return $map;
    }

    /**
     * @return array<int,int>
     */
    protected function openOrderQty($db, ?int $sellerId, ?int $mineSellerId): array
    {
        if (! $this->tableExists($db, 'order_items')) {
            return [];
        }
        try {
            $b = $db->table('order_items oi')->select('oi.product_id, SUM(oi.quantity) as qty');
            if ($this->hasColumn($db, 'order_items', 'fulfillment_status')) {
                $b->groupStart()
                    ->whereNotIn('oi.fulfillment_status', ['delivered', 'cancelled', 'returned'])
                    ->orWhere('oi.fulfillment_status', null)
                    ->orWhere('oi.fulfillment_status', '')
                    ->groupEnd();
            } else {
                $b->join('orders o', 'o.id = oi.order_id')
                    ->whereNotIn('o.status', ['delivered', 'cancelled', 'returned']);
            }
            if ($sellerId !== null && $sellerId > 0) {
                $b->where('oi.seller_id', $sellerId);
            } elseif ($mineSellerId !== null && $mineSellerId > 0) {
                $b->where('oi.seller_id', $mineSellerId);
            }
            $rows = $b->groupBy('oi.product_id')->get()->getResultArray();
        } catch (\Throwable $e) {
            return [];
        }
        $map = [];
        foreach ($rows as $row) {
            $map[(int) $row['product_id']] = (int) $row['qty'];
        }

        return $map;
    }

    protected function hasColumn($db, string $table, string $column): bool
    {
        try {
            return $db->tableExists($table) && $db->fieldExists($column, $table);
        } catch (\Throwable $e) {
            return false;
        }
    }

    protected function tableExists($db, string $table): bool
    {
        try {
            return $db->tableExists($table);
        } catch (\Throwable $e) {
            return false;
        }
    }
}
