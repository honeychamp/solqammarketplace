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
        $buyers = (new OrderItemModel())->getSellerBuyers($sellerId);

        return view('seller/customers/index', [
            'title'  => 'Your buyers — Solqam Seller Hub',
            'buyers' => $buyers,
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
