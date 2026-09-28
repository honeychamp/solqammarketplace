<?php

namespace Tests\Feature;

use App\Models\AddressModel;
use App\Models\CartItemModel;
use App\Models\CartModel;
use App\Models\OrderItemModel;
use App\Models\OrderModel;
use App\Models\ProductModel;
use App\Models\UserModel;
use App\Models\WalletModel;
use App\Models\WalletTransactionModel;
use App\Services\Order\OrderService;
use App\Services\Wallet\WalletService;
use CodeIgniter\Test\CIUnitTestCase;

/**
 * @internal
 *
 * Tests the complete checkout flow including:
 * - Cart → Order conversion
 * - COD payment path
 * - Wallet deduction at checkout
 * - Stock decrement
 * - Order record integrity
 */
final class CheckoutFlowTest extends CIUnitTestCase
{
    protected UserModel $userModel;
    protected ProductModel $productModel;
    protected CartModel $cartModel;
    protected CartItemModel $cartItemModel;
    protected AddressModel $addressModel;
    protected OrderModel $orderModel;
    protected OrderItemModel $orderItemModel;
    protected WalletModel $walletModel;
    protected WalletTransactionModel $transactionModel;
    protected WalletService $walletService;
    protected OrderService $orderService;

    protected int $testCustomerId;
    protected int $testSellerId;
    protected int $testProductId;
    protected int $testAddressId;
    protected int $initialStock = 20;

    protected function setUp(): void
    {
        parent::setUp();

        $this->userModel        = new UserModel();
        $this->productModel     = new ProductModel();
        $this->cartModel        = new CartModel();
        $this->cartItemModel    = new CartItemModel();
        $this->addressModel     = new AddressModel();
        $this->orderModel       = new OrderModel();
        $this->orderItemModel   = new OrderItemModel();
        $this->walletModel      = new WalletModel();
        $this->transactionModel = new WalletTransactionModel();
        $this->walletService    = new WalletService();
        $this->orderService     = new OrderService();

        // Create a test seller
        $suffix = uniqid();
        $this->testSellerId = (int) $this->userModel->insert([
            'name'          => 'Checkout Test Seller',
            'email'         => "seller_{$suffix}@checkout.test",
            'phone'         => '0321' . random_int(1000000, 9999999),
            'password_hash' => password_hash('secret123', PASSWORD_BCRYPT),
            'role'          => 'seller',
            'status'        => 'active',
            'is_verified'   => 1,
            'api_token'     => bin2hex(random_bytes(16)),
        ]);

        // Create a test customer
        $this->testCustomerId = (int) $this->userModel->insert([
            'name'          => 'Checkout Test Customer',
            'email'         => "customer_{$suffix}@checkout.test",
            'phone'         => '0311' . random_int(1000000, 9999999),
            'password_hash' => password_hash('secret123', PASSWORD_BCRYPT),
            'role'          => 'customer',
            'status'        => 'active',
            'is_verified'   => 1,
            'api_token'     => bin2hex(random_bytes(16)),
        ]);

        // Create a test product
        $this->testProductId = (int) $this->productModel->insert([
            'seller_id'   => $this->testSellerId,
            'category_id' => 1,
            'name'        => 'Test Widget',
            'slug'        => 'test-widget-' . $suffix,
            'description' => 'A widget for testing.',
            'price'       => 1500.00,
            'stock'       => $this->initialStock,
            'status'      => 'active',
        ]);

        // Create a shipping address for the customer
        $this->testAddressId = (int) $this->addressModel->insert([
            'user_id'        => $this->testCustomerId,
            'recipient_name' => 'Test Customer',
            'phone'          => '0311' . random_int(1000000, 9999999),
            'street_address' => '123 Test Street',
            'city'           => 'Lahore',
            'province'       => 'Punjab',
            'postal_code'    => '54000',
            'is_default'     => 1,
        ]);
    }

