<?php

namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use App\Models\SellerPayoutModel;

class PayoutController extends BaseController
{
    public function index()
    {
        $payouts = (new SellerPayoutModel())
            ->select('seller_payouts.*, seller_profiles.store_name, users.name as seller_name, orders.order_number')
            ->join('seller_profiles', 'seller_profiles.user_id = seller_payouts.seller_id', 'left')
            ->join('users', 'users.id = seller_payouts.seller_id', 'left')
            ->join('orders', 'orders.id = seller_payouts.order_id', 'left')
            ->orderBy('seller_payouts.id', 'DESC')
            ->findAll(200);

        return view('admin/payouts/index', [
            'title'   => 'Seller Payouts — Solqam Admin',
            'payouts' => $payouts,
        ]);
    }

    public function markPaid($id)
    {
        (new SellerPayoutModel())->update((int) $id, [
            'status'  => 'paid',
            'paid_at' => date('Y-m-d H:i:s'),
        ]);
        return $this->redirectAfterHub('/admin/payouts', 'success', 'Payout marked as paid.');
    }

    public function printPdf($id)
    {
        $payout = (new SellerPayoutModel())
            ->select('seller_payouts.*, seller_profiles.store_name, users.name as seller_name, users.email as seller_email, orders.order_number')
            ->join('seller_profiles', 'seller_profiles.user_id = seller_payouts.seller_id', 'left')
            ->join('users', 'users.id = seller_payouts.seller_id', 'left')
            ->join('orders', 'orders.id = seller_payouts.order_id', 'left')
            ->where('seller_payouts.id', (int) $id)
            ->first();
        if (! $payout) {
            return redirect()->to('/admin/payouts')->with('error', 'Payout not found.');
        }

        return view('admin/payouts/print', [
            'title'  => 'Payout voucher',
            'payout' => $payout,
        ]);
    }
}
