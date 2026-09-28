<?php

namespace App\Models;

use CodeIgniter\Model;

class StoreFollowModel extends Model
{
    protected $table         = 'store_follows';
    protected $primaryKey    = 'id';
    protected $returnType    = 'array';
    protected $allowedFields = ['user_id', 'seller_id'];
    protected $useTimestamps = true;
    protected $updatedField  = '';

    public function toggle(int $userId, int $sellerId): bool
    {
        $row = $this->where('user_id', $userId)->where('seller_id', $sellerId)->first();
        if ($row) {
            $this->delete($row['id']);
            return false;
        }
        $this->insert(['user_id' => $userId, 'seller_id' => $sellerId]);
        return true;
    }

    public function isFollowing(int $userId, int $sellerId): bool
    {
        return (bool) $this->where('user_id', $userId)->where('seller_id', $sellerId)->first();
    }

    public function followerCount(int $sellerId): int
    {
        return $this->where('seller_id', $sellerId)->countAllResults();
    }
}
