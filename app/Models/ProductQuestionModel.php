<?php

namespace App\Models;

use CodeIgniter\Model;

class ProductQuestionModel extends Model
{
    protected $table         = 'product_questions';
    protected $primaryKey    = 'id';
    protected $returnType    = 'array';
    protected $allowedFields = ['product_id', 'user_id', 'question', 'answer', 'answered_by', 'answered_at'];
    protected $useTimestamps = true;
    protected $updatedField  = '';

    public function forProduct(int $productId): array
    {
        return $this->select('product_questions.*, users.name as asker_name')
            ->join('users', 'users.id = product_questions.user_id', 'left')
            ->where('product_questions.product_id', $productId)
            ->orderBy('product_questions.id', 'DESC')
            ->findAll();
    }
}
