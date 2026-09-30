<?php

namespace App\Models;

use CodeIgniter\Model;

class FlashSaleItemModel extends Model
{
    protected $table         = 'flash_sale_items';
    protected $primaryKey    = 'id';
    protected $returnType    = 'array';
    protected $allowedFields = ['flash_sale_id', 'product_id', 'seller_id', 'sale_price', 'status', 'source'];
    protected $useTimestamps = true;
    protected $updatedField  = '';
}
