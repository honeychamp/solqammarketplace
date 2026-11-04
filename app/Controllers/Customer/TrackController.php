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
            $row = self::findOrderByRef($orderModel, $query);
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

    protected static function findOrderByRef(OrderModel $orderModel, string $query): ?array
    {
        $raw = trim($query);
        $norm = strtoupper(str_replace([' ', '#', '_'], '', $raw));
        $candidates = array_values(array_unique(array_filter([$raw, $norm])));
        if (preg_match('/^S-?(\d+)$/i', $norm, $m)) {
            $candidates[] = 'S-' . $m[1];
            $candidates[] = $m[1];
        }
        $digits = preg_replace('/\D+/', '', $norm) ?? '';
        try {
            $row = $orderModel->whereIn('order_number', $candidates)->first();
            if ($row) {
                return $row;
            }
            $id = (int) $digits;
            if ($id > 0 && strlen($digits) <= 8) {
                $row = $orderModel->where('order_number', 'S-' . $id)->first()
                    ?: $orderModel->find($id);
                if ($row) {
                    return $row;
                }
            }
        } catch (\Throwable $e) {
            return $orderModel->where('order_number', $raw)->first();
        }

        return null;
    }
}
