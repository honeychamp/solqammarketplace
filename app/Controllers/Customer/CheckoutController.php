<?php

namespace App\Controllers\Customer;

use App\Controllers\BaseController;
use App\Models\AddressModel;
use App\Models\CartItemModel;
use App\Models\CartModel;
use App\Services\Checkout\GuestCheckoutService;
use App\Services\Order\OrderService;
use App\Services\Promotion\CouponService;
use App\Services\Shipping\ShippingService;
use App\Services\Wallet\WalletService;
use Exception;

class CheckoutController extends BaseController
{
    protected CartModel $cartModel;
    protected CartItemModel $cartItemModel;
    protected AddressModel $addressModel;
    protected WalletService $walletService;
    protected OrderService $orderService;
    protected ShippingService $shippingService;
    protected CouponService $couponService;

    public function __construct()
    {
        $this->cartModel       = new CartModel();
        $this->cartItemModel   = new CartItemModel();
        $this->addressModel    = new AddressModel();
        $this->walletService   = new WalletService();
        $this->orderService    = new OrderService();
        $this->shippingService = new ShippingService();
        $this->couponService   = new CouponService();
    }

    public function index()
    {
        if (session()->get('user.isLoggedIn') && session()->get('user.role') !== 'customer') {
            return redirect()->to('/')->with('error', 'Customer checkout is for shoppers. Open Guest checkout in a private window, or use a customer account.');
        }
        $userId = $this->sessionUserId();
        $guest  = $userId < 1;
        $cart   = $this->currentCart();
        if (!$cart) {
            return redirect()->to('/cart')->with('error', 'Your cart is empty.');
        }

        $items = $this->cartItemModel->getItemsWithProducts($cart['id']);
        if (empty($items)) {
            return redirect()->to('/cart')->with('error', 'Your cart is empty.');
        }

        $subtotal = 0.0;
        foreach ($items as $item) {
            $subtotal += ((float) $item['unit_price'] * (int) $item['quantity']);
        }

        $addresses     = $userId > 0 ? $this->addressModel->getUserAddresses($userId) : [];
        $walletBalance = $guest ? 0.0 : $this->walletService->getBalance($userId);
        $walletDebt    = $guest ? 0.0 : $this->walletService->getOutstandingDebt($userId);
        $defaultAddr   = $addresses[0] ?? null;
        $draft         = session()->get('checkout_draft') ?? [];
        $draftAddrId   = (string) ($draft['address_id'] ?? '');
        $quoteCity     = null;
        $quoteProv     = null;
        if ($draftAddrId !== '' && $draftAddrId !== 'new') {
            foreach ($addresses as $saved) {
                if ((string) $saved['id'] === $draftAddrId) {
                    $quoteCity = $saved['city'] ?? null;
                    $quoteProv = $saved['province'] ?? null;
                    break;
                }
            }
        } elseif ($draftAddrId === 'new' || $defaultAddr === null) {
            $draftCity = (string) ($draft['city'] ?? '');
            $quoteCity = $draftCity === '__other' ? ($draft['city_other'] ?? '') : $draftCity;
            $quoteProv = $draft['province'] ?? null;
        } elseif ($defaultAddr) {
            $quoteCity = $defaultAddr['city'] ?? null;
            $quoteProv = $defaultAddr['province'] ?? null;
        }
        $shippingQuote = $this->shippingService->quote(
            $quoteCity !== '' ? $quoteCity : null,
            $quoteProv,
            $subtotal
        );

        $couponCode = session()->get('checkout_coupon_code');
        $couponDiscount = 0.0;
        if ($couponCode) {
            try {
                $applied = $this->couponService->apply($couponCode, max(0, $userId), $subtotal);
                $couponDiscount = $applied['discount'];
            } catch (Exception $e) {
                session()->remove('checkout_coupon_code');
                $couponCode = null;
            }
        }

        \App\Services\Marketing\MarketingService::trackCartItems('InitiateCheckout', $items, [
            'value'       => max(0, $subtotal - $couponDiscount),
            'coupon'      => (string) $couponCode,
            'phone'       => (string) (session()->get('user.phone') ?? ''),
            'email'       => (string) (session()->get('user.email') ?? ''),
            'external_id' => (string) $userId,
        ]);

        return view('customer/checkout', [
            'title'          => 'Checkout — Solqam Marketplace',
            'items'          => $items,
            'subtotal'       => $subtotal,
            'addresses'      => $addresses,
            'walletBalance'  => $walletBalance,
            'walletDebt'     => $walletDebt,
            'shippingQuote'  => $shippingQuote,
            'couponCode'     => $couponCode,
            'couponDiscount' => $couponDiscount,
            'estimatedCashback' => cart_cashback_total($items),
            'payfastReady'   => config('Payments')->payfastReady(),
            'shippingZones'  => $this->shippingService->cityList(),
            'isGuest'        => $guest,
        ]);
    }

