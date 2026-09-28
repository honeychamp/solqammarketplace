<?php

namespace App\Models;

use CodeIgniter\Model;

class FlashSaleItemModel extends Model
{
    protected $table         = 'flash_sale_items';
    protected $primaryKey    = 'id';
    protected $returnType    = 'array';
    protected $allowedFields = ['flash_sale_id', 'product_id', 'sale_price'];
    protected $useTimestamps = true;
    protected $updatedField  = '';
}
