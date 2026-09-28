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
}
