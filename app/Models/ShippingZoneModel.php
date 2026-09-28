<?php

namespace App\Models;

use CodeIgniter\Model;

class ShippingZoneModel extends Model
{
    protected $table         = 'shipping_zones';
    protected $primaryKey    = 'id';
    protected $returnType    = 'array';
    protected $allowedFields = ['city', 'province', 'rate', 'free_above', 'eta_days'];
    protected $useTimestamps = true;
}
