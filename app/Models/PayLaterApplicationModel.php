<?php

namespace App\Models;

use CodeIgniter\Model;

class PayLaterApplicationModel extends Model
{
    protected $table            = 'pay_later_applications';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $protectFields    = true;
    protected $allowedFields    = [
        'order_id',
        'user_id',
        'full_name',
        'cnic_number',
        'phone',
        'address_text',
        'cnic_front_path',
        'cnic_back_path',
        'utility_bill_path',
        'status',
    ];
    protected $useTimestamps = true;
    protected $dateFormat    = 'datetime';
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';
}
