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
        [$items, $pager] = hub_paginate_array($items, 25);

        return view('seller/orders/index', [
            'title' => 'Incoming Orders — Solqam Seller Hub',
            'items' => $items,
            'pager' => $pager,
        ]);
    }

    public function export()
    {
        try {
            @ini_set('max_execution_time', '180');
            @ini_set('memory_limit', '512M');
            $sellerId = (int) session()->get('user.id');
            $svc = new \App\Services\Export\OrderExportService();
            $filters = $svc->filtersFromRequest($this->request, $sellerId);
            $stamp = date('Ymd_His');
            $name = 'Solqam_Store_Orders_';

            if (($filters['format'] ?? 'xlsx') === 'csv') {
                return $svc->download(array_merge($filters, ['format' => 'csv']), $sellerId, $name . $stamp);
            }

            $pack = $svc->build($filters, $sellerId);
            return (new \App\Controllers\Admin\OrderController())->sendOrderSpreadsheet(
                $pack,
                $name . $stamp,
                \App\Services\Export\OrderExportService::HEADERS,
                \App\Services\Export\OrderExportService::GROUPS
            );
        } catch (\Throwable $e) {
            log_message('error', 'Seller order export: ' . $e->getMessage());

            return redirect()->to(site_url('seller/orders'))->with('error', 'Export failed: ' . $e->getMessage());
        }
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

    public function editPlaced($orderId)
    {
        $sellerId = (int) session()->get('user.id');
        $order = $this->orderModel->getOrderDetail((int) $orderId);
        if (! $order) {
            return redirect()->to('/seller/orders')->with('error', 'Order not found.');
        }
        $mine = array_filter($order['items'] ?? [], static fn ($it) => (int) ($it['seller_id'] ?? 0) === $sellerId);
        if ($mine === []) {
            return redirect()->to('/seller/orders')->with('error', 'Access denied.');
        }
        $pkgStatus = 'placed';
        $fulfillBy = 'seller';
        foreach ($order['shipments'] ?? [] as $pkg) {
            if ((int) ($pkg['seller_id'] ?? 0) === $sellerId) {
                $pkgStatus = (string) ($pkg['status'] ?? 'placed');
                $fulfillBy = (string) ($pkg['fulfill_by'] ?? 'seller');
                break;
            }
        }
        if ($pkgStatus !== 'placed') {
            return $this->redirectAfterHub('/seller/orders/' . $orderId, 'error', 'You can edit only a placed package, before confirm.');
        }
        if ($fulfillBy === 'admin') {
            return $this->redirectAfterHub('/seller/orders/' . $orderId, 'error', 'Solqam will deliver this package. Admin will edit and confirm it.');
        }

        $qty = $this->request->getPost('qty') ?? [];
        $quantities = [];
        if (is_array($qty)) {
            foreach ($qty as $itemId => $n) {
                $quantities[(int) $itemId] = (int) $n;
            }
        }
        $address = [
            'recipient_name'  => (string) $this->request->getPost('recipient_name'),
            'phone'           => (string) $this->request->getPost('phone'),
            'street_address'  => (string) $this->request->getPost('street_address'),
            'city'            => (string) $this->request->getPost('city'),
            'province'        => (string) $this->request->getPost('province'),
            'postal_code'     => (string) $this->request->getPost('postal_code'),
            'alternate_phone' => (string) $this->request->getPost('alternate_phone'),
            'area'            => (string) $this->request->getPost('area'),
            'landmark'        => (string) $this->request->getPost('landmark'),
        ];
        $notes = (string) $this->request->getPost('notes');
        try {
            $this->orderService->editPlacedOrder((int) $orderId, $quantities, $address, $notes, [$sellerId]);

            return $this->redirectAfterHub('/seller/orders/' . $orderId, 'success', 'Order updated. Now tap Confirm (Accept Order). Commission is not due until you confirm.');
        } catch (\Throwable $e) {
            return $this->redirectAfterHub('/seller/orders/' . $orderId, 'error', $e->getMessage());
        }
    }

    public function updateStatus($orderId)
    {
        $sellerId = (int) session()->get('user.id');
        $order = $this->orderModel->find((int) $orderId);

        if (!$order) {
            return redirect()->to('/seller/orders')->with('error', 'Order not found.');
        }

        $newStatus = $this->request->getPost('status');
        $sellerAllowedStatuses = ['confirmed', 'shipped', 'delivered', 'undelivered'];
        if (!in_array($newStatus, $sellerAllowedStatuses, true)) {
            return redirect()->back()->with('error', 'Invalid status. Use Confirmed, Shipped, Delivered, or Not received.');
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

    public function setFulfill($orderId)
    {
        $sellerId = (int) session()->get('user.id');
        $by       = (string) $this->request->getPost('fulfill_by');
        try {
            $this->orderService->setFulfillBy((int) $orderId, $sellerId, $by);
            $msg = $by === 'admin'
                ? 'Package assigned to Solqam delivery. Send the parcel to Solqam. Admin will deliver and keep the delivery fee.'
                : 'You will deliver this package. After delivery you keep the delivery fee; category commission still goes to Solqam.';

            return $this->redirectAfterHub('/seller/orders/' . $orderId, 'success', $msg);
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
