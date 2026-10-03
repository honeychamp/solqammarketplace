<?php

namespace App\Services\Order;

use App\Models\AddressModel;
use App\Models\CartItemModel;
use App\Models\CartModel;
use App\Models\OrderItemModel;
use App\Models\OrderModel;
use App\Models\PayLaterApplicationModel;
use App\Models\PaymentModel;
use App\Models\ProductModel;
use App\Models\SellerPayoutModel;
use App\Models\ShipmentModel;
use App\Services\Catalog\PricingService;
use App\Services\Commission\CommissionService;
use App\Models\UserModel;
use App\Services\Payment\GatewayFactory;
use App\Services\Promotion\CouponService;
use App\Services\Sms\SmsNotifier;
use App\Services\Shipping\ShippingService;
use App\Services\Wallet\WalletService;
use Config\Database;
use Exception;
use RuntimeException;

class OrderService
{
    protected OrderModel $orderModel;
    protected OrderItemModel $orderItemModel;
    protected CartModel $cartModel;
    protected CartItemModel $cartItemModel;
    protected ProductModel $productModel;
    protected PaymentModel $paymentModel;
    protected WalletService $walletService;
    protected CommissionService $commissionService;
    protected PricingService $pricingService;
    protected ShippingService $shippingService;
    protected CouponService $couponService;
    protected $db;
    protected ?array $adminUserIdCache = null;

    public function __construct()
    {
        $this->orderModel        = new OrderModel();
        $this->orderItemModel    = new OrderItemModel();
        $this->cartModel         = new CartModel();
        $this->cartItemModel     = new CartItemModel();
        $this->productModel      = new ProductModel();
        $this->paymentModel      = new PaymentModel();
        $this->walletService     = new WalletService();
        $this->commissionService = new CommissionService();
        $this->pricingService    = new PricingService();
        $this->shippingService   = new ShippingService();
        $this->couponService     = new CouponService();
        $this->db                = Database::connect();
        helper('marketplace');
    }

