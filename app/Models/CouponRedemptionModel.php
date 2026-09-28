<?php

namespace App\Models;

use CodeIgniter\Model;

class CouponRedemptionModel extends Model
{
    protected $table         = 'coupon_redemptions';
    protected $primaryKey    = 'id';
    protected $returnType    = 'array';
    protected $allowedFields = ['coupon_id', 'user_id', 'order_id'];
    protected $useTimestamps = true;
    protected $updatedField  = '';
}
