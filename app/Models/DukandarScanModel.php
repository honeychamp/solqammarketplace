<?php

namespace App\Models;

use CodeIgniter\Model;

class DukandarScanModel extends Model
{
    protected $table         = 'dukandar_scans';
    protected $primaryKey    = 'id';
    protected $returnType    = 'array';
    protected $protectFields = true;
    protected $allowedFields = [
        'dukandar_id',
        'customer_user_id',
        'card_code',
        'pan',
        'bill_amount',
        'credit_percent',
        'credit_amount',
        'paid_amount',
        'queued_amount',
        'status',
        'proof_path',
        'proof_note',
        'submitted_at',
        'released_at',
        'rejected_at',
        'admin_note',
    ];
    protected $useTimestamps = true;
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';
}