    public function checkout(
        int $userId,
        int $addressId,
        string $paymentMethod = 'cod',
        bool $useWallet = false,
        ?string $notes = null,
        ?string $couponCode = null,
        array $payLater = []
    ): array {
        $allowedPayments = ['cod', 'payfast', 'wallet', 'pay_later'];
        if ($paymentMethod === 'jazzcash' || $paymentMethod === 'easypaisa' || $paymentMethod === 'card') {
            $paymentMethod = 'payfast';
        }
        if (!in_array($paymentMethod, $allowedPayments, true)) {
            throw new RuntimeException('Invalid payment method.');
        }

        $cart = $this->cartModel->where('user_id', $userId)->first();
        if (!$cart) {
            throw new RuntimeException('Cart is empty.');
        }

        $cartItems = $this->cartItemModel->getItemsWithProducts($cart['id']);
        if (empty($cartItems)) {
            throw new RuntimeException('Your cart is empty.');
        }

        $totalAmount = 0.0;
        foreach ($cartItems as $item) {
            if ($item['stock_available'] < $item['quantity']) {
                throw new RuntimeException('Insufficient stock for product: ' . esc($item['product_name']));
            }
            $totalAmount += ((float) $item['unit_price'] * (int) $item['quantity']);
        }

        $address = (new AddressModel())->find($addressId);
        $shippingQuote = $this->shippingService->quote(
            $address['city'] ?? null,
            $address['province'] ?? null,
            $totalAmount
        );
        $shippingAmount = (float) $shippingQuote['amount'];

        $couponId = null;
        $couponDiscount = 0.0;
        $normalizedCoupon = $couponCode ? strtoupper(trim($couponCode)) : null;
        if ($normalizedCoupon) {
            $applied = $this->couponService->apply($normalizedCoupon, $userId, $totalAmount);
            $couponId = (int) $applied['coupon']['id'];
            $couponDiscount = (float) $applied['discount'];
        }

        $goodsAfterDiscount = max(0.0, $totalAmount - $couponDiscount);
        $walletBalance = $this->walletService->getBalance($userId);
        $deliveryArrears = $this->walletService->getOutstandingDebt($userId);
        $payableBeforeWallet = $goodsAfterDiscount + $shippingAmount + $deliveryArrears;

        $walletAmountUsed = 0.0;
        if ($useWallet && $walletBalance > 0) {
            $walletAmountUsed = min($walletBalance, $payableBeforeWallet);
        }

        $finalPayable = max(0.0, $payableBeforeWallet - $walletAmountUsed);

        $this->assertCodFraudLimit($userId, $paymentMethod, $addressId);

        if ($finalPayable <= 0) {
            $paymentMethod = 'wallet';
        } elseif ($paymentMethod === 'wallet') {
            throw new RuntimeException('Wallet balance is less than the order total. Choose Cash on Delivery for the remaining amount.');
        } elseif ($paymentMethod === 'payfast') {
            if (! config('Payments')->payfastReady()) {
                throw new RuntimeException('Online payment is not available yet. Use Cash on Delivery.');
            }
        } elseif ($paymentMethod === 'pay_later') {
            if (!$useWallet || $walletAmountUsed <= 0 || $finalPayable <= 0) {
                throw new RuntimeException('Pay later tab available hai jab wallet use ho aur order total wallet se zyada ho. Baqi JazzCash, EasyPaisa, card, COD, ya Pay later se de sakte ho.');
            }
            if (empty($payLater['full_name']) || empty($payLater['cnic_number']) || empty($payLater['cnic_front_path']) || empty($payLater['utility_bill_path'])) {
                throw new RuntimeException('Pay later requires full name, CNIC number, CNIC copy, and a utility bill copy.');
            }
        }

        $commissionAmount = 0.0;
        $cashbackTotal = 0.0;
        $weightedRate = 0.0;
        $sellerGoods = 0.0;
        foreach ($cartItems as $item) {
            $line = (float) $item['unit_price'] * (int) $item['quantity'];
            $cashbackTotal += cashback_amount($line, $item);
            if ($this->isAdminSeller((int) $item['seller_id'])) {
                continue;
            }
            $lineRate = $this->commissionService->rateForCategory((int) ($item['category_id'] ?? 0));
            $commissionAmount += commission_amount($line, false, $lineRate);
            $sellerGoods += $line;
            $weightedRate += $line * $lineRate;
        }
        $commissionAmount = round($commissionAmount, 2);
        $commissionRate = $sellerGoods > 0 ? round($weightedRate / $sellerGoods, 2) : 0.0;
        $cashbackTotal = round($cashbackTotal, 2);
        $orderNumber = 'SOL-' . strtoupper(date('ymd')) . '-' . strtoupper(substr(md5(uniqid()), 0, 5));

        $this->db->transStart();

        try {
            $orderId = $this->orderModel->insert($this->onlyTableColumns('orders', [
                'order_number'       => $orderNumber,
                'user_id'            => $userId,
                'address_id'         => $addressId,
                'total_amount'       => $totalAmount,
                'discount_amount'    => $couponDiscount,
                'shipping_amount'    => $shippingAmount,
                'delivery_arrears'   => $deliveryArrears,
                'coupon_id'          => $couponId,
                'coupon_code'        => $normalizedCoupon,
                'coupon_discount'    => $couponDiscount,
                'wallet_amount_used' => $walletAmountUsed,
                'pay_later_wallet'   => ($paymentMethod === 'pay_later') ? $finalPayable : 0.0,
                'pay_later_cleared'  => 0,
                'final_payable'      => $finalPayable,
                'commission_rate'    => $commissionRate,
                'commission_amount'  => $commissionAmount,
                'cashback_amount'    => $cashbackTotal,
                'status'             => 'placed',
                'notes'              => $notes,
            ]));
            if ((int) $orderId <= 0) {
                throw new RuntimeException($this->dbError('Order could not be saved.'));
            }

            foreach ($cartItems as $item) {
                $subtotal = (float) $item['unit_price'] * (int) $item['quantity'];
                $locked = $this->lockProductStock((int) $item['product_id']);
                if (! $locked || (int) $locked['stock'] < (int) $item['quantity']) {
                    throw new RuntimeException('Insufficient stock for product: ' . ($item['product_name'] ?? ''));
                }
                $itemCommission = $this->isAdminSeller((int) $item['seller_id'])
                    ? 0.0
                    : commission_amount(
                        $subtotal,
                        false,
                        $this->commissionService->rateForCategory((int) ($item['category_id'] ?? 0))
                    );
                $itemCashbackRate = cashback_rate($item);
                $itemCashback = cashback_amount($subtotal, $itemCashbackRate);

                $itemId = $this->orderItemModel->insert($this->onlyTableColumns('order_items', [
                    'order_id'          => $orderId,
                    'product_id'        => $item['product_id'],
                    'variant_id'        => $item['variant_id'] ?? null,
                    'seller_id'         => $item['seller_id'],
                    'product_name'      => $item['product_name'],
                    'variant_label'     => $item['variant_label'] ?? null,
                    'price'             => $item['unit_price'],
                    'quantity'          => $item['quantity'],
                    'subtotal'          => $subtotal,
                    'commission_amount' => $itemCommission,
                    'cashback_percent'  => $itemCashbackRate,
                    'cashback_amount'   => $itemCashback,
                    'fulfillment_status'=> 'placed',
                ]));
                if ((int) $itemId <= 0) {
                    throw new RuntimeException($this->dbError('Order item could not be saved.'));
                }

                $this->db->table('products')
                    ->where('id', $item['product_id'])
                    ->decrement('stock', $item['quantity']);
                if ($this->tableHas('products', 'sold_count')) {
                    $this->db->table('products')
                        ->where('id', $item['product_id'])
                        ->increment('sold_count', $item['quantity']);
                }

                if (!empty($item['variant_id']) && $this->db->tableExists('product_variants')) {
                    $this->db->table('product_variants')
                        ->where('id', $item['variant_id'])
                        ->decrement('stock', $item['quantity']);
                }
            }

            $this->createShipments((int) $orderId, $cartItems, $shippingAmount);

            if ($walletAmountUsed > 0) {
                $this->walletService->debit(
                    $userId,
                    $walletAmountUsed,
                    'order_payment',
                    $orderId,
                    "Payment for Order {$orderNumber}"
                );
            }

            if ($deliveryArrears > 0) {
                $this->walletService->credit(
                    $userId,
                    $deliveryArrears,
                    'delivery_arrears',
                    (int) $orderId,
                    "Unpaid courier / Pay later balance added to Order {$orderNumber} payable"
                );
                $this->markOpenPayLaterCleared($userId, (int) $orderId);
            }

            if ($paymentMethod === 'pay_later' && $finalPayable > 0) {
                $this->walletService->debit(
                    $userId,
                    $finalPayable,
                    'pay_later',
                    (int) $orderId,
                    "Pay later remaining for Order {$orderNumber} — wallet minus until paid",
                    true
                );
            }

            if ($couponId) {
                $this->couponService->redeem($couponId, $userId, (int) $orderId);
            }

            $paymentStatus = 'pending';
            $transactionRef = null;
            $gatewayResponse = null;
            $gatewayRedirect = null;

            if (is_online_gateway($paymentMethod) && $finalPayable > 0) {
                $buyer = (new UserModel())->find($userId);
                $adapter = GatewayFactory::make('payfast');
                $initResult = $adapter->initiatePayment([
                    'order_id'     => $orderId,
                    'order_number' => $orderNumber,
                    'amount'       => $finalPayable,
                    'user_id'      => $userId,
                    'phone'        => $buyer['phone'] ?? '',
                    'email'        => $buyer['email'] ?? '',
                ]);
                $transactionRef  = $initResult['transaction_ref'] ?? null;
                $gatewayResponse = json_encode($initResult);
                $gatewayRedirect = $initResult;
                $paymentStatus   = 'pending';
                $paymentMethod   = 'payfast';
            } elseif ($finalPayable == 0) {
                $paymentStatus = 'paid';
                $transactionRef = 'WALLET-FULL-' . $orderId;
                $gatewayResponse = json_encode(['method' => 'wallet_full']);
            }

            if ($this->db->tableExists('payments')) {
                $this->paymentModel->insert($this->onlyTableColumns('payments', [
                    'order_id'         => $orderId,
                    'payment_method'   => $paymentMethod,
                    'transaction_ref'  => $transactionRef,
                    'amount'           => $finalPayable,
                    'status'           => $paymentStatus,
                    'gateway_response' => $gatewayResponse,
                    'paid_at'          => ($paymentStatus === 'paid') ? date('Y-m-d H:i:s') : null,
                ]));
            }

            if ($paymentMethod === 'pay_later') {
                (new PayLaterApplicationModel())->insert([
                    'order_id'          => $orderId,
                    'user_id'           => $userId,
                    'full_name'         => $payLater['full_name'],
                    'cnic_number'       => $payLater['cnic_number'],
                    'phone'             => $payLater['phone'] ?? null,
                    'address_text'      => $payLater['address_text'] ?? null,
                    'cnic_front_path'   => $payLater['cnic_front_path'],
                    'cnic_back_path'    => $payLater['cnic_back_path'] ?? null,
                    'utility_bill_path' => $payLater['utility_bill_path'],
                    'status'            => 'submitted',
                ]);
            }

            if ($paymentStatus === 'paid' && ! in_array($paymentMethod, ['cod', 'pay_later'], true)) {
                $this->settleSellerFunds((int) $orderId, 'online');
                $this->grantBuyerCashback([
                    'id'              => (int) $orderId,
                    'user_id'         => $userId,
                    'total_amount'    => $totalAmount,
                    'cashback_amount' => $cashbackTotal,
                    'order_number'    => $orderNumber,
                ], 'paid');
            }

            $this->cartItemModel->where('cart_id', $cart['id'])->delete();

            $this->db->transComplete();

            if ($this->db->transStatus() === false) {
                throw new RuntimeException($this->dbError('Checkout transaction failed.'));
            }

            $buyer = (new UserModel())->find($userId);
            SmsNotifier::orderPlaced(
                (string) ($buyer['phone'] ?? ''),
                $orderNumber,
                $paymentMethod,
                $finalPayable,
                $paymentStatus
            );
            try {
                \App\Services\Mail\MailService::orderUpdate(
                    (string) ($buyer['email'] ?? ''),
                    $orderNumber,
                    'placed',
                    $paymentStatus === 'paid' ? 'Payment confirmed.' : 'Payment pending / COD.'
                );
            } catch (\Throwable $e) {
            }
            \App\Services\Platform\AuditService::log('order_placed', 'order', (int) $orderId, ['number' => $orderNumber]);

            return [
                'success'       => true,
                'order_id'      => $orderId,
                'order_number'  => $orderNumber,
                'total_amount'  => $totalAmount,
                'final_payable' => $finalPayable,
                'payment_status'=> $paymentStatus,
                'payment_method'=> $paymentMethod,
                'redirect'      => $gatewayRedirect,
            ];
        } catch (Exception $e) {
            $this->db->transRollback();
            throw $e;
        } catch (\Throwable $e) {
            $this->db->transRollback();
            throw new RuntimeException($this->dbError('Checkout failed: ' . $e->getMessage()));
        }
    }

