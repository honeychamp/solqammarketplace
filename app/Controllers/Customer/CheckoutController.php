<?php

namespace App\Controllers\Customer;

use App\Controllers\BaseController;
use App\Models\AddressModel;
use App\Models\CartItemModel;
use App\Models\CartModel;
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
        $userId = (int) session()->get('user.id');
        $cart = $this->cartModel->where('user_id', $userId)->first();
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

        $addresses     = $this->addressModel->getUserAddresses($userId);
        $walletBalance = $this->walletService->getBalance($userId);
        $walletDebt    = $this->walletService->getOutstandingDebt($userId);
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
                $applied = $this->couponService->apply($couponCode, $userId, $subtotal);
                $couponDiscount = $applied['discount'];
            } catch (Exception $e) {
                session()->remove('checkout_coupon_code');
                $couponCode = null;
            }
        }

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
        ]);
    }

    public function shippingQuote()
    {
        $userId = (int) session()->get('user.id');
        $city   = trim((string) $this->request->getGet('city'));
        $province = trim((string) $this->request->getGet('province'));
        $addressId = (int) $this->request->getGet('address_id');

        if ($addressId > 0) {
            $addr = $this->addressModel->where('id', $addressId)->where('user_id', $userId)->first();
            if ($addr) {
                $city     = (string) $addr['city'];
                $province = (string) ($addr['province'] ?? '');
            }
        }

        $cart = $this->cartModel->where('user_id', $userId)->first();
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
        $userId = (int) session()->get('user.id');
        $code = (string) $this->request->getPost('coupon_code');
        $cart = $this->cartModel->where('user_id', $userId)->first();
        $items = $cart ? $this->cartItemModel->getItemsWithProducts($cart['id']) : [];
        $subtotal = 0.0;
        foreach ($items as $item) {
            $subtotal += ((float) $item['unit_price'] * (int) $item['quantity']);
        }

        $this->rememberCheckoutDraft();
        try {
            $this->couponService->apply($code, $userId, $subtotal);
            session()->set('checkout_coupon_code', strtoupper(trim($code)));
            return redirect()->to('/checkout')->withInput()->with('success', 'Voucher applied.');
        } catch (Exception $e) {
            session()->remove('checkout_coupon_code');
            return redirect()->to('/checkout')->withInput()->with('error', $e->getMessage());
        }
    }

    public function process()
    {
        $userId = (int) session()->get('user.id');
        $this->rememberCheckoutDraft();

        $addressId = $this->request->getPost('address_id');
        $fail      = $this->failCheckoutAddress($addressId);
        if ($fail !== null) {
            return $fail;
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
        $paymentMethod = $this->request->getPost('payment_method') ?? 'cod';
        $useWallet     = (bool) $this->request->getPost('use_wallet');
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

            return redirect()->to('/account/orders/' . $result['order_id'])->with('success', $msg);
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

    public function pay($orderId)
    {
        $userId = (int) session()->get('user.id');
        $order = (new \App\Models\OrderModel())->getOrderDetail((int) $orderId);
        if (! $order || (int) $order['user_id'] !== $userId) {
            throw \CodeIgniter\Exceptions\PageNotFoundException::forPageNotFound('Order not found');
        }
        if (($order['payment_status'] ?? '') === 'paid') {
            return redirect()->to('/account/orders/' . $orderId)->with('success', 'This order is already paid.');
        }

        $redirect = $this->orderService->rebuildGatewayRedirect((int) $orderId);
        if (! $redirect) {
            return redirect()->to('/account/orders/' . $orderId)->with('error', 'This order does not need online payment.');
        }

        return view('customer/pay_redirect', [
            'title'  => 'Complete payment — Solqam',
            'pay'    => $redirect,
            'orderId'=> (int) $orderId,
            'orderNo'=> $order['order_number'],
        ]);
    }
}
