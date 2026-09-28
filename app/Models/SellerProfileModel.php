<?php

namespace App\Models;

use CodeIgniter\Model;

class SellerProfileModel extends Model
{
    protected $table            = 'seller_profiles';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $protectFields    = true;
    protected $allowedFields    = [
        'user_id',
        'store_name',
        'business_name',
        'cnic_or_ntn',
        'business_address',
        'city',
        'bank_account_title',
        'bank_name',
        'account_number_or_iban',
        'approval_status',
        'rejection_reason',
    ];

    protected $useTimestamps = true;
    protected $dateFormat    = 'datetime';
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';

    public function getByUserId(int $userId): ?array
    {
        return $this->where('user_id', $userId)->first();
    }

    public function getPendingSellers(): array
    {
        return $this->select('seller_profiles.*, users.name as owner_name, users.email as owner_email, users.phone as owner_phone')
            ->join('users', 'users.id = seller_profiles.user_id')
            ->where('seller_profiles.approval_status', 'pending')
            ->findAll();
    }

    public function getAllWithUser(): array
    {
        return $this->select('seller_profiles.*, users.name as owner_name, users.email as owner_email, users.phone as owner_phone, users.status as user_status')
            ->join('users', 'users.id = seller_profiles.user_id')
            ->orderBy('seller_profiles.id', 'DESC')
            ->findAll();
    }

    /**
     * Returns each seller with their total orders, unique customer count, and revenue.
     */
    public function getSellerStats(): array
    {
        $db = \Config\Database::connect();
        return $db->query("
            SELECT
                sp.id,
                sp.user_id,
                sp.store_name,
                sp.approval_status,
                u.name  AS seller_name,
                u.email AS seller_email,
                u.created_at AS joined_at,
                COUNT(DISTINCT oi.order_id) AS total_orders,
                COUNT(DISTINCT o.user_id)   AS total_customers,
                COALESCE(SUM(oi.subtotal), 0) AS total_revenue
            FROM seller_profiles sp
            JOIN users u ON u.id = sp.user_id
            LEFT JOIN order_items oi ON oi.seller_id = sp.user_id
            LEFT JOIN orders o ON o.id = oi.order_id
            GROUP BY sp.id, sp.user_id, sp.store_name, sp.approval_status, u.name, u.email, u.created_at
            ORDER BY total_revenue DESC
        ")->getResultArray();
    }
}