    protected function tableHas(string $table, string $field): bool
    {
        try {
            return $this->db->fieldExists($field, $table);
        } catch (\Throwable $e) {
            return false;
        }
    }

    protected function onlyTableColumns(string $table, array $row): array
    {
        $out = [];
        foreach ($row as $key => $value) {
            if ($this->tableHas($table, $key)) {
                $out[$key] = $value;
            }
        }

        return $out;
    }

    protected function dbError(string $fallback): string
    {
        $err = $this->db->error();
        $msg = trim((string) ($err['message'] ?? ''));

        return $msg !== '' ? ($fallback . ' ' . $msg) : $fallback;
    }

    protected function lockProductStock(int $productId): ?array
    {
        try {
            $row = $this->db->query(
                'SELECT id, stock FROM products WHERE id = ? FOR UPDATE',
                [$productId]
            )->getRowArray();
            if ($row) {
                return $row;
            }
        } catch (\Throwable $e) {
            log_message('error', 'Product stock lock: ' . $e->getMessage());
        }

        return $this->db->table('products')->select('id, stock')->where('id', $productId)->get()->getRowArray();
    }

    public function confirmOnlinePayment(array $verified, string $gateway): bool
    {
        $payment = $this->findPaymentForGateway($verified);
        if (! $payment) {
            return false;
        }
        if ($payment['status'] === 'paid') {
            return true;
        }
        if (! ($verified['success'] ?? false)) {
            return $this->markPaymentFailed($payment, $verified);
        }

        $this->db->transStart();
        $this->paymentModel->update($payment['id'], [
            'status'           => 'paid',
            'transaction_ref'  => $verified['transaction_ref'] ?: $payment['transaction_ref'],
            'gateway_response' => json_encode($verified),
            'paid_at'          => date('Y-m-d H:i:s'),
        ]);

        $order = $this->orderModel->find((int) $payment['order_id']);
        if ($order && $order['status'] === 'placed') {
            $this->orderModel->update((int) $order['id'], ['status' => 'confirmed']);
        }

        $this->settleSellerFunds((int) $payment['order_id'], 'online');
        if ($order) {
            $this->grantBuyerCashback($order, 'paid');
            $buyer = (new UserModel())->find((int) $order['user_id']);
            SmsNotifier::paymentConfirmed(
                (string) ($buyer['phone'] ?? ''),
                (string) $order['order_number'],
                (float) $payment['amount'],
                $gateway
            );
        }
        $this->db->transComplete();

        return $this->db->transStatus() !== false;
    }

