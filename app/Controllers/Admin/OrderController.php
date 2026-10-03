<?php

namespace App\Controllers\Admin;

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
        return $this->renderOrderList(false);
    }

    public function mine()
    {
        return $this->renderOrderList(true);
    }

    protected function renderOrderList(bool $mineOnly)
    {
        $adminId = (int) session()->get('user.id');
        $status  = $this->request->getGet('status');
        $builder = $this->orderModel
            ->select('orders.*, users.name as customer_name, users.phone as customer_phone')
            ->join('users', 'users.id = orders.user_id')
            ->orderBy('orders.id', 'DESC');

        if ($mineOnly) {
            $idRows = $this->orderItemModel
                ->select('order_id')
                ->where('seller_id', $adminId)
                ->groupBy('order_id')
                ->findAll();
            $ids = array_column($idRows, 'order_id');
            if (empty($ids)) {
                $builder->where('orders.id', 0);
            } else {
                $builder->whereIn('orders.id', $ids);
            }
        }

        if (!empty($status)) {
            $builder->where('orders.status', $status);
        }

        $orders = $builder->findAll(150);

        foreach ($orders as &$order) {
            $items = $this->orderItemModel
                ->select('order_items.seller_id, order_items.fulfillment_status, seller_profiles.store_name, users.name as seller_name')
                ->join('seller_profiles', 'seller_profiles.user_id = order_items.seller_id', 'left')
                ->join('users', 'users.id = order_items.seller_id', 'left')
                ->where('order_items.order_id', $order['id'])
                ->groupBy('order_items.seller_id, order_items.fulfillment_status, seller_profiles.store_name, users.name')
                ->findAll();
            $order['sellers'] = $items;
        }
        unset($order);

        return view('admin/orders/index', [
            'title'         => $mineOnly ? 'My Orders — Solqam Admin Store' : 'Orders Monitor — Solqam Admin Console',
            'orders'        => $orders,
            'currentStatus' => $status,
            'mineOnly'      => $mineOnly,
        ]);
    }

    public function show($id)
    {
        $adminId = (int) session()->get('user.id');
        $order   = $this->orderModel->getOrderDetail((int) $id);

        if (!$order) {
            return redirect()->to('/admin/orders')->with('error', 'Order not found.');
        }

        $hasAdminItems = false;
        foreach ($order['items'] as &$item) {
            $item['is_admin_item'] = ((int) ($item['seller_id'] ?? 0) === $adminId);
            if ($item['is_admin_item']) {
                $hasAdminItems = true;
            }
        }
        unset($item);

        return view('admin/orders/show', [
            'title'         => "Order #{$order['order_number']} — Solqam Admin",
            'order'         => $order,
            'adminId'       => $adminId,
            'hasAdminItems' => $hasAdminItems,
            'fromMine'      => $this->request->getGet('from') === 'mine',
        ]);
    }

    public function updateStatus($id)
    {
        $adminId       = (int) session()->get('user.id');
        $newStatus     = $this->request->getPost('status');
        $validStatuses = ['placed', 'confirmed', 'shipped', 'delivered', 'cancelled', 'undelivered'];

        if (!in_array($newStatus, $validStatuses, true)) {
            return redirect()->back()->with('error', 'Invalid status value.');
        }

        $items       = $this->orderItemModel->where('order_id', (int) $id)->findAll();
        $adminItems  = array_filter($items, static fn ($row) => (int) $row['seller_id'] === $adminId);
        $sellerItems = array_filter($items, static fn ($row) => (int) $row['seller_id'] !== $adminId);

        if ($adminItems === []) {
            return $this->redirectAfterHub(
                '/admin/orders/' . $id,
                'error',
                'You can only change fulfillment on your own products. Seller packages are view-only.'
            );
        }

        try {
            if ($sellerItems === []) {
                $this->orderService->updateOrderStatus((int) $id, $newStatus);
            } else {
                if ($newStatus === 'cancelled') {
                    return $this->redirectAfterHub(
                        '/admin/orders/' . $id,
                        'error',
                        'This order also has seller items. Cancel is not allowed from admin for mixed orders.'
                    );
                }
                if ($newStatus === 'placed') {
                    return $this->redirectAfterHub('/admin/orders/' . $id, 'error', 'Use Confirmed, Shipped, or Delivered for your package.');
                }
                $this->orderService->updateSellerShipment((int) $id, $adminId, $newStatus);
            }

            $msg = 'Your product package is now ' . ucfirst($newStatus) . '.';
            if ($newStatus === 'delivered') {
                $msg .= ' Buyer gets the cashback % set on each product when the full order is delivered. No commission on your store items.';
            }

            $back = $this->request->getPost('mine') ? '/admin/my-orders' : '/admin/orders/' . $id;

            return $this->redirectAfterHub($back, 'success', $msg);
        } catch (\Exception $e) {
            $back = $this->request->getPost('mine') ? '/admin/my-orders' : '/admin/orders/' . $id;

            return $this->redirectAfterHub($back, 'error', $e->getMessage());
        }
    }
}
