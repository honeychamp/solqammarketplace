<?php

namespace App\Controllers\Payments;

use App\Controllers\BaseController;
use App\Services\Order\OrderService;
use App\Services\Payment\GatewayFactory;

class CallbackController extends BaseController
{
    protected OrderService $orderService;

    public function __construct()
    {
        $this->orderService = new OrderService();
    }

    public function payfastSuccess()
    {
        return $this->handlePayfast(true);
    }

    public function payfastFailure()
    {
        return $this->handlePayfast(false);
    }

    public function payfastIpn()
    {
        $payload = $this->payload();
        $verified = GatewayFactory::make('payfast')->verifyPayment($payload);
        $this->orderService->confirmOnlinePayment($verified, 'payfast');

        return $this->response->setStatusCode(200)->setBody('OK');
    }

    protected function handlePayfast(bool $expectSuccess)
    {
        $payload = $this->payload();
        $verified = GatewayFactory::make('payfast')->verifyPayment($payload);
        if (! $expectSuccess) {
            $verified['success'] = false;
            $verified['message'] = $verified['message'] ?: 'PayFast payment cancelled or failed.';
        }
        $ok = $this->orderService->confirmOnlinePayment($verified, 'payfast');
        $orderId = $this->resolveOrderId($verified);

        if ($ok && ($verified['success'] ?? false)) {
            return $this->finish($orderId, 'Payment confirmed via PayFast.');
        }

        return $this->finish($orderId, $verified['message'] ?? 'Payment not confirmed yet. COD orders do not use PayFast.', false);
    }

    protected function payload(): array
    {
        $json = $this->request->getJSON(true);
        if (is_array($json) && $json !== []) {
            return $json;
        }

        return array_merge($this->request->getGet() ?? [], $this->request->getPost() ?? []);
    }

    protected function resolveOrderId(array $verified): ?int
    {
        if (! empty($verified['order_id'])) {
            return (int) $verified['order_id'];
        }
        if (empty($verified['order_number'])) {
            return null;
        }
        $order = (new \App\Models\OrderModel())->where('order_number', $verified['order_number'])->first();

        return $order ? (int) $order['id'] : null;
    }

    protected function finish(?int $orderId, string $message, bool $success = true)
    {
        $target = $orderId ? '/account/orders/' . $orderId : '/account/orders';
        if (session()->get('user.isLoggedIn')) {
            return redirect()->to($target)->with($success ? 'success' : 'error', $message);
        }

        return view('customer/payment_result', [
            'title'   => 'Payment result — Solqam',
            'success' => $success,
            'message' => $message,
            'orderId' => $orderId,
        ]);
    }
}