    public function shippingQuote()
    {
        $userId = $this->sessionUserId();
        $city   = trim((string) $this->request->getGet('city'));
        $province = trim((string) $this->request->getGet('province'));
        $addressId = (int) $this->request->getGet('address_id');

        if ($addressId > 0 && $userId > 0) {
            $addr = $this->addressModel->where('id', $addressId)->where('user_id', $userId)->first();
            if ($addr) {
                $city     = (string) $addr['city'];
                $province = (string) ($addr['province'] ?? '');
            }
        }

        $cart = $this->currentCart();
        $items = $cart ? $this->cartItemModel->getItemsWithProducts((int) $cart['id']) : [];
        $subtotal = 0.0;
        foreach ($items as $item) {
            $subtotal += ((float) $item['unit_price'] * (int) $item['quantity']);
        }

        $quote = $this->shippingService->quote($city !== '' ? $city : null, $province !== '' ? $province : null, $subtotal);

        return $this->response->setJSON($quote);
    }

    public function applyCoupon()
    {
        $userId = $this->sessionUserId();
        $code = (string) $this->request->getPost('coupon_code');
        $cart = $this->currentCart();
        $items = $cart ? $this->cartItemModel->getItemsWithProducts($cart['id']) : [];
        $subtotal = 0.0;
        foreach ($items as $item) {
            $subtotal += ((float) $item['unit_price'] * (int) $item['quantity']);
        }

        $this->rememberCheckoutDraft();
        try {
            $this->couponService->apply($code, max(0, $userId), $subtotal);
            session()->set('checkout_coupon_code', strtoupper(trim($code)));
            \App\Services\Marketing\MarketingService::track('CouponApplied', [
                'coupon' => strtoupper(trim($code)),
                'value'  => $subtotal,
            ]);
            return redirect()->to('/checkout')->withInput()->with('success', 'Voucher applied.');
        } catch (Exception $e) {
            session()->remove('checkout_coupon_code');
            return redirect()->to('/checkout')->withInput()->with('error', $e->getMessage());
        }
    }

