<?php

namespace App\Controllers\Api\V1;

use App\Services\Wallet\WalletService;

class WalletController extends BaseApiController
{
    protected WalletService $walletService;

    public function __construct()
    {
        $this->walletService = new WalletService();
    }

    public function balance()
    {
        $user = $this->getAuthenticatedUser();
        if (!$user) {
            return $this->respondFail('Unauthorized.', null, 401);
        }

        $balance = $this->walletService->getBalance((int) $user['id']);

        return $this->respondSuccess([
            'balance'  => $balance,
            'currency' => 'PKR',
        ], 'Wallet balance calculated from ledger.');
    }

    public function transactions()
    {
        $user = $this->getAuthenticatedUser();
        if (!$user) {
            return $this->respondFail('Unauthorized.', null, 401);
        }

        $limit = (int) ($this->request->getGet('limit') ?? 50);
        $transactions = $this->walletService->getTransactions((int) $user['id'], $limit);
        $balance = $this->walletService->getBalance((int) $user['id']);

        return $this->respondSuccess([
            'balance'      => $balance,
            'transactions' => $transactions,
        ], 'Wallet transactions ledger retrieved.');
    }
}
