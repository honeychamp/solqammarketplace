<?php

namespace Tests\Feature;

use App\Models\AddressModel;
use App\Models\CategoryModel;
use App\Models\OrderItemModel;
use App\Models\OrderModel;
use App\Models\ProductModel;
use App\Models\UserModel;
use App\Services\Order\OrderService;
use App\Services\Wallet\WalletService;
use CodeIgniter\Test\CIUnitTestCase;

/**
 * @internal
 */
final class OrderStatusTransitionTest extends CIUnitTestCase
{
    protected OrderService $orderService;
    protected WalletService $walletService;
    protected OrderModel $orderModel;
    protected OrderItemModel $orderItemModel;
    protected UserModel $userModel;
    protected ProductModel $productModel;
    protected CategoryModel $categoryModel;
    protected AddressModel $addressModel;

    protected int $customerId;
    protected int $sellerId;
    protected int $categoryId;
    protected int $productId;
    protected int $addressId;

    protected function setUp(): void
    {
        parent::setUp();

        $this->orderService    = new OrderService();
        $this->walletService   = new WalletService();
        $this->orderModel      = new OrderModel();
        $this->orderItemModel  = new OrderItemModel();
        $this->userModel       = new UserModel();
        $this->productModel    = new ProductModel();
        $this->categoryModel   = new CategoryModel();
        $this->addressModel    = new AddressModel();

        // 1. Create Customer
        $this->customerId = (int) $this->userModel->insert([
            'name'          => 'Transition Test Customer',
            'email'         => 'cust_trans_' . uniqid() . '@solqam.pk',
            'phone'         => '0311' . random_int(1000000, 9999999),
            'password_hash' => password_hash('pass123', PASSWORD_BCRYPT),
            'role'          => 'customer',
            'status'        => 'active',
            'is_verified'   => 1,
        ]);

        // 2. Create Seller
        $this->sellerId = (int) $this->userModel->insert([
            'name'          => 'Transition Test Seller',
            'email'         => 'seller_trans_' . uniqid() . '@solqam.pk',
            'phone'         => '0312' . random_int(1000000, 9999999),
            'password_hash' => password_hash('pass123', PASSWORD_BCRYPT),
            'role'          => 'seller',
            'status'        => 'active',
            'is_verified'   => 1,
        ]);

        // 3. Create Category
        $this->categoryId = (int) $this->categoryModel->insert([
            'name'        => 'Test Category ' . uniqid(),
            'slug'        => 'test-cat-' . uniqid(),
            'description' => 'Test Desc',
            'is_active'   => 1,
        ]);

        // 4. Create Product with initial stock of 20
        $this->productId = (int) $this->productModel->insert([
            'seller_id'   => $this->sellerId,
            'category_id' => $this->categoryId,
            'name'        => 'Test Product ' . uniqid(),
            'slug'        => 'test-prod-' . uniqid(),
            'description' => 'Description',
            'price'       => 2000.00,
            'stock'       => 20,
            'sku'         => 'SKU-TRANS-' . uniqid(),
            'status'      => 'active',
        ]);

        // 5. Create Address
        $this->addressId = (int) $this->addressModel->insert([
            'user_id'        => $this->customerId,
            'recipient_name' => 'Recipient Name',
            'phone'          => '03001234567',
            'street_address' => 'Street 1, Phase 5',
            'city'           => 'Lahore',
            'province'       => 'Punjab',
            'is_default'     => 1,
        ]);
    }

    protected function tearDown(): void
    {
        // Clean up created entities
        $this->productModel->delete($this->productId, true);
        $this->categoryModel->delete($this->categoryId);
        $this->addressModel->where('user_id', $this->customerId)->delete();
        $this->userModel->delete($this->customerId, true);
        $this->userModel->delete($this->sellerId, true);

        parent::tearDown();
    }

    public function testOrderLifecycleAndAutomaticCashbackUponDelivery(): void
    {
        // Create an order with total Rs. 2000
        $orderId = (int) $this->orderModel->insert([
            'order_number'       => 'TEST-ORD-' . uniqid(),
            'user_id'            => $this->customerId,
            'address_id'         => $this->addressId,
            'total_amount'       => 2000.00,
            'discount_amount'    => 0.00,
            'wallet_amount_used' => 0.00,
            'final_payable'      => 2000.00,
            'commission_rate'    => 10.00,
            'commission_amount'  => 200.00,
            'status'             => 'placed',
        ]);

        $this->orderItemModel->insert([
            'order_id'          => $orderId,
            'product_id'        => $this->productId,
            'seller_id'         => $this->sellerId,
            'product_name'      => 'Test Product',
            'price'             => 2000.00,
            'quantity'          => 1,
            'subtotal'          => 2000.00,
            'commission_amount' => 200.00,
        ]);

        // Initial customer wallet balance must be 0
        $this->assertSame(0.0, $this->walletService->getBalance($this->customerId));

        // Transition: placed -> confirmed
        $this->orderService->updateOrderStatus($orderId, 'confirmed');
        $this->assertSame('confirmed', $this->orderModel->find($orderId)['status']);

        // Transition: confirmed -> shipped
        $this->orderService->updateOrderStatus($orderId, 'shipped');
        $this->assertSame('shipped', $this->orderModel->find($orderId)['status']);

        // Transition: shipped -> delivered
        $this->orderService->updateOrderStatus($orderId, 'delivered');
        $updatedOrder = $this->orderModel->find($orderId);
        $this->assertSame('delivered', $updatedOrder['status']);
        $this->assertNotNull($updatedOrder['delivery_date']);

        // VERIFY 10% CASHBACK IN WALLET LEDGER: 10% of 2000.00 = Rs. 200.00
        $cashbackBalance = $this->walletService->getBalance($this->customerId);
        $this->assertSame(200.00, $cashbackBalance);

        // Clean up this order
        $this->orderItemModel->where('order_id', $orderId)->delete();
        $this->orderModel->delete($orderId);
    }
}
