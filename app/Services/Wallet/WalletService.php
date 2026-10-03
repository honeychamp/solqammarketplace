<?php

namespace App\Services\Wallet;

use App\Models\WalletModel;
use App\Models\WalletTransactionModel;
use Config\Database;
use RuntimeException;

class WalletService
{
    protected WalletModel $walletModel;
    protected WalletTransactionModel $transactionModel;
    protected $db;

    public function __construct()
    {
        $this->walletModel      = new WalletModel();
        $this->transactionModel = new WalletTransactionModel();
        $this->db               = Database::connect();
    }

    /**
     * Get or create wallet for user and return wallet ID.
     */
    public function getOrCreateWallet(int $userId): int
    {
        $wallet = $this->walletModel->getOrCreateForUser($userId);
        return (int) $wallet['id'];
    }

    /**
     * Compute current balance strictly from the immutable ledger.
     * Balance = SUM(credits) - SUM(debits). Never read from a static column.
     */
    public function getBalance(int $userId): float
    {
        try {
            $walletId = $this->getOrCreateWallet($userId);
            if ($walletId <= 0) {
                return 0.0;
            }
            return $this->walletModel->calculateBalance($walletId);
        } catch (\Throwable $e) {
            return 0.0;
        }
    }

    /**
     * Append a CREDIT entry to the ledger.
     */
    public function credit(
        int $userId,
        float $amount,
        string $referenceType,
        ?int $referenceId,
        string $description
    ): bool {
        if ($amount <= 0) {
            return false;
        }

        $walletId = $this->getOrCreateWallet($userId);

        $this->db->transStart();

        $this->transactionModel->insert([
            'wallet_id'      => $walletId,
            'type'           => 'credit',
            'amount'         => round($amount, 2),
            'reference_type' => $referenceType,
            'reference_id'   => $referenceId,
            'description'    => $description,
            'created_at'     => date('Y-m-d H:i:s'),
        ]);

        $this->db->transComplete();

        return $this->db->transStatus();
    }

    /**
     * Unpaid failed-delivery charges sitting as a negative wallet balance.
     */
    public function getOutstandingDebt(int $userId): float
    {
        return max(0.0, round(-$this->getBalance($userId), 2));
    }

    /**
     * Append a DEBIT entry to the ledger.
     * Throws if balance is insufficient unless $allowNegative is true
     * (failed-delivery courier fee that the buyer still owes).
     */
    public function debit(
        int $userId,
        float $amount,
        string $referenceType,
        ?int $referenceId,
        string $description,
        bool $allowNegative = false
    ): bool {
        if ($amount <= 0) {
            return false;
        }

        $walletId = $this->getOrCreateWallet($userId);

        $this->db->transStart();

        $currentBalance = $this->walletModel->calculateBalance($walletId);
        if (! $allowNegative && $currentBalance < round($amount, 2)) {
            $this->db->transRollback();
            throw new RuntimeException("Insufficient wallet balance: Rs. {$currentBalance} available, Rs. {$amount} requested.");
        }

        $this->transactionModel->insert([
            'wallet_id'      => $walletId,
            'type'           => 'debit',
            'amount'         => round($amount, 2),
            'reference_type' => $referenceType,
            'reference_id'   => $referenceId,
            'description'    => $description,
            'created_at'     => date('Y-m-d H:i:s'),
        ]);

        $this->db->transComplete();

        return $this->db->transStatus();
    }

    /**
     * Retrieve transaction ledger records for user.
     */
    public function getTransactions(int $userId, int $limit = 50): array
    {
        $walletId = $this->getOrCreateWallet($userId);
        return $this->transactionModel->getWalletHistory($walletId, $limit);
    }
}
