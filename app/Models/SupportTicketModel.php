<?php

namespace App\Models;

use CodeIgniter\Model;

class SupportTicketModel extends Model
{
    protected $table         = 'support_tickets';
    protected $primaryKey    = 'id';
    protected $returnType    = 'array';
    protected $allowedFields = ['user_id', 'subject', 'message', 'status'];
    protected $useTimestamps = true;
}
