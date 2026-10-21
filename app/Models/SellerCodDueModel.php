<?php

namespace App\Models;

use CodeIgniter\Model;

class SellerCodDueModel extends Model
{
    protected $table         = 'seller_cod_dues';
    protected $primaryKey    = 'id';
    protected $returnType    = 'array';
    protected $useTimestamps = true;
    protected $allowedFields = [
        'seller_id',
        'order_id',
        'shipment_id',
        'goods_amount',
        'shipping_amount',
        'commission_amount',
        'cashback_amount',
        'due_amount',
        'paid_amount',
        'collected_cash',
        'payment_method',
        'fulfill_by',
        'status',
        'paid_at',
        'paid_note',
    ];
}
