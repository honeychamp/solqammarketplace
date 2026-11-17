<?php

namespace App\Models;

use CodeIgniter\Model;

class SolqamGeoCityModel extends Model
{
    protected $table         = 'solqam_geo_cities';
    protected $primaryKey    = 'id';
    protected $returnType    = 'array';
    protected $protectFields = true;
    protected $allowedFields = ['division_id', 'code', 'name', 'is_active'];
    protected $useTimestamps = true;
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';
}