    protected function tearDown(): void
    {
        $db = \Config\Database::connect();

        // Remove orders placed by test customer (orders.user_id is the FK column)
        $orders = $this->orderModel->where('user_id', $this->testCustomerId)->findAll();
        foreach ($orders as $order) {
            $this->orderItemModel->where('order_id', $order['id'])->delete();
            $db->table('payments')->where('order_id', $order['id'])->delete();
        }
        $this->orderModel->where('user_id', $this->testCustomerId)->delete();

        // Wallet cleanup
        $wallet = $this->walletModel->where('user_id', $this->testCustomerId)->first();
        if ($wallet) {
            $this->transactionModel->where('wallet_id', $wallet['id'])->delete();
            $this->walletModel->delete($wallet['id']);
        }

        // Cart cleanup
        $cart = $this->cartModel->where('user_id', $this->testCustomerId)->first();
        if ($cart) {
            $this->cartItemModel->where('cart_id', $cart['id'])->delete();
            $this->cartModel->delete($cart['id']);
        }

        // Address cleanup
        $this->addressModel->delete($this->testAddressId, true);

        // Product cleanup
        $this->productModel->delete($this->testProductId, true);

        // User cleanup
        $this->userModel->delete($this->testSellerId, true);
        $this->userModel->delete($this->testCustomerId, true);

        parent::tearDown();
    }

    /** Seed cart with items for the test customer */
    private function seedCart(int $quantity = 2): void
    {
        $cart = $this->cartModel->where('user_id', $this->testCustomerId)->first();
        if (!$cart) {
            $cartId = $this->cartModel->insert([
                'user_id'    => $this->testCustomerId,
                'session_id' => 'test_session_' . uniqid(),
            ]);
        } else {
            $cartId = $cart['id'];
        }

        $this->cartItemModel->insert([
            'cart_id'    => $cartId,
            'product_id' => $this->testProductId,
            'quantity'   => $quantity,
            'unit_price' => 1500.00,
        ]);
    }

    public function testCodCheckoutCreatesOrder(): void
    {
        $this->seedCart(2);

        $result = $this->orderService->checkout(
            $this->testCustomerId,
            $this->testAddressId,
            'cod',
            false,
            'Please deliver by evening.'
        );

        $this->assertArrayHasKey('order_id', $result);
        $this->assertArrayHasKey('order_number', $result);
        $this->assertNotEmpty($result['order_number']);

        // Verify order exists
        $order = $this->orderModel->find($result['order_id']);
        $this->assertNotNull($order);
        $this->assertSame('placed', $order['status']);
        // orders table uses user_id (not customer_id)
        $this->assertSame($this->testCustomerId, (int) $order['user_id']);

        // payment_method lives in the payments table
        $db = \Config\Database::connect();
        $payment = $db->table('payments')->where('order_id', $result['order_id'])->get()->getRowArray();
        $this->assertNotNull($payment);
        $this->assertSame('cod', $payment['payment_method']);
    }

    public function testCodCheckoutDecrementsStock(): void
    {
        $qty = 3;
        $this->seedCart($qty);

        $this->orderService->checkout(
            $this->testCustomerId,
            $this->testAddressId,
            'cod',
            false,
            null
        );

        $product = $this->productModel->find($this->testProductId);
        $this->assertSame($this->initialStock - $qty, (int) $product['stock']);
    }

    public function testCheckoutWithWalletDeduction(): void
    {
        // Give customer a wallet balance of 1000 PKR
        $this->walletService->credit(
            $this->testCustomerId,
            1000.00,
            'adjustment',
            null,
            'Pre-fund for checkout test'
        );

        $this->seedCart(1); // 1500.00 subtotal

        $result = $this->orderService->checkout(
            $this->testCustomerId,
            $this->testAddressId,
            'cod',
            true, // use wallet
            null
        );

        $order = $this->orderModel->find($result['order_id']);
        $this->assertNotNull($order);

        // Wallet deduction must appear as a debit ledger entry
        $walletId = $this->walletService->getOrCreateWallet($this->testCustomerId);
        $debit = $this->transactionModel
            ->where('wallet_id', $walletId)
            ->where('type', 'debit')
            ->where('reference_type', 'order_payment')
            ->first();

        $this->assertNotNull($debit, 'Expected a wallet debit entry for order payment.');
        $this->assertGreaterThan(0.0, (float) $debit['amount']);

        // Remaining balance should be less than the original credit
        $remainingBalance = $this->walletService->getBalance($this->testCustomerId);
        $this->assertLessThan(1000.00, $remainingBalance);
    }

    public function testPayfastWithoutKeysIsRejected(): void
    {
        $this->seedCart(1);
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('PayFast');

        $this->orderService->checkout(
            $this->testCustomerId,
            $this->testAddressId,
            'payfast',
            false,
            null
        );
    }

    public function testCheckoutFailsWithEmptyCart(): void
    {
        // Do NOT seed cart — it should be empty
        $this->expectException(\RuntimeException::class);

        $this->orderService->checkout(
            $this->testCustomerId,
            $this->testAddressId,
            'cod',
            false,
            null
        );
    }
}