    public function markPaymentFailed(array $payment, array $verified = []): bool
    {
        if (($payment['status'] ?? '') === 'paid') {
            return true;
        }
        $this->paymentModel->update($payment['id'], [
            'status'           => 'failed',
            'gateway_response' => json_encode($verified),
        ]);
        $order = $this->orderModel->find((int) $payment['order_id']);
        if ($order) {
            $buyer = (new UserModel())->find((int) $order['user_id']);
            SmsNotifier::paymentFailed((string) ($buyer['phone'] ?? ''), (string) $order['order_number']);
        }

        return true;
    }

    public function rebuildGatewayRedirect(int $orderId): ?array
    {
        $payment = $this->paymentModel->where('order_id', $orderId)->first();
        if (! $payment || $payment['status'] === 'paid') {
            return null;
        }
        if (! is_online_gateway($payment['payment_method'] ?? null)) {
            return null;
        }
        $order = $this->orderModel->find($orderId);
        if (! $order) {
            return null;
        }
        $adapter = GatewayFactory::make('payfast');
        $buyer = (new UserModel())->find((int) $order['user_id']);
        $init = $adapter->initiatePayment([
            'order_id'     => $orderId,
            'order_number' => $order['order_number'],
            'amount'       => (float) $payment['amount'],
            'user_id'      => (int) $order['user_id'],
            'phone'        => $buyer['phone'] ?? '',
            'email'        => $buyer['email'] ?? '',
        ]);
        $this->paymentModel->update($payment['id'], [
            'status'           => 'pending',
            'transaction_ref'  => $init['transaction_ref'] ?? $payment['transaction_ref'],
            'gateway_response' => json_encode($init),
        ]);

        return $init;
    }

    protected function findPaymentForGateway(array $verified): ?array
    {
        if (! empty($verified['transaction_ref'])) {
            $row = $this->paymentModel->where('transaction_ref', $verified['transaction_ref'])->first();
            if ($row) {
                return $row;
            }
        }
        if (! empty($verified['order_id'])) {
            return $this->paymentModel->where('order_id', (int) $verified['order_id'])->first();
        }
        if (! empty($verified['order_number'])) {
            $order = $this->orderModel->where('order_number', $verified['order_number'])->first();
            if ($order) {
                return $this->paymentModel->where('order_id', $order['id'])->first();
            }
        }

        return null;
    }

    protected function assertOnlinePaymentCleared(int $orderId): void
    {
        $payment = $this->paymentModel->where('order_id', $orderId)->first();
        if (! $payment) {
            return;
        }
        if (is_online_gateway($payment['payment_method'] ?? null) && $payment['status'] !== 'paid') {
            throw new RuntimeException('PayFast did not confirm payment. The order is confirmed or shipped only after payment is paid.');
        }
    }

    public function updateOrderStatus(int $orderId, string $newStatus, ?string $note = null, array $extra = []): bool
    {
        $allowedTransitions = ['placed', 'confirmed', 'shipped', 'delivered', 'cancelled', 'returned', 'undelivered'];
        if (!in_array($newStatus, $allowedTransitions, true)) {
            throw new RuntimeException("Invalid order status: {$newStatus}");
        }

        $order = $this->orderModel->find($orderId);
        if (!$order) {
            throw new RuntimeException('Order not found.');
        }

        if ($order['status'] === $newStatus) {
            return true;
        }

        if (in_array($newStatus, ['confirmed', 'shipped', 'delivered'], true)) {
            $this->assertOnlinePaymentCleared($orderId);
        }

        if ($newStatus === 'undelivered' && ! in_array((string) $order['status'], ['shipped', 'undelivered'], true)) {
            throw new RuntimeException('Mark undelivered only after the parcel was handed to courier.');
        }

        $this->db->transStart();

        $updateData = ['status' => $newStatus];
        if (!empty($extra['tracking_number'])) {
            $updateData['tracking_number'] = $extra['tracking_number'];
        }
        if (!empty($extra['courier'])) {
            $updateData['courier'] = $extra['courier'];
        }

        if ($newStatus === 'delivered') {
            $updateData['delivery_date'] = date('Y-m-d H:i:s');

            $payment = $this->paymentModel->where('order_id', $orderId)->first();
            if ($payment && $payment['status'] === 'pending') {
                $this->paymentModel->update($payment['id'], [
                    'status'  => 'paid',
                    'paid_at' => date('Y-m-d H:i:s'),
                ]);
            }

            $this->grantBuyerCashback($order, 'delivered');

            $payment = $this->paymentModel->where('order_id', $orderId)->first();
            $method = $payment['payment_method'] ?? 'cod';
            if ($method === 'cod' || $method === 'pay_later' || ($payment['status'] ?? '') !== 'paid') {
                $this->settleSellerFunds((int) $orderId, 'cod');
            } else {
                foreach ((new ShipmentModel())->where('order_id', $orderId)->findAll() as $pkg) {
                    if (($pkg['status'] ?? '') === 'delivered') {
                        $this->settlePackageOnDelivered((int) $orderId, (int) $pkg['seller_id'], $method);
                    }
                }
            }
            $this->settlePayLaterWallet($order);
        } elseif ($newStatus === 'cancelled') {
            $this->restockItems($this->orderItemModel->where('order_id', $orderId)->findAll());
            $this->refundWalletOnVoid($order, 'Cancelled');
            $this->reversePayLaterWallet($order);
            $this->restoreDeliveryArrearsOnVoid($order);
        } elseif ($newStatus === 'undelivered') {
            if (empty($extra['skip_void_effects'])) {
                $this->restockItems($this->orderItemModel->where('order_id', $orderId)->findAll());
                $this->chargeFailedDeliveryFee(
                    $order,
                    (float) ($order['shipping_amount'] ?? 0),
                    $orderId,
                    "Courier fee for undelivered order {$order['order_number']}"
                );
            }
            $this->refundWalletOnVoid($order, 'Not received');
            $this->reversePayLaterWallet($order);
        }

        $this->orderModel->update($orderId, $updateData);

        $shipmentModel = new ShipmentModel();
        $shipmentModel->where('order_id', $orderId)->set(['status' => $newStatus])->update();
        if (!empty($extra['tracking_number']) || !empty($extra['courier'])) {
            $shipUpdate = [];
            if (!empty($extra['tracking_number'])) {
                $shipUpdate['tracking_number'] = $extra['tracking_number'];
            }
            if (!empty($extra['courier'])) {
                $shipUpdate['courier'] = $extra['courier'];
            }
            if ($shipUpdate) {
                $shipmentModel->where('order_id', $orderId)->set($shipUpdate)->update();
            }
        }
        $this->orderItemModel->where('order_id', $orderId)->set(['fulfillment_status' => $newStatus])->update();

        $this->db->transComplete();

        $ok = $this->db->transStatus();
        if ($ok) {
            $fresh = $this->orderModel->find($orderId) ?: $order;
            $this->smsBuyerStatus($fresh, $newStatus, $extra);
        }

        return $ok;
    }

