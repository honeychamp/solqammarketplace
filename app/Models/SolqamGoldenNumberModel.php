<?php

namespace App\Models;

use CodeIgniter\Model;

class SolqamGoldenNumberModel extends Model
{
    protected $table         = 'solqam_golden_numbers';
    protected $primaryKey    = 'id';
    protected $returnType    = 'array';
    protected $protectFields = true;
    protected $allowedFields = ['member_type', 'serial', 'status', 'user_id', 'card_id', 'note'];
    protected $useTimestamps = true;
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';
}