    public function process()
    {
        if (session()->get('user.isLoggedIn') && session()->get('user.role') !== 'customer') {
            return redirect()->to('/')->with('error', 'Customer checkout is for shoppers.');
        }
        $loggedIn = $this->sessionUserId();
        $guest    = $loggedIn < 1;
        $this->rememberCheckoutDraft();

        $addressId = $this->request->getPost('address_id');
        $fail      = $this->failCheckoutAddress($addressId);
        if ($fail !== null) {
            return $fail;
        }

        $paymentMethod = $this->request->getPost('payment_method') ?? 'cod';
        $useWallet     = $guest ? false : (bool) $this->request->getPost('use_wallet');
        if ($guest && $paymentMethod === 'pay_later') {
            return $this->checkoutReject([], 'Pay later needs a signed-in wallet. Use COD or Pay online, or Sign In.');
        }

        $userId = $loggedIn;
        if ($guest) {
            $guestSvc = new GuestCheckoutService();
            $userId = $guestSvc->resolveBuyer(
                (string) $this->request->getPost('recipient_name'),
                (string) $this->request->getPost('phone'),
                (string) $this->request->getPost('guest_email')
            );
            $guestSvc->attachSessionCart($userId);
        }

        if ($addressId === 'new') {
            $cityPost = (string) $this->request->getPost('city');
            if ($cityPost === '__other') {
                $cityPost = trim((string) $this->request->getPost('city_other'));
            }

            $addressId = $this->addressModel->insert([
                'user_id'        => $userId,
                'recipient_name' => trim((string) $this->request->getPost('recipient_name')),
                'phone'          => trim((string) $this->request->getPost('phone')),
                'street_address' => trim((string) $this->request->getPost('street_address')),
                'city'           => $cityPost,
                'province'       => trim((string) $this->request->getPost('province')),
                'postal_code'    => trim((string) $this->request->getPost('postal_code')),
                'is_default'     => 1,
            ]);
        }

        $addressId     = (int) $addressId;
        $notes         = $this->request->getPost('notes');
        $couponCode    = session()->get('checkout_coupon_code');

        if (! $addressId) {
            return $this->checkoutReject(['address_id' => 'Please select or enter a shipping address.']);
        }

        try {
            $payLater = [];
            if ($paymentMethod === 'pay_later') {
                if (!$useWallet) {
                    return $this->checkoutReject([], 'Pay later tab milta hai jab wallet use ho aur wallet mein paise kam hon.');
                }
                $cnicFront = $this->storePayLaterFile($this->request->getFile('cnic_front'), 'cnic');
                $cnicBack  = $this->storePayLaterFile($this->request->getFile('cnic_back'), 'cnic');
                $billCopy  = $this->storePayLaterFile($this->request->getFile('utility_bill'), 'bill');
                if (!$cnicFront || !$billCopy) {
                    return $this->checkoutReject(['cnic_front' => 'Upload CNIC copies and a utility bill for Pay later.']);
                }
                $payLater = [
                    'full_name'         => trim((string) $this->request->getPost('pay_later_name')),
                    'cnic_number'       => trim((string) $this->request->getPost('pay_later_cnic')),
                    'phone'             => trim((string) $this->request->getPost('pay_later_phone')),
                    'address_text'      => trim((string) $this->request->getPost('pay_later_address')),
                    'cnic_front_path'   => $cnicFront,
                    'cnic_back_path'    => $cnicBack,
                    'utility_bill_path' => $billCopy,
                ];
            }

            $cartSnap = $this->cartModel->where('user_id', $userId)->first() ?: $this->currentCart();
            $snapItems = $cartSnap ? $this->cartItemModel->getItemsWithProducts((int) $cartSnap['id']) : [];
            $addrRow = $this->addressModel->find($addressId);
            \App\Services\Marketing\MarketingService::trackCartItems('AddPaymentInfo', $snapItems, [
                'payment_type' => (string) $paymentMethod,
                'phone'        => (string) ($this->request->getPost('phone') ?: ($addrRow['phone'] ?? session()->get('user.phone') ?? '')),
                'email'        => (string) (session()->get('user.email') ?? ''),
                'external_id'  => (string) $userId,
                'fbp'          => (string) ($this->request->getPost('fbp') ?? ''),
                'fbc'          => (string) ($this->request->getPost('fbc') ?? ''),
            ]);

            $result = $this->orderService->checkout(
                $userId,
                $addressId,
                $paymentMethod,
                $useWallet,
                $notes,
                $couponCode,
                $payLater
            );

            session()->remove('checkout_coupon_code');
            session()->remove('checkout_draft');
            if ($guest) {
                remember_guest_order((int) $result['order_id']);
            }

            if (! empty($result['redirect']['fields']) && ($result['payment_status'] ?? '') === 'pending') {
                return view('customer/pay_redirect', [
                    'title'  => 'Redirecting to payment — Solqam',
                    'pay'    => $result['redirect'],
                    'orderId'=> $result['order_id'],
                    'orderNo'=> $result['order_number'],
                ]);
            }

            $msg = ($result['payment_status'] ?? '') === 'paid'
                ? "Order placed and payment confirmed. {$result['order_number']}"
                : "Order placed. Payment pending confirmation. {$result['order_number']}";

            return redirect()->to(order_status_url((int) $result['order_id']))->with('success', $msg);
        } catch (Exception $e) {
            return $this->checkoutReject([], $e->getMessage());
        }
    }

    protected function rememberCheckoutDraft(): void
    {
        $post = $this->request->getPost() ?? [];
        unset($post['csrf_test_name'], $post[csrf_token()]);
        session()->set('checkout_draft', $post);
    }

    protected function checkoutReject(array $errors = [], string $error = '')
    {
        $redirect = redirect()->to('/checkout')->withInput();
        if ($errors !== []) {
            $redirect = $redirect->with('errors', $errors);
        }
        if ($error !== '') {
            $redirect = $redirect->with('error', $error);
        }

        return $redirect;
    }

