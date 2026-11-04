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
        'gift_wrap_amount',
        'gift_hide_price',
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
        $addrCols = 'addresses.recipient_name, addresses.phone as recipient_phone, addresses.street_address, addresses.city, addresses.province, addresses.postal_code';
        try {
            $names = $this->db->getFieldNames('addresses');
            foreach (['alternate_phone', 'area', 'landmark'] as $col) {
                if (in_array($col, $names, true)) {
                    $addrCols .= ', addresses.' . $col;
                }
            }
        } catch (\Throwable $e) {
            // Live DB without extra address columns still loads the order.
        }

        $payCols = 'payments.payment_method, payments.status as payment_status, payments.transaction_ref';
        try {
            $pnames = $this->db->getFieldNames('payments');
            if (in_array('amount', $pnames, true)) {
                $payCols .= ', payments.amount as payment_amount';
            }
            if (in_array('paid_at', $pnames, true)) {
                $payCols .= ', payments.paid_at';
            }
        } catch (\Throwable $e) {
        }

        $order = $this->select('orders.*, users.name as customer_name, users.email as customer_email, users.phone as customer_phone, ' . $addrCols . ', ' . $payCols)
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
