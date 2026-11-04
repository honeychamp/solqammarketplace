<?php

namespace App\Services\Catalog;

use Config\Database;

class CatalogPriceAdjuster
{
    /**
     * Add or subtract a rupee amount on every matching product, SKU, and flash price.
     *
     * @return array{count:int,delta:float}
     */
    public function adjustByRupees(float $amount, string $direction, ?int $sellerId = null): array
    {
        $rupees = round(abs($amount), 2);
        if ($rupees < 0.01) {
            throw new \RuntimeException('Enter rupees greater than 0.');
        }
        $delta = strtolower($direction) === 'down' ? -$rupees : $rupees;
        $dSql  = sprintf('%0.2F', $delta);

        $db = Database::connect();
        $soft = false;
        try {
            $soft = $db->fieldExists('deleted_at', 'products');
        } catch (\Throwable $e) {
        }

        $countQ = $db->table('products');
        if ($sellerId !== null && $sellerId > 0) {
            $countQ->where('seller_id', $sellerId);
        }
        if ($soft) {
            $countQ->where('deleted_at', null);
        }
        $count = (int) $countQ->countAllResults();
        if ($count < 1) {
            throw new \RuntimeException('No products to update.');
        }

        $db->transStart();

        $prod = $db->table('products');
        if ($sellerId !== null && $sellerId > 0) {
            $prod->where('seller_id', $sellerId);
        }
        if ($soft) {
            $prod->where('deleted_at', null);
        }
        $set = [
            'price'      => "GREATEST(1, ROUND(price + ({$dSql}), 2))",
            'updated_at' => "'" . date('Y-m-d H:i:s') . "'",
        ];
        try {
            if ($db->fieldExists('compare_at_price', 'products')) {
                $set['compare_at_price'] = "CASE WHEN compare_at_price IS NULL OR compare_at_price <= 0 THEN compare_at_price ELSE GREATEST(GREATEST(1, ROUND(price + ({$dSql}), 2)), ROUND(compare_at_price + ({$dSql}), 2)) END";
            }
        } catch (\Throwable $e) {
        }
        $prod->set($set, null, false)->update();

        $sellerSql = ($sellerId !== null && $sellerId > 0) ? ' AND p.seller_id = ' . (int) $sellerId : '';
        $softSql   = $soft ? ' AND p.deleted_at IS NULL' : '';

        if ($db->tableExists('product_variants')) {
            $db->query(
                "UPDATE product_variants v
                 INNER JOIN products p ON p.id = v.product_id
                 SET v.price = GREATEST(1, ROUND(v.price + ({$dSql}), 2))
                 WHERE 1=1{$sellerSql}{$softSql}"
            );
        }

        if ($db->tableExists('flash_sale_items')) {
            $db->query(
                "UPDATE flash_sale_items f
                 INNER JOIN products p ON p.id = f.product_id
                 SET f.sale_price = GREATEST(1, LEAST(p.price, ROUND(f.sale_price + ({$dSql}), 2)))
                 WHERE 1=1{$sellerSql}{$softSql}"
            );
        }

        $db->transComplete();
        if ($db->transStatus() === false) {
            throw new \RuntimeException('Price update failed. Try again.');
        }

        return ['count' => $count, 'delta' => $delta];
    }
}
