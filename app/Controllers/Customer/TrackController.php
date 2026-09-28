<?php

namespace App\Controllers\Customer;

use App\Controllers\BaseController;
use App\Models\OrderModel;

class TrackController extends BaseController
{
    public function index()
    {
        $query = trim((string) ($this->request->getGet('order') ?? $this->request->getPost('order') ?? ''));
        $order = null;
        if ($query !== '') {
            $orderModel = new OrderModel();
            $row = $orderModel->where('order_number', $query)->first();
            if ($row) {
                $order = $orderModel->getOrderDetail((int) $row['id']);
            }
        }

        return view('customer/track', [
            'title' => 'Track Order — Solqam',
            'query' => $query,
            'order' => $order,
        ]);
    }
}
