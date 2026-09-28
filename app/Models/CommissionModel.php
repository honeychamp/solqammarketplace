<?php

namespace App\Models;

use CodeIgniter\Model;

class CommissionModel extends Model
{
    protected $table            = 'commissions';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $protectFields    = true;
    protected $allowedFields    = [
        'name',
        'percentage',
        'is_active',
        'updated_by',
    ];

    protected $useTimestamps = true;
    protected $dateFormat    = 'datetime';
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';

    public function getActiveRule(): array
    {
        $rule = $this->where('is_active', 1)->first();
        if (!$rule) {
            return [
                'id' => 0,
                'name' => 'Default 10% Commission',
                'percentage' => 10.00,
                'is_active' => 1,
            ];
        }
        return $rule;
    }
}