    public function setFulfillBy(int $orderId, int $sellerId, string $fulfillBy): void
    {
        if ($this->isAdminSeller($sellerId)) {
            throw new RuntimeException('Admin store packages are fulfilled by Solqam.');
        }
        $fulfillBy = $fulfillBy === 'admin' ? 'admin' : 'seller';
        $this->assertOnlinePaymentCleared($orderId);

        $shipmentModel = new ShipmentModel();
        $shipment = $shipmentModel->where('order_id', $orderId)->where('seller_id', $sellerId)->first();
        if (! $shipment) {
            throw new RuntimeException('Shipment not found.');
        }
        if (in_array((string) ($shipment['status'] ?? ''), ['shipped', 'delivered'], true)) {
            throw new RuntimeException('This package is already in transit or delivered. Fulfillment cannot change.');
        }

        $payload = [
            'fulfill_by'     => $fulfillBy,
            'handoff_status' => $fulfillBy === 'admin' ? 'pending_admin' : null,
        ];
        if ($fulfillBy === 'admin' && in_array((string) $shipment['status'], ['placed', ''], true)) {
            $payload['status'] = 'confirmed';
            $this->orderItemModel
                ->where('order_id', $orderId)
                ->where('seller_id', $sellerId)
                ->set(['fulfillment_status' => 'confirmed'])
                ->update();
        }
        $shipmentModel->update($shipment['id'], $payload);
    }

    public function receiveAdminInbound(int $shipmentId): void
    {
        $shipmentModel = new ShipmentModel();
        $shipment = $shipmentModel->find($shipmentId);
        if (! $shipment || ($shipment['fulfill_by'] ?? '') !== 'admin') {
            throw new RuntimeException('This package is not assigned to Solqam delivery.');
        }
        $shipmentModel->update($shipmentId, ['handoff_status' => 'received']);
    }

    public function updateSellerShipment(int $orderId, int $sellerId, string $newStatus, array $extra = [], bool $asAdminCourier = false): bool
    {
        $allowed = ['confirmed', 'shipped', 'delivered', 'undelivered'];
        if (!in_array($newStatus, $allowed, true)) {
            throw new RuntimeException('Package status may be Confirmed, Shipped, Delivered, or Not received.');
        }
        $this->assertOnlinePaymentCleared($orderId);

        $shipmentModel = new ShipmentModel();
        $shipment = $shipmentModel->where('order_id', $orderId)->where('seller_id', $sellerId)->first();
        if (!$shipment) {
            $shipmentId = $shipmentModel->insert([
                'order_id'   => $orderId,
                'seller_id'  => $sellerId,
                'fulfill_by' => $this->isAdminSeller($sellerId) ? 'admin' : 'seller',
                'status'     => 'placed',
            ]);
            $shipment = $shipmentModel->find($shipmentId);
        }

        $fulfillBy = (string) ($shipment['fulfill_by'] ?? 'seller');
        if ($fulfillBy === 'admin' && ! $asAdminCourier && ! $this->isAdminSeller($sellerId) && in_array($newStatus, ['shipped', 'delivered', 'undelivered'], true)) {
            throw new RuntimeException('This package is assigned to Solqam delivery. You cannot mark it shipped, delivered, or not received.');
        }
        if ($fulfillBy === 'seller' && $asAdminCourier && ! $this->isAdminSeller($sellerId)) {
            throw new RuntimeException('Seller is delivering this package. Admin courier is not assigned.');
        }

        if ($newStatus === 'undelivered' && ! in_array((string) ($shipment['status'] ?? ''), ['shipped', 'undelivered'], true)) {
            throw new RuntimeException('Mark not received only after this package was handed to courier.');
        }

        $data = ['status' => $newStatus];
        if (!empty($extra['tracking_number'])) {
            $data['tracking_number'] = $extra['tracking_number'];
        }
        if (!empty($extra['courier'])) {
            $data['courier'] = $extra['courier'];
        }
        $shipmentModel->update($shipment['id'], $data);

        $this->orderItemModel
            ->where('order_id', $orderId)
            ->where('seller_id', $sellerId)
            ->set(['fulfillment_status' => $newStatus])
            ->update();

            if ($newStatus === 'delivered') {
            $payment = $this->paymentModel->where('order_id', $orderId)->first();
            $method = $payment['payment_method'] ?? 'cod';
            $this->settlePackageOnDelivered($orderId, $sellerId, $method);
        }

        if ($newStatus === 'undelivered') {
            $orderRow = $this->orderModel->find($orderId);
            $this->restockItems($this->orderItemModel->where('order_id', $orderId)->where('seller_id', $sellerId)->findAll());
            $fee = (float) ($shipment['shipping_amount'] ?? 0);
            if ($fee <= 0 && $orderRow) {
                $fee = (float) ($orderRow['shipping_amount'] ?? 0);
            }
            if ($orderRow) {
                $this->chargeFailedDeliveryFee(
                    $orderRow,
                    $fee,
                    (int) $shipment['id'],
                    "Courier fee — buyer did not receive package on order {$orderRow['order_number']}"
                );
            }
        }

        $packages = $shipmentModel->where('order_id', $orderId)->findAll();
        if ($packages === []) {
            return $this->updateOrderStatus($orderId, $newStatus, null, $extra);
        }

        $statuses = array_column($packages, 'status');
        $open = array_intersect($statuses, ['placed', 'confirmed', 'shipped']);
        $closed = array_unique($statuses);
        if ($open === [] && $closed === ['delivered']) {
            return $this->updateOrderStatus($orderId, 'delivered', null, $extra);
        }
        if ($open === [] && $closed === ['undelivered']) {
            $orderRow = $this->orderModel->find($orderId);
            if ($orderRow) {
                $this->refundWalletOnVoid($orderRow, 'Not received');
            }
            return $this->updateOrderStatus($orderId, 'undelivered', null, array_merge($extra, [
                'skip_void_effects' => true,
            ]));
        }
        if (in_array('shipped', $statuses, true) || in_array('delivered', $statuses, true)) {
            $order = $this->orderModel->find($orderId);
            if ($order && in_array($order['status'], ['placed', 'confirmed'], true)) {
                $this->orderModel->update($orderId, [
                    'status'          => 'shipped',
                    'tracking_number' => $extra['tracking_number'] ?? $order['tracking_number'],
                    'courier'         => $extra['courier'] ?? $order['courier'],
                ]);
                $fresh = $this->orderModel->find($orderId) ?: $order;
                $this->smsBuyerStatus($fresh, 'shipped', $extra);
            }
        } elseif (in_array('confirmed', $statuses, true)) {
            $order = $this->orderModel->find($orderId);
            if ($order && $order['status'] === 'placed') {
                $this->orderModel->update($orderId, ['status' => 'confirmed']);
                $fresh = $this->orderModel->find($orderId) ?: $order;
                $this->smsBuyerStatus($fresh, 'confirmed', $extra);
            }
        }

        return true;
    }