    protected function failCheckoutAddress($addressId)
    {
        if ($addressId !== 'new' && (int) $addressId > 0) {
            return null;
        }

        $errors   = [];
        $name     = trim((string) $this->request->getPost('recipient_name'));
        $phone    = trim((string) $this->request->getPost('phone'));
        $street   = trim((string) $this->request->getPost('street_address'));
        $city     = trim((string) $this->request->getPost('city'));
        $cityOther= trim((string) $this->request->getPost('city_other'));
        $province = trim((string) $this->request->getPost('province'));
        $postal   = trim((string) $this->request->getPost('postal_code'));

        if ($name === '' || strlen($name) < 2) {
            $errors['recipient_name'] = 'Recipient full name is required.';
        }
        $digits = preg_replace('/\D+/', '', $phone) ?? '';
        if ($digits === '' || strlen($digits) < 10 || strlen($digits) > 12) {
            $errors['phone'] = 'Enter a valid mobile number (e.g. 03XXXXXXXXX).';
        }
        $guestEmail = trim((string) $this->request->getPost('guest_email'));
        if ($guestEmail !== '' && ! filter_var($guestEmail, FILTER_VALIDATE_EMAIL)) {
            $errors['guest_email'] = 'Enter a valid email or leave it blank.';
        }
        if ($street === '' || strlen($street) < 8) {
            $errors['street_address'] = 'Street / house address is required.';
        }
        $resolvedCity = $city === '__other' ? $cityOther : $city;
        if ($resolvedCity === '') {
            $errors['city'] = 'City is required so delivery charges can be applied.';
        }
        if ($province === '') {
            $errors['province'] = 'Province is required.';
        }
        if ($postal === '' || ! preg_match('/^[0-9]{4,6}$/', $postal)) {
            $errors['postal_code'] = 'Postal code is required (4–6 digits).';
        }

        return $errors === [] ? null : $this->checkoutReject($errors);
    }

    protected function storePayLaterFile($file, string $prefix): ?string
    {
        if (!$file || ! $file->isValid() || $file->hasMoved()) {
            return null;
        }
        $ext = strtolower($file->getExtension());
        if (!in_array($ext, ['jpg', 'jpeg', 'png', 'webp', 'pdf'], true)) {
            throw new Exception('Only JPG, PNG, WEBP or PDF files are allowed for Pay later documents.');
        }
        if ($file->getSize() > 4 * 1024 * 1024) {
            throw new Exception('Each Pay later file must be 4 MB or smaller.');
        }
        $uploadDir = FCPATH . 'uploads/pay-later';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }
        $newName = $prefix . '_' . time() . '_' . bin2hex(random_bytes(4)) . '.' . $ext;
        $file->move($uploadDir, $newName);

        return base_url('uploads/pay-later/' . $newName);
    }

    public function thanks($id)
    {
        $orderId = (int) $id;
        $order   = (new \App\Models\OrderModel())->getOrderDetail($orderId);
        $userId  = $this->sessionUserId();
        $ok      = $order && (guest_can_see_order($orderId) || ($userId > 0 && (int) $order['user_id'] === $userId));
        if (! $ok) {
            return redirect()->to('/track')->with('error', 'Enter your order number to track this order.');
        }

        return view('customer/order_thanks', [
            'title' => 'Order placed — Solqam',
            'order' => $order,
        ]);
    }

    public function pay($orderId)
    {
        $userId = $this->sessionUserId();
        $order = (new \App\Models\OrderModel())->getOrderDetail((int) $orderId);
        $allowed = $order && (
            ($userId > 0 && (int) $order['user_id'] === $userId)
            || guest_can_see_order((int) $orderId)
        );
        if (! $allowed) {
            throw \CodeIgniter\Exceptions\PageNotFoundException::forPageNotFound('Order not found');
        }
        if (($order['payment_status'] ?? '') === 'paid') {
            return redirect()->to(order_status_url((int) $orderId))->with('success', 'This order is already paid.');
        }

        $redirect = $this->orderService->rebuildGatewayRedirect((int) $orderId);
        if (! $redirect) {
            return redirect()->to(order_status_url((int) $orderId))->with('error', 'This order does not need online payment.');
        }

        return view('customer/pay_redirect', [
            'title'  => 'Complete payment — Solqam',
            'pay'    => $redirect,
            'orderId'=> (int) $orderId,
            'orderNo'=> $order['order_number'],
        ]);
    }

    protected function sessionUserId(): int
    {
        return session()->get('user.isLoggedIn') ? (int) session()->get('user.id') : 0;
    }

    protected function currentCart(): ?array
    {
        $userId = $this->sessionUserId();
        if ($userId > 0) {
            $cart = $this->cartModel->where('user_id', $userId)->first();
            if ($cart) {
                return $cart;
            }
        }
        $sid = session_id();
        if ($sid === '') {
            return null;
        }

        return $this->cartModel->where('session_id', $sid)->first();
    }
}
