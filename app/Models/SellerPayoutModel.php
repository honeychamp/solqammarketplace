<?php

namespace App\Models;

use CodeIgniter\Model;

class SellerPayoutModel extends Model
{
    protected $table         = 'seller_payouts';
    protected $primaryKey    = 'id';
    protected $returnType    = 'array';
    protected $allowedFields = ['seller_id', 'order_id', 'amount', 'status', 'paid_at'];
    protected $useTimestamps = true;
}
