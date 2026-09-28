<?php

namespace App\Models;

use CodeIgniter\Model;

class WalletModel extends Model
{
    protected $table            = 'wallets';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $protectFields    = true;
    protected $allowedFields    = [
        'user_id',
    ];

    protected $useTimestamps = true;
    protected $dateFormat    = 'datetime';
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';

    public function getOrCreateForUser(int $userId): array
    {
        $wallet = $this->where('user_id', $userId)->first();
        if (!$wallet) {
            $db = \Config\Database::connect();
            $userExists = $db->table('users')->where('id', $userId)->countAllResults() > 0;
            if (!$userExists) {
                return ['id' => 0, 'user_id' => $userId];
            }
            $id = $this->insert(['user_id' => $userId]);
            $wallet = $this->find($id);
        }
        return $wallet ?? ['id' => 0, 'user_id' => $userId];
    }

    /**
     * Compute balance dynamically by summing ledger rows.
     * Never overwrites or stores a balance column.
     */
    public function calculateBalance(int $walletId): float
    {
        if ($walletId <= 0) {
            return 0.0;
        }

        $db = \Config\Database::connect();
        $row = $db->table('wallet_transactions')
            ->select("COALESCE(SUM(CASE WHEN type = 'credit' THEN amount ELSE -amount END), 0) as balance")
            ->where('wallet_id', $walletId)
            ->get()
            ->getRowArray();

        return (float) ($row['balance'] ?? 0.0);
    }
}
