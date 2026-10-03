<?php

namespace App\Models;

use CodeIgniter\Model;

class OrderModel extends Model
{
    protected $table            = 'orders';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $protectFields    = true;
    protected $allowedFields    = [
        'order_number',
        'user_id',
        'address_id',
        'total_amount',
        'discount_amount',
        'shipping_amount',
        'delivery_arrears',
        'coupon_id',
        'coupon_code',
        'coupon_discount',
        'wallet_amount_used',
        'pay_later_wallet',
        'pay_later_cleared',
        'final_payable',
        'commission_rate',
        'commission_amount',
        'cashback_amount',
        'status',
        'delivery_date',
        'notes',
        'tracking_number',
        'courier',
    ];

    protected $useTimestamps = true;
    protected $dateFormat    = 'datetime';
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';

    public function getCustomerOrders(int $userId): array
    {
        return $this->where('user_id', $userId)
            ->orderBy('id', 'DESC')
            ->findAll();
    }

    public function getOrderDetail(int $id): ?array
    {
        $order = $this->select('orders.*, users.name as customer_name, users.email as customer_email, users.phone as customer_phone, addresses.recipient_name, addresses.phone as recipient_phone, addresses.street_address, addresses.city, addresses.province, addresses.postal_code, payments.payment_method, payments.status as payment_status, payments.transaction_ref')
            ->join('users', 'users.id = orders.user_id')
            ->join('addresses', 'addresses.id = orders.address_id', 'left')
            ->join('payments', 'payments.order_id = orders.id', 'left')
            ->where('orders.id', $id)
            ->first();

        if ($order) {
            $itemModel = new OrderItemModel();
            $order['items'] = $itemModel->getOrderItems($id);
            try {
                $order['shipments'] = (new ShipmentModel())->where('order_id', $id)->findAll();
            } catch (\Throwable $e) {
                $order['shipments'] = [];
            }
            try {
                $order['pay_later'] = (new PayLaterApplicationModel())->where('order_id', $id)->first();
            } catch (\Throwable $e) {
                $order['pay_later'] = null;
            }
        }

        return $order;
    }
}
