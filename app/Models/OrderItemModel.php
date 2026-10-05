<?php

namespace App\Models;

use CodeIgniter\Model;

class OrderItemModel extends Model
{
    protected $table            = 'order_items';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $protectFields    = true;
    protected $allowedFields    = [
        'order_id',
        'product_id',
        'variant_id',
        'seller_id',
        'product_name',
        'variant_label',
        'price',
        'quantity',
        'subtotal',
        'commission_amount',
        'cashback_percent',
        'cashback_amount',
        'fulfillment_status',
    ];

    protected $useTimestamps = true;
    protected $dateFormat    = 'datetime';
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';

    public function getOrderItems(int $orderId): array
    {
        return $this->select('order_items.*, seller_profiles.store_name, (SELECT image_path FROM product_images WHERE product_images.product_id = order_items.product_id ORDER BY is_primary DESC, id ASC LIMIT 1) as product_image')
            ->join('seller_profiles', 'seller_profiles.user_id = order_items.seller_id', 'left')
            ->where('order_items.order_id', $orderId)
            ->findAll();
    }

    public function getSellerOrderItems(int $sellerId): array
    {
        return $this->select('order_items.*, orders.order_number, orders.status as order_status, orders.created_at as order_date, orders.user_id as customer_id, orders.wallet_amount_used, orders.final_payable, users.name as customer_name, users.phone as customer_phone')
            ->join('orders', 'orders.id = order_items.order_id')
            ->join('users', 'users.id = orders.user_id')
            ->where('order_items.seller_id', $sellerId)
            ->orderBy('order_items.id', 'DESC')
            ->findAll();
    }

    /**
     * Unique buyers per seller user id (order_items.seller_id).
     *
     * @param list<int> $sellerUserIds
     * @return array<int, int>
     */
    public function countCustomersBySellerIds(array $sellerUserIds): array
    {
        $sellerUserIds = array_values(array_unique(array_filter(array_map('intval', $sellerUserIds))));
        if ($sellerUserIds === []) {
            return [];
        }

        try {
            $rows = $this->db->table('order_items')
                ->select('order_items.seller_id, COUNT(DISTINCT orders.user_id) AS customer_count')
                ->join('orders', 'orders.id = order_items.order_id')
                ->whereIn('order_items.seller_id', $sellerUserIds)
                ->groupBy('order_items.seller_id')
                ->get()
                ->getResultArray();
        } catch (\Throwable $e) {
            log_message('error', 'Seller customer counts: ' . $e->getMessage());

            return [];
        }

        $map = [];
        foreach ($rows as $row) {
            $map[(int) $row['seller_id']] = (int) $row['customer_count'];
        }

        return $map;
    }

    /**
     * Distinct buyers who purchased from this seller, with spend totals.
     *
     * @return list<array<string, mixed>>
     */
    public function getSellerBuyers(int $sellerId): array
    {
        if ($sellerId < 1) {
            return [];
        }

        $select = 'order_items.*, orders.user_id as customer_id, users.name as customer_name, users.phone as customer_phone, users.email as customer_email, users.status as customer_status';
        try {
            $this->builder()->resetQuery();
            $items = $this->select($select)
                ->join('orders', 'orders.id = order_items.order_id')
                ->join('users', 'users.id = orders.user_id')
                ->where('order_items.seller_id', $sellerId)
                ->orderBy('order_items.id', 'DESC')
                ->findAll();
        } catch (\Throwable $e) {
            log_message('error', 'Seller buyers: ' . $e->getMessage());

            return [];
        }

        $buyers = [];
        foreach ($items as $item) {
            $cid = (int) ($item['customer_id'] ?? 0);
            if ($cid <= 0) {
                continue;
            }
            if (! isset($buyers[$cid])) {
                $buyers[$cid] = [
                    'id'         => $cid,
                    'name'       => $item['customer_name'] ?? 'Customer',
                    'phone'      => $item['customer_phone'] ?? '',
                    'email'      => $item['customer_email'] ?? '',
                    'status'     => $item['customer_status'] ?? '',
                    'orders'     => 0,
                    'lines'      => 0,
                    'order_ids'  => [],
                    'goods'      => 0.0,
                    'commission' => 0.0,
                    'cashback'   => 0.0,
                ];
            }
            $buyers[$cid]['lines']++;
            $oid = (int) ($item['order_id'] ?? 0);
            if ($oid > 0) {
                $buyers[$cid]['order_ids'][$oid] = true;
            }
            $buyers[$cid]['goods'] += (float) ($item['subtotal'] ?? 0);
            $buyers[$cid]['commission'] += (float) ($item['commission_amount'] ?? 0);
            $buyers[$cid]['cashback'] += function_exists('item_cashback')
                ? (float) item_cashback($item)
                : (float) ($item['cashback_amount'] ?? 0);
        }

        foreach ($buyers as &$buyer) {
            $buyer['orders'] = count($buyer['order_ids']);
            unset($buyer['order_ids']);
        }
        unset($buyer);

        return array_values($buyers);
    }
}
