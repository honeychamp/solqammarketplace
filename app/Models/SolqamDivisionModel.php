<?php

namespace App\Models;

use CodeIgniter\Model;

class SolqamDivisionModel extends Model
{
    protected $table         = 'solqam_divisions';
    protected $primaryKey    = 'id';
    protected $returnType    = 'array';
    protected $protectFields = true;
    protected $allowedFields = ['code', 'name', 'province_name', 'is_active'];
    protected $useTimestamps = true;
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';
}