    protected function settleSellerFunds(int $orderId, string $mode, ?int $onlySellerId = null): void
    {
        $items = $this->orderItemModel->where('order_id', $orderId)->findAll();
        $bySeller = [];
        foreach ($items as $item) {
            $sid = (int) $item['seller_id'];
            if ($onlySellerId !== null && $sid !== $onlySellerId) {
                continue;
            }
            if (! isset($bySeller[$sid])) {
                $bySeller[$sid] = ['goods' => 0.0, 'commission' => 0.0, 'cashback' => 0.0];
            }
            $bySeller[$sid]['goods'] += (float) $item['subtotal'];
            $bySeller[$sid]['commission'] += (float) $item['commission_amount'];
            $bySeller[$sid]['cashback'] += item_cashback($item);
        }

        $payment = $this->paymentModel->where('order_id', $orderId)->first();
        $method  = $payment['payment_method'] ?? 'cod';

        foreach ($bySeller as $sellerId => $totals) {
            if ($this->isAdminSeller((int) $sellerId)) {
                continue;
            }
            if ($mode === 'online') {
                $this->settleOnlineGoods($orderId, (int) $sellerId, $totals);
            } else {
                $this->settlePackageOnDelivered($orderId, (int) $sellerId, $method, $totals);
            }
        }
    }

    protected function settleOnlineGoods(int $orderId, int $sellerId, array $totals): void
    {
        $order = $this->orderModel->find($orderId);
        $orderNumber = $order['order_number'] ?? ('#' . $orderId);
        $adminId = $this->platformAdminId();
        $commission = round($totals['commission'], 2);
        $cashback = round($totals['cashback'], 2);
        $net = round(max(0.0, $totals['goods'] - $commission - $cashback), 2);

        if ($commission > 0 && $adminId > 0 && ! $this->hasLedger($orderId, 'commission', 'seller ' . $sellerId)) {
            $this->walletService->credit(
                $adminId,
                $commission,
                'commission',
                $orderId,
                "Platform commission from order {$orderNumber} seller {$sellerId} (goods only, no delivery)"
            );
        }

        if ($net > 0 && ! $this->hasLedger($orderId, 'seller_payout', 'seller ' . $sellerId)) {
            $this->walletService->credit(
                $sellerId,
                $net,
                'seller_payout',
                $orderId,
                "Goods payout order {$orderNumber} seller {$sellerId} (category commission cut, delivery held)"
            );
        }

        $this->upsertPayout($orderId, $sellerId, $net);
    }

    protected function settlePackageOnDelivered(int $orderId, int $sellerId, string $method, ?array $totals = null): void
    {
        if ($this->isAdminSeller($sellerId)) {
            return;
        }
        if ($totals === null) {
            $items = $this->orderItemModel->where('order_id', $orderId)->where('seller_id', $sellerId)->findAll();
            $totals = ['goods' => 0.0, 'commission' => 0.0, 'cashback' => 0.0];
            foreach ($items as $item) {
                $totals['goods'] += (float) $item['subtotal'];
                $totals['commission'] += (float) $item['commission_amount'];
                $totals['cashback'] += item_cashback($item);
            }
        }

        $shipment = (new ShipmentModel())->where('order_id', $orderId)->where('seller_id', $sellerId)->first();
        $fulfillBy = (string) ($shipment['fulfill_by'] ?? 'seller');
        $shipAmt = round((float) ($shipment['shipping_amount'] ?? 0), 2);
        $order = $this->orderModel->find($orderId);
        $orderNumber = $order['order_number'] ?? ('#' . $orderId);
        $adminId = $this->platformAdminId();
        $commission = round($totals['commission'], 2);
        $cashback = round($totals['cashback'], 2);
        $goodsNet = round(max(0.0, $totals['goods'] - $commission - $cashback), 2);
        $sellerGetsDelivery = $fulfillBy !== 'admin';
        $deliveryToSeller = $sellerGetsDelivery ? $shipAmt : 0.0;
        $isCod = in_array($method, ['cod', 'pay_later'], true);

        if ($commission > 0 && $adminId > 0 && ! $this->hasLedger($orderId, 'commission', 'seller ' . $sellerId)) {
            $this->walletService->credit(
                $adminId,
                $commission,
                'commission',
                $orderId,
                "Platform commission from order {$orderNumber} seller {$sellerId} (goods only, no delivery)"
            );
        }

        if ($isCod) {
            if ($fulfillBy === 'admin' && $goodsNet > 0 && ! $this->hasLedger($orderId, 'seller_payout', 'seller ' . $sellerId)) {
                $this->walletService->credit(
                    $sellerId,
                    $goodsNet,
                    'seller_payout',
                    $orderId,
                    "COD collected by Solqam, goods after commission order {$orderNumber} seller {$sellerId}"
                );
            }
        } elseif ($sellerGetsDelivery && $deliveryToSeller > 0 && ! $this->hasLedger($orderId, 'seller_shipping', 'seller ' . $sellerId)) {
            $this->walletService->credit(
                $sellerId,
                $deliveryToSeller,
                'seller_shipping',
                $orderId,
                "Delivery fee passed to seller (self-ship) order {$orderNumber} seller {$sellerId}"
            );
        }

        $this->upsertPayout($orderId, $sellerId, round($goodsNet + $deliveryToSeller, 2));
    }

