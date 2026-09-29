<?php

namespace App\Controllers\Seller;

use App\Controllers\BaseController;
use App\Models\OrderItemModel;
use App\Models\OrderModel;
use App\Services\Order\OrderService;

class OrderController extends BaseController
{
    protected OrderModel $orderModel;
    protected OrderItemModel $orderItemModel;
    protected OrderService $orderService;

    public function __construct()
    {
        $this->orderModel     = new OrderModel();
        $this->orderItemModel = new OrderItemModel();
        $this->orderService   = new OrderService();
    }

    public function index()
    {
        $sellerId = (int) session()->get('user.id');
        $items = $this->orderItemModel->getSellerOrderItems($sellerId);

        return view('seller/orders/index', [
            'title' => 'Incoming Orders — Solqam Seller Hub',
            'items' => $items,
        ]);
    }

    public function show($orderId)
    {
        $sellerId = (int) session()->get('user.id');
        $order = $this->orderModel->getOrderDetail((int) $orderId);

        if (!$order) {
            return redirect()->to('/seller/orders')->with('error', 'Order not found.');
        }

        // Filter items to show seller's items
        $sellerItems = array_filter($order['items'], fn($it) => (int) $it['seller_id'] === $sellerId);
        if (empty($sellerItems)) {
            return redirect()->to('/seller/orders')->with('error', 'Access denied.');
        }

        $shipment = null;
        foreach ($order['shipments'] ?? [] as $pkg) {
            if ((int) $pkg['seller_id'] === $sellerId) {
                $shipment = $pkg;
                break;
            }
        }

        return view('seller/orders/detail', [
            'title'       => "Order {$order['order_number']} — Solqam Seller Hub",
            'order'       => $order,
            'sellerItems' => $sellerItems,
            'shipment'    => $shipment,
        ]);
    }

    public function updateStatus($orderId)
    {
        $sellerId = (int) session()->get('user.id');
        $order = $this->orderModel->find((int) $orderId);

        if (!$order) {
            return redirect()->to('/seller/orders')->with('error', 'Order not found.');
        }

        $newStatus = $this->request->getPost('status');
        $sellerAllowedStatuses = ['confirmed', 'shipped', 'delivered'];
        if (!in_array($newStatus, $sellerAllowedStatuses, true)) {
            return redirect()->back()->with('error', 'Invalid status. Sellers may update to: Confirmed, Shipped, or Delivered.');
        }

        try {
            $this->orderService->updateSellerShipment((int) $orderId, $sellerId, $newStatus, [
                'tracking_number' => $this->request->getPost('tracking_number'),
                'courier'         => $this->request->getPost('courier'),
            ]);
            return $this->redirectAfterHub('/seller/orders/' . $orderId, 'success', 'Your package status is now ' . ucfirst($newStatus));
        } catch (\Exception $e) {
            return $this->redirectAfterHub('/seller/orders/' . $orderId, 'error', $e->getMessage());
        }
    }

    public function slip($orderId)
    {
        $sellerId = (int) session()->get('user.id');
        $order = $this->orderModel->getOrderDetail((int) $orderId);
        if (! $order) {
            return redirect()->to('/seller/orders')->with('error', 'Order not found.');
        }
        $sellerItems = array_filter($order['items'], fn ($it) => (int) $it['seller_id'] === $sellerId);
        if ($sellerItems === []) {
            return redirect()->to('/seller/orders')->with('error', 'Access denied.');
        }

        return view('seller/orders/slip', [
            'title'       => 'Packing slip ' . $order['order_number'],
            'order'       => $order,
            'sellerItems' => $sellerItems,
        ]);
    }
}
