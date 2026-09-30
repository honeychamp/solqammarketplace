<?php

namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use App\Models\ShipmentModel;
use App\Services\Order\OrderService;

class InboundController extends BaseController
{
    public function index()
    {
        $packages = (new ShipmentModel())
            ->select('shipments.*, orders.order_number, orders.final_payable, seller_profiles.store_name, users.name as seller_name')
            ->join('orders', 'orders.id = shipments.order_id')
            ->join('seller_profiles', 'seller_profiles.user_id = shipments.seller_id', 'left')
            ->join('users', 'users.id = shipments.seller_id', 'left')
            ->where('shipments.fulfill_by', 'admin')
            ->whereNotIn('shipments.status', ['delivered', 'cancelled'])
            ->orderBy('shipments.id', 'DESC')
            ->findAll(100);

        return view('admin/inbound/index', [
            'title'    => 'Seller inbound — Solqam delivery',
            'packages' => $packages,
        ]);
    }

    public function receive($id)
    {
        try {
            (new OrderService())->receiveAdminInbound((int) $id);

            return $this->redirectAfterHub('/admin/inbound', 'success', 'Parcel received at Solqam. You can now ship to the buyer.');
        } catch (\Exception $e) {
            return $this->redirectAfterHub('/admin/inbound', 'error', $e->getMessage());
        }
    }

    public function updateStatus($id)
    {
        $pkg = (new ShipmentModel())->find((int) $id);
        if (! $pkg || ($pkg['fulfill_by'] ?? '') !== 'admin') {
            return redirect()->to('/admin/inbound')->with('error', 'Package not found.');
        }

        $status = (string) $this->request->getPost('status');
        try {
            (new OrderService())->updateSellerShipment(
                (int) $pkg['order_id'],
                (int) $pkg['seller_id'],
                $status,
                [
                    'tracking_number' => $this->request->getPost('tracking_number'),
                    'courier'         => $this->request->getPost('courier'),
                ],
                true
            );

            return $this->redirectAfterHub('/admin/inbound', 'success', 'Solqam delivery updated to ' . ucfirst($status) . '.');
        } catch (\Exception $e) {
            return $this->redirectAfterHub('/admin/inbound', 'error', $e->getMessage());
        }
    }
}