    protected function upsertPayout(int $orderId, int $sellerId, float $amount): void
    {
        $payoutModel = new SellerPayoutModel();
        $existing = $payoutModel->where('order_id', $orderId)->where('seller_id', $sellerId)->first();
        $row = [
            'seller_id' => $sellerId,
            'order_id'  => $orderId,
            'amount'    => $amount,
            'status'    => 'paid',
            'paid_at'   => date('Y-m-d H:i:s'),
        ];
        if ($existing) {
            $payoutModel->update($existing['id'], $row);
        } else {
            $payoutModel->insert($row);
        }
    }

    protected function restockItems(array $items): void
    {
        foreach ($items as $item) {
            $this->db->table('products')
                ->where('id', $item['product_id'])
                ->increment('stock', $item['quantity']);
            if (! empty($item['variant_id'])) {
                $this->db->table('product_variants')
                    ->where('id', $item['variant_id'])
                    ->increment('stock', $item['quantity']);
            }
        }
    }

    protected function refundWalletOnVoid(array $order, string $reason): void
    {
        $orderId = (int) $order['id'];
        if ((float) ($order['wallet_amount_used'] ?? 0) > 0 && ! $this->hasLedger($orderId, 'refund', 'wallet amount')) {
            $this->walletService->credit(
                (int) $order['user_id'],
                (float) $order['wallet_amount_used'],
                'refund',
                $orderId,
                "Refund of wallet amount for {$reason} Order {$order['order_number']}"
            );
        }

        $cb = $this->cashbackRow($orderId);
        if ($cb && (float) $cb['amount'] > 0) {
            try {
                $this->walletService->debit(
                    (int) $order['user_id'],
                    (float) $cb['amount'],
                    'refund',
                    $orderId,
                    "Reverse cashback for {$reason} Order {$order['order_number']}"
                );
            } catch (\Throwable $e) {
                // Ledger already spent; void still proceeds.
            }
        }
    }

    protected function restoreDeliveryArrearsOnVoid(array $order): void
    {
        $arrears = (float) ($order['delivery_arrears'] ?? 0);
        if ($arrears <= 0) {
            return;
        }
        $orderId = (int) $order['id'];
        if ($this->hasLedger($orderId, 'delivery_fee', 'Restored unpaid courier')) {
            return;
        }
        $this->walletService->debit(
            (int) $order['user_id'],
            $arrears,
            'delivery_fee',
            $orderId,
            "Restored unpaid courier fee after void of Order {$order['order_number']}",
            true
        );
    }

    protected function markOpenPayLaterCleared(int $userId, int $exceptOrderId): void
    {
        if (! $this->tableHas('orders', 'pay_later_cleared')) {
            return;
        }
        $this->db->table('orders')
            ->where('user_id', $userId)
            ->where('pay_later_cleared', 0)
            ->where('pay_later_wallet >', 0)
            ->where('id !=', $exceptOrderId)
            ->update(['pay_later_cleared' => 1]);
    }

    protected function flagPayLaterCleared(int $orderId): void
    {
        if (! $this->tableHas('orders', 'pay_later_cleared')) {
            return;
        }
        $this->orderModel->update($orderId, ['pay_later_cleared' => 1]);
    }

    protected function settlePayLaterWallet(array $order): void
    {
        $amount = round((float) ($order['pay_later_wallet'] ?? 0), 2);
        if ($amount <= 0 || (int) ($order['pay_later_cleared'] ?? 0) === 1) {
            return;
        }
        $orderId = (int) $order['id'];
        if (! $this->hasLedger($orderId, 'pay_later_settled')) {
            $this->walletService->credit(
                (int) $order['user_id'],
                $amount,
                'pay_later_settled',
                $orderId,
                "Pay later amount collected for Order {$order['order_number']}"
            );
        }
        $this->flagPayLaterCleared($orderId);
    }

    protected function reversePayLaterWallet(array $order): void
    {
        $amount = round((float) ($order['pay_later_wallet'] ?? 0), 2);
        if ($amount <= 0 || (int) ($order['pay_later_cleared'] ?? 0) === 1) {
            return;
        }
        $orderId = (int) $order['id'];
        if (! $this->hasLedger($orderId, 'pay_later_settled') && ! $this->hasLedger($orderId, 'refund', 'Pay later')) {
            $this->walletService->credit(
                (int) $order['user_id'],
                $amount,
                'refund',
                $orderId,
                "Reverse Pay later wallet minus for Order {$order['order_number']}"
            );
        }
        $this->flagPayLaterCleared($orderId);
    }

