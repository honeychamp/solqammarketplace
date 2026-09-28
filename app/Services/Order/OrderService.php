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
        $payableBeforeWallet = $goodsAfterDiscount + $shippingAmount;

        $walletBalance = $this->walletService->getBalance($userId);
        $walletAmountUsed = 0.0;
        if ($useWallet && $walletBalance > 0) {
            $walletAmountUsed = min($walletBalance, $payableBeforeWallet);
        }

        $finalPayable = max(0.0, $payableBeforeWallet - $walletAmountUsed);

        if ($finalPayable <= 0) {
            $paymentMethod = 'wallet';
        } elseif ($paymentMethod === 'wallet') {
            throw new RuntimeException('Wallet balance is less than the order total. Choose Cash on Delivery for the remaining amount.');
        } elseif ($paymentMethod === 'payfast') {
            if (! config('Payments')->payfastReady()) {
                throw new RuntimeException('Online payment (PayFast) abhi on nahi. Abhi Cash on Delivery use karein.');
            }
        } elseif ($paymentMethod === 'pay_later') {
            if (!$useWallet || $walletAmountUsed <= 0 || $finalPayable <= 0) {
                throw new RuntimeException('Pay later tab available hai jab wallet use ho aur order total wallet se zyada ho. Baqi JazzCash, EasyPaisa, card, COD, ya Pay later se de sakte ho.');
            }
            if (empty($payLater['full_name']) || empty($payLater['cnic_number']) || empty($payLater['cnic_front_path']) || empty($payLater['utility_bill_path'])) {
                throw new RuntimeException('Pay later requires full name, CNIC number, CNIC copy, and a utility bill copy.');
            }
        }

        $commissionRate = $this->commissionService->getCommissionRate();
        $commissionAmount = 0.0;
        $cashbackTotal = 0.0;
        foreach ($cartItems as $item) {
            $line = (float) $item['unit_price'] * (int) $item['quantity'];
            $cashbackTotal += cashback_amount($line, $item);
            if ($this->isAdminSeller((int) $item['seller_id'])) {
                continue;
            }
            $commissionAmount += commission_amount($line, false, $commissionRate);
        }
        $commissionAmount = round($commissionAmount, 2);
        $cashbackTotal = round($cashbackTotal, 2);
        $orderNumber = 'SOL-' . strtoupper(date('ymd')) . '-' . strtoupper(substr(md5(uniqid()), 0, 5));

        $this->db->transStart();

        try {
            $orderId = $this->orderModel->insert([
                'order_number'       => $orderNumber,
                'user_id'            => $userId,
                'address_id'         => $addressId,
                'total_amount'       => $totalAmount,
                'discount_amount'    => $couponDiscount,
                'shipping_amount'    => $shippingAmount,
                'coupon_id'          => $couponId,
                'coupon_code'        => $normalizedCoupon,
                'coupon_discount'    => $couponDiscount,
                'wallet_amount_used' => $walletAmountUsed,
                'final_payable'      => $finalPayable,
                'commission_rate'    => $commissionRate,
                'commission_amount'  => $commissionAmount,
                'cashback_amount'    => $cashbackTotal,
                'status'             => 'placed',
                'notes'              => $notes,
            ]);

            foreach ($cartItems as $item) {
                $subtotal = (float) $item['unit_price'] * (int) $item['quantity'];
                $itemCommission = $this->isAdminSeller((int) $item['seller_id'])
                    ? 0.0
                    : commission_amount($subtotal, false, $commissionRate);
                $itemCashbackRate = cashback_rate($item);
                $itemCashback = cashback_amount($subtotal, $itemCashbackRate);

                $this->orderItemModel->insert([
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
                ]);

                $this->db->table('products')
                    ->where('id', $item['product_id'])
                    ->decrement('stock', $item['quantity']);
                $this->db->table('products')
                    ->where('id', $item['product_id'])
                    ->increment('sold_count', $item['quantity']);

                if (!empty($item['variant_id'])) {
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

            $this->paymentModel->insert([
                'order_id'         => $orderId,
                'payment_method'   => $paymentMethod,
                'transaction_ref'  => $transactionRef,
                'amount'           => $finalPayable,
                'status'           => $paymentStatus,
                'gateway_response' => $gatewayResponse,
                'paid_at'          => ($paymentStatus === 'paid') ? date('Y-m-d H:i:s') : null,
            ]);

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
                throw new RuntimeException('Checkout transaction failed.');
            }

            $buyer = (new UserModel())->find($userId);
            SmsNotifier::orderPlaced(
                (string) ($buyer['phone'] ?? ''),
                $orderNumber,
                $paymentMethod,
                $finalPayable,
                $paymentStatus
            );

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
        }
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
            throw new RuntimeException('PayFast ne payment confirm nahi ki. Paid hone ke baad hi order confirm/ship hoga.');
        }
    }

    public function updateOrderStatus(int $orderId, string $newStatus, ?string $note = null, array $extra = []): bool
    {
        $allowedTransitions = ['placed', 'confirmed', 'shipped', 'delivered', 'cancelled', 'returned'];
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
            if (($method === 'cod' || ($payment['status'] ?? '') !== 'paid')) {
                $this->settleSellerFunds((int) $orderId, 'cod');
            } else {
                $this->settleSellerFunds((int) $orderId, 'online');
            }
        } elseif ($newStatus === 'cancelled') {
            $items = $this->orderItemModel->where('order_id', $orderId)->findAll();
            foreach ($items as $item) {
                $this->db->table('products')
                    ->where('id', $item['product_id'])
                    ->increment('stock', $item['quantity']);
                if (!empty($item['variant_id'])) {
                    $this->db->table('product_variants')
                        ->where('id', $item['variant_id'])
                        ->increment('stock', $item['quantity']);
                }
            }

            if ((float) $order['wallet_amount_used'] > 0) {
                $this->walletService->credit(
                    (int) $order['user_id'],
                    (float) $order['wallet_amount_used'],
                    'refund',
                    $orderId,
                    "Refund of wallet amount for Cancelled Order {$order['order_number']}"
                );
            }

            $cb = $this->cashbackRow((int) $orderId);
            if ($cb && (float) $cb['amount'] > 0) {
                try {
                    $this->walletService->debit(
                        (int) $order['user_id'],
                        (float) $cb['amount'],
                        'refund',
                        $orderId,
                        "Reverse cashback for Cancelled Order {$order['order_number']}"
                    );
                } catch (\Throwable $e) {
                    // Ledger already spent; order still cancels.
                }
            }
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

    public function updateSellerShipment(int $orderId, int $sellerId, string $newStatus, array $extra = []): bool
    {
        $allowed = ['confirmed', 'shipped', 'delivered'];
        if (!in_array($newStatus, $allowed, true)) {
            throw new RuntimeException('Sellers may update to Confirmed, Shipped, or Delivered.');
        }
        $this->assertOnlinePaymentCleared($orderId);

        $shipmentModel = new ShipmentModel();
        $shipment = $shipmentModel->where('order_id', $orderId)->where('seller_id', $sellerId)->first();
        if (!$shipment) {
            $shipmentId = $shipmentModel->insert([
                'order_id'  => $orderId,
                'seller_id' => $sellerId,
                'status'    => 'placed',
            ]);
            $shipment = $shipmentModel->find($shipmentId);
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
            if ($method === 'cod' || $method === 'pay_later') {
                $this->settleSellerFunds($orderId, 'cod', $sellerId);
            }
        }

        $packages = $shipmentModel->where('order_id', $orderId)->findAll();
        if ($packages === []) {
            return $this->updateOrderStatus($orderId, $newStatus, null, $extra);
        }

        $statuses = array_column($packages, 'status');
        if (!in_array('placed', $statuses, true) && !in_array('confirmed', $statuses, true) && !in_array('shipped', $statuses, true) && count(array_unique($statuses)) === 1 && $statuses[0] === 'delivered') {
            return $this->updateOrderStatus($orderId, 'delivered', null, $extra);
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

        $payoutModel = new SellerPayoutModel();
        $adminId = $this->platformAdminId();
        $order = $this->orderModel->find($orderId);
        $orderNumber = $order['order_number'] ?? ('#' . $orderId);

        foreach ($bySeller as $sellerId => $totals) {
            if ($this->isAdminSeller((int) $sellerId)) {
                continue;
            }

            $existing = $payoutModel->where('order_id', $orderId)->where('seller_id', $sellerId)->first();
            if ($existing && ($existing['status'] ?? '') === 'paid') {
                continue;
            }

            $commission = round($totals['commission'], 2);
            $cashback = round($totals['cashback'], 2);
            $net = round(max(0.0, $totals['goods'] - $commission - $cashback), 2);

            if ($commission > 0 && $adminId > 0) {
                $this->walletService->credit(
                    $adminId,
                    $commission,
                    'commission',
                    $orderId,
                    "Platform commission from order {$orderNumber}"
                );
            }

            if ($mode === 'online' && $net > 0) {
                $this->walletService->credit(
                    $sellerId,
                    $net,
                    'seller_payout',
                    $orderId,
                    "Auto payout after paid order {$orderNumber} (cashback + commission cut)"
                );
            }

            $row = [
                'seller_id' => $sellerId,
                'order_id'  => $orderId,
                'amount'    => $net,
                'status'    => 'paid',
                'paid_at'   => date('Y-m-d H:i:s'),
            ];
            if ($existing) {
                $payoutModel->update($existing['id'], $row);
            } else {
                $payoutModel->insert($row);
            }
        }
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
            $shipmentModel->insert([
                'order_id'         => $orderId,
                'seller_id'        => $sellerId,
                'status'           => 'placed',
                'shipping_amount'  => $amt,
            ]);
        }
    }

    protected function smsBuyerStatus(array $order, string $newStatus, array $extra = []): void
    {
        try {
            $buyer = (new UserModel())->find((int) ($order['user_id'] ?? 0));
            SmsNotifier::notifyOrderStatus($order, $newStatus, array_merge($extra, [
                'phone' => (string) ($buyer['phone'] ?? ''),
            ]));
        } catch (\Throwable $e) {
            log_message('error', 'Order SMS skipped: ' . $e->getMessage());
        }
    }
}
