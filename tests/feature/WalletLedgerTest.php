<?php

namespace Tests\Feature;

use App\Models\UserModel;
use App\Models\WalletModel;
use App\Models\WalletTransactionModel;
use App\Services\Wallet\WalletService;
use CodeIgniter\Test\CIUnitTestCase;
use RuntimeException;

/**
 * @internal
 */
final class WalletLedgerTest extends CIUnitTestCase
{
    protected WalletService $walletService;
    protected UserModel $userModel;
    protected WalletModel $walletModel;
    protected WalletTransactionModel $transactionModel;
    protected int $testUserId;

    protected function setUp(): void
    {
        parent::setUp();

        $this->walletService    = new WalletService();
        $this->userModel         = new UserModel();
        $this->walletModel       = new WalletModel();
        $this->transactionModel  = new WalletTransactionModel();

        // Create a dedicated test user
        $email = 'ledger_test_' . uniqid() . '@solqam.pk';
        $this->testUserId = (int) $this->userModel->insert([
            'name'          => 'Ledger Test Customer',
            'email'         => $email,
            'phone'         => '0300' . random_int(1000000, 9999999),
            'password_hash' => password_hash('secret123', PASSWORD_BCRYPT),
            'role'          => 'customer',
            'status'        => 'active',
            'is_verified'   => 1,
            'api_token'     => bin2hex(random_bytes(16)),
        ]);
    }

    protected function tearDown(): void
    {
        // Clean up test data
        $wallet = $this->walletModel->where('user_id', $this->testUserId)->first();
        if ($wallet) {
            $this->transactionModel->where('wallet_id', $wallet['id'])->delete();
            $this->walletModel->delete($wallet['id']);
        }
        $this->userModel->delete($this->testUserId, true);

        parent::tearDown();
    }

    public function testInitialWalletBalanceIsZero(): void
    {
        $balance = $this->walletService->getBalance($this->testUserId);
        $this->assertSame(0.0, $balance);
    }

    public function testCreditIncreasesComputedBalance(): void
    {
        $credited = $this->walletService->credit(
            $this->testUserId,
            1250.50,
            'cashback',
            101,
            'Cashback bonus'
        );

        $this->assertTrue($credited);
        $balance = $this->walletService->getBalance($this->testUserId);
        $this->assertSame(1250.50, $balance);
    }

    public function testDebitDecreasesComputedBalance(): void
    {
        // Credit 2000 first
        $this->walletService->credit($this->testUserId, 2000.00, 'adjustment', null, 'Initial credit');

        // Debit 750
        $debited = $this->walletService->debit($this->testUserId, 750.00, 'order_payment', 202, 'Order payment');
        $this->assertTrue($debited);

        $balance = $this->walletService->getBalance($this->testUserId);
        $this->assertSame(1250.00, $balance);
    }

    public function testDebitThrowsExceptionWhenBalanceInsufficient(): void
    {
        $this->expectException(RuntimeException::class);

        // Attempting to debit without balance
        $this->walletService->debit($this->testUserId, 500.00, 'order_payment', 303, 'Overdraft attempt');
    }

    public function testAppendOnlyLedgerIntegrity(): void
    {
        // Append 3 transactions
        $this->walletService->credit($this->testUserId, 100.00, 'cashback', 1, 'Txn 1');
        $this->walletService->credit($this->testUserId, 200.00, 'cashback', 2, 'Txn 2');
        $this->walletService->debit($this->testUserId, 50.00, 'order_payment', 3, 'Txn 3');

        $walletId = $this->walletService->getOrCreateWallet($this->testUserId);
        $count = $this->transactionModel->where('wallet_id', $walletId)->countAllResults();

        // Must be exactly 3 discrete immutable records
        $this->assertSame(3, $count);

        // Balance must be 100 + 200 - 50 = 250
        $balance = $this->walletService->getBalance($this->testUserId);
        $this->assertSame(250.00, $balance);
    }

    public function testDebitAllowNegativeGoesBelowZero(): void
    {
        $this->walletService->debit(
            $this->testUserId,
            350.00,
            'delivery_fee',
            404,
            'Failed delivery courier fee',
            true
        );

        $balance = $this->walletService->getBalance($this->testUserId);
        $this->assertSame(-350.00, $balance);
        $this->assertSame(350.00, $this->walletService->getOutstandingDebt($this->testUserId));
    }
}
