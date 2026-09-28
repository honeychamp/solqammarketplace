<?php

namespace App\Models;

use CodeIgniter\Model;

class MessageModel extends Model
{
    protected $table         = 'messages';
    protected $primaryKey    = 'id';
    protected $returnType    = 'array';
    protected $allowedFields = ['conversation_id', 'sender_id', 'body'];
    protected $useTimestamps = true;
    protected $updatedField  = '';
}
