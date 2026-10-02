<?php

namespace App\Models;

use CodeIgniter\Model;

class CartModel extends Model
{
    protected $table            = 'carts';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $protectFields    = true;
    protected $allowedFields    = [
        'user_id',
        'session_id',
    ];

    protected $useTimestamps = true;
    protected $dateFormat    = 'datetime';
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';

    public function getOrCreateCart(?int $userId, ?string $sessionId): array
    {
        try {
            return $this->findOrCreateCart($userId, $sessionId);
        } catch (\Throwable $e) {
            log_message('error', 'Cart: ' . $e->getMessage());

            return ['id' => 0, 'user_id' => $userId, 'session_id' => $sessionId];
        }
    }

    protected function findOrCreateCart(?int $userId, ?string $sessionId): array
    {
        if ($userId) {
            $cart = $this->where('user_id', $userId)->first();
            if (!$cart && $sessionId) {
                // Check if there was a guest cart to migrate
                $cart = $this->where('session_id', $sessionId)->where('user_id', null)->first();
                if ($cart) {
                    $this->update($cart['id'], ['user_id' => $userId]);
                    $cart['user_id'] = $userId;
                    return $cart;
                }
            }
            if (!$cart) {
                $id = $this->insert(['user_id' => $userId, 'session_id' => $sessionId]);
                return $this->find($id);
            }
            return $cart;
        }

        if ($sessionId) {
            $cart = $this->where('session_id', $sessionId)->first();
            if (!$cart) {
                $id = $this->insert(['session_id' => $sessionId]);
                return $this->find($id);
            }
            return $cart;
        }

        $id = $this->insert([]);
        return $this->find($id);
    }
}
