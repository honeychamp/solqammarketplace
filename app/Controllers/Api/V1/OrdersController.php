<?php

namespace App\Controllers\Api\V1;

use App\Models\OrderModel;
use App\Services\Order\OrderService;
use Exception;

class OrdersController extends BaseApiController
{
    protected OrderModel $orderModel;
    protected OrderService $orderService;

    public function __construct()
    {
        $this->orderModel   = new OrderModel();
        $this->orderService = new OrderService();
    }

    public function index()
    {
        $user = $this->getAuthenticatedUser();
        if (!$user) {
            return $this->respondFail('Unauthorized.', null, 401);
        }

        $userId = (int) $user['id'];
        $role   = $user['role'];

        if ($role === 'admin') {
            $orders = $this->orderModel->orderBy('id', 'DESC')->findAll(100);
        } else {
            $orders = $this->orderModel->getCustomerOrders($userId);
        }

        return $this->respondSuccess($orders, 'Orders retrieved.');
    }

    public function show($id = null)
    {
        $user = $this->getAuthenticatedUser();
        if (!$user) {
            return $this->respondFail('Unauthorized.', null, 401);
        }

        $order = $this->orderModel->getOrderDetail((int) $id);
        if (!$order) {
            return $this->respondFail('Order not found.', null, 404);
        }

        // Access check
        if ($user['role'] !== 'admin' && (int) $order['user_id'] !== (int) $user['id']) {
            return $this->respondFail('Access denied.', null, 403);
        }

        return $this->respondSuccess($order, 'Order details.');
    }

    public function checkout()
    {
        $user = $this->getAuthenticatedUser();
        if (!$user) {
            return $this->respondFail('Authentication required to checkout.', null, 401);
        }

        $input = $this->request->getJSON(true) ?? $this->request->getPost();
        $addressId     = (int) ($input['address_id'] ?? 0);
        $paymentMethod = $input['payment_method'] ?? 'cod';
        $useWallet     = (bool) ($input['use_wallet'] ?? false);
        $notes         = $input['notes'] ?? null;

        if (!$addressId) {
            return $this->respondFail('Shipping address ID is required.', null, 422);
        }

        try {
            $result = $this->orderService->checkout(
                (int) $user['id'],
                $addressId,
                $paymentMethod,
                $useWallet,
                $notes
            );

            return $this->respondSuccess($result, 'Order placed successfully!', 201);

        } catch (Exception $e) {
            return $this->respondFail($e->getMessage(), null, 400);
        }
    }
}
