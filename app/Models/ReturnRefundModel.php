<?php

namespace App\Models;

use CodeIgniter\Model;

class ReturnRefundModel extends Model
{
    protected $table            = 'returns_refunds';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $protectFields    = true;
    protected $allowedFields    = [
        'order_id',
        'order_item_id',
        'user_id',
        'reason',
        'customer_note',
        'admin_note',
        'refund_amount',
        'status',
        'processed_at',
    ];

    protected $useTimestamps = true;
    protected $dateFormat    = 'datetime';
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';

    public function getAllWithDetails(): array
    {
        return $this->select('returns_refunds.*, orders.order_number, users.name as customer_name, users.phone as customer_phone')
            ->join('orders', 'orders.id = returns_refunds.order_id')
            ->join('users', 'users.id = returns_refunds.user_id')
            ->orderBy('returns_refunds.id', 'DESC')
            ->findAll();
    }
}
