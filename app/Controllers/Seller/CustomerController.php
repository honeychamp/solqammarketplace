<?php

namespace App\Controllers\Seller;

use App\Controllers\BaseController;
use App\Models\OrderItemModel;
use App\Services\Customer\CustomerInsightService;

class CustomerController extends BaseController
{
    public function index()
    {
        $sellerId = (int) session()->get('user.id');
        $items = (new OrderItemModel())->getSellerOrderItems($sellerId);
        $buyers = [];
        foreach ($items as $item) {
            $cid = (int) ($item['customer_id'] ?? 0);
            if ($cid <= 0) {
                continue;
            }
            if (!isset($buyers[$cid])) {
                $buyers[$cid] = [
                    'id'         => $cid,
                    'name'       => $item['customer_name'],
                    'phone'      => $item['customer_phone'],
                    'orders'     => 0,
                    'goods'      => 0.0,
                    'commission' => 0.0,
                    'cashback'   => 0.0,
                ];
            }
            $buyers[$cid]['orders']++;
            $buyers[$cid]['goods'] += (float) $item['subtotal'];
            $buyers[$cid]['commission'] += (float) $item['commission_amount'];
            $buyers[$cid]['cashback'] += item_cashback($item);
        }

        return view('seller/customers/index', [
            'title'  => 'Your buyers — Solqam Seller Hub',
            'buyers' => array_values($buyers),
        ]);
    }

    public function show($id)
    {
        $sellerId = (int) session()->get('user.id');
        $dossier = (new CustomerInsightService())->dossier((int) $id, $sellerId);
        if (!$dossier || empty($dossier['orders'])) {
            return redirect()->to('/seller/customers')->with('error', 'This buyer has no orders with your store.');
        }

        return view('shared/customer_dossier', [
            'title'   => 'Buyer 360 — ' . $dossier['user']['name'],
            'dossier' => $dossier,
            'mode'    => 'seller',
        ]);
    }
}
