<?php

namespace App\Models;

use CodeIgniter\Model;

class SolqamMerchantCategoryModel extends Model
{
    protected $table         = 'solqam_merchant_categories';
    protected $primaryKey    = 'id';
    protected $returnType    = 'array';
    protected $protectFields = true;
    protected $allowedFields = ['code', 'name', 'is_active'];
    protected $useTimestamps = true;
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';
}
