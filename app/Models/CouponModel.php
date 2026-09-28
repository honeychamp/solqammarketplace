<?php

namespace App\Models;

use CodeIgniter\Model;

class CouponModel extends Model
{
    protected $table         = 'coupons';
    protected $primaryKey    = 'id';
    protected $returnType    = 'array';
    protected $allowedFields = ['code', 'type', 'value', 'min_order', 'max_uses', 'used_count', 'starts_at', 'expires_at', 'is_active'];
    protected $useTimestamps = true;
}
