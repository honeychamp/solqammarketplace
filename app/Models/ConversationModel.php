<?php

namespace App\Models;

use CodeIgniter\Model;

class ConversationModel extends Model
{
    protected $table         = 'conversations';
    protected $primaryKey    = 'id';
    protected $returnType    = 'array';
    protected $allowedFields = ['customer_id', 'seller_id', 'product_id', 'last_message_at'];
    protected $useTimestamps = true;

    public function findOrCreate(int $customerId, int $sellerId, ?int $productId = null): array
    {
        $row = $this->where('customer_id', $customerId)->where('seller_id', $sellerId)->first();
        if ($row) {
            return $row;
        }
        $id = $this->insert([
            'customer_id'     => $customerId,
            'seller_id'       => $sellerId,
            'product_id'      => $productId,
            'last_message_at' => date('Y-m-d H:i:s'),
        ]);
        return $this->find($id);
    }

    public function forUser(int $userId, string $role): array
    {
        $builder = $this->select('conversations.*, buyer.name as customer_name, seller.name as seller_name, seller_profiles.store_name, products.name as product_name')
            ->join('users as buyer', 'buyer.id = conversations.customer_id', 'left')
            ->join('users as seller', 'seller.id = conversations.seller_id', 'left')
            ->join('seller_profiles', 'seller_profiles.user_id = conversations.seller_id', 'left')
            ->join('products', 'products.id = conversations.product_id', 'left')
            ->orderBy('conversations.last_message_at', 'DESC');

        if ($role === 'seller') {
            $builder->where('conversations.seller_id', $userId);
        } else {
            $builder->where('conversations.customer_id', $userId);
        }

        return $builder->findAll();
    }
}
