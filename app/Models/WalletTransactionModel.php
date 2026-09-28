<?php

namespace App\Models;

use CodeIgniter\Model;

class WalletTransactionModel extends Model
{
    protected $table            = 'wallet_transactions';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $protectFields    = true;
    protected $allowedFields    = [
        'wallet_id',
        'type',
        'amount',
        'reference_type',
        'reference_id',
        'description',
    ];

    protected $useTimestamps = true;
    protected $dateFormat    = 'datetime';
    protected $createdField  = 'created_at';
    protected $updatedField  = ''; // Append-only ledger; rows are never updated!

    public function getWalletHistory(int $walletId, int $limit = 50): array
    {
        return $this->where('wallet_id', $walletId)
            ->orderBy('id', 'DESC')
            ->findAll($limit);
    }
}
