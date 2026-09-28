<?php

namespace App\Models;

use CodeIgniter\Model;

class OtpLogModel extends Model
{
    protected $table            = 'otp_logs';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $protectFields    = true;
    protected $allowedFields    = [
        'phone',
        'otp_code',
        'purpose',
        'is_used',
        'expires_at',
    ];

    protected $useTimestamps = true;
    protected $dateFormat    = 'datetime';
    protected $createdField  = 'created_at';
    protected $updatedField  = '';
}