    /**
     * COD / unpaid shipping still owed after courier ran but buyer did not take the parcel.
     * Prepaid PayFast already collected the fee — do not debit wallet again.
     */
    protected function chargeFailedDeliveryFee(array $order, float $fee, int $referenceId, string $label): void
    {
        $fee = round($fee, 2);
        if ($fee <= 0 || $referenceId <= 0) {
            return;
        }
        if ($this->hasLedger($referenceId, 'delivery_fee')) {
            return;
        }

        $payment = $this->paymentModel->where('order_id', (int) $order['id'])->first();
        if ($payment && ($payment['status'] ?? '') === 'paid' && is_online_gateway($payment['payment_method'] ?? '')) {
            return;
        }

        $this->walletService->debit(
            (int) $order['user_id'],
            $fee,
            'delivery_fee',
            $referenceId,
            $label,
            true
        );
    }

    protected function hasLedger(int $orderId, string $type, string $needle = ''): bool
    {
        $builder = $this->db->table('wallet_transactions')
            ->where('reference_id', $orderId)
            ->where('reference_type', $type);
        if ($needle !== '') {
            $builder->like('description', $needle);
        }

        return $builder->get()->getRowArray() !== null;
    }

    protected function grantBuyerCashback(array $order, string $reason = 'paid'): void
    {
        $orderId = (int) ($order['id'] ?? 0);
        if ($orderId <= 0 || $this->hasCashback($orderId)) {
            return;
        }

        $cashbackAmount = isset($order['cashback_amount']) && $order['cashback_amount'] !== null && $order['cashback_amount'] !== ''
            ? round((float) $order['cashback_amount'], 2)
            : cashback_amount((float) ($order['total_amount'] ?? 0));
        if ($cashbackAmount <= 0) {
            return;
        }

        $label = $reason === 'delivered'
            ? 'Product cashback on delivered order '
            : 'Product cashback on paid order ';

        $this->walletService->credit(
            (int) $order['user_id'],
            $cashbackAmount,
            'cashback',
            $orderId,
            $label . ($order['order_number'] ?? '')
        );
    }

    protected function hasCashback(int $orderId): bool
    {
        return ! empty($this->cashbackRow($orderId));
    }

    protected function cashbackRow(int $orderId): ?array
    {
        $row = $this->db->table('wallet_transactions')
            ->where('reference_type', 'cashback')
            ->where('reference_id', $orderId)
            ->get()
            ->getRowArray();

        return $row ?: null;
    }

    protected function platformAdminId(): int
    {
        $ids = $this->adminUserIds();

        return $ids[0] ?? 0;
    }

    protected function adminUserIds(): array
    {
        if ($this->adminUserIdCache === null) {
            $rows = $this->db->table('users')
                ->select('id')
                ->where('role', 'admin')
                ->get()
                ->getResultArray();
            $this->adminUserIdCache = array_map('intval', array_column($rows, 'id'));
        }

        return $this->adminUserIdCache;
    }

    protected function isAdminSeller(int $sellerId): bool
    {
        return $sellerId > 0 && in_array($sellerId, $this->adminUserIds(), true);
    }

    protected function createShipments(int $orderId, array $cartItems, float $shippingAmount): void
    {
        try {
            if (! $this->db->tableExists('shipments')) {
                return;
            }
        } catch (\Throwable $e) {
            log_message('error', 'Shipments table: ' . $e->getMessage());

            return;
        }
        $sellerIds = [];
        foreach ($cartItems as $item) {
            $sellerIds[(int) $item['seller_id']] = true;
        }
        $ids = array_keys($sellerIds);
        $count = count($ids);
        if ($count === 0) {
            return;
        }
        $share = round($shippingAmount / $count, 2);
        $shipmentModel = new ShipmentModel();
        foreach ($ids as $i => $sellerId) {
            $amt = ($i === $count - 1) ? round($shippingAmount - ($share * ($count - 1)), 2) : $share;
            $shipmentModel->insert($this->onlyTableColumns('shipments', [
                'order_id'        => $orderId,
                'seller_id'       => $sellerId,
                'fulfill_by'      => $this->isAdminSeller((int) $sellerId) ? 'admin' : 'seller',
                'status'          => 'placed',
                'shipping_amount' => $amt,
            ]));
        }
    }

    public function cancelByBuyer(int $orderId, int $userId): bool
    {
        $order = $this->orderModel->find($orderId);
        if (! $order || (int) $order['user_id'] !== $userId) {
            throw new RuntimeException('Order not found.');
        }
        if (($order['status'] ?? '') !== 'placed') {
            throw new RuntimeException('Cancel sirf placed order par allowed hai.');
        }
        return $this->updateOrderStatus($orderId, 'cancelled', 'Cancelled by buyer');
    }

    protected function assertCodFraudLimit(int $userId, string $paymentMethod, int $addressId): void
    {
        if ($paymentMethod !== 'cod') {
            return;
        }
        $max = \App\Services\Platform\SettingService::int('cod_max_open', 5);
        $open = $this->db->table('orders')
            ->join('payments', 'payments.order_id = orders.id', 'left')
            ->where('orders.user_id', $userId)
            ->whereIn('orders.status', ['placed', 'confirmed', 'shipped'])
            ->groupStart()
                ->where('payments.payment_method', 'cod')
                ->orWhere('payments.payment_method', null)
            ->groupEnd()
            ->countAllResults();
        if ($open >= $max) {
            throw new RuntimeException('Too many open COD orders. Complete pending parcels first.');
        }
    }

    protected function smsBuyerStatus(array $order, string $newStatus, array $extra = []): void
    {
        try {
            $buyer = (new UserModel())->find((int) ($order['user_id'] ?? 0));
            SmsNotifier::notifyOrderStatus($order, $newStatus, array_merge($extra, [
                'phone' => (string) ($buyer['phone'] ?? ''),
            ]));
            \App\Services\Mail\MailService::orderUpdate(
                (string) ($buyer['email'] ?? ''),
                (string) ($order['order_number'] ?? ''),
                $newStatus
            );
            \App\Services\Platform\AuditService::log('order_status', 'order', (int) ($order['id'] ?? 0), ['status' => $newStatus]);
        } catch (\Throwable $e) {
            log_message('error', 'Order SMS skipped: ' . $e->getMessage());
        }
    }
}
