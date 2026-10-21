<?php

namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use App\Models\SellerCodDueModel;
use App\Models\SellerProfileModel;
use App\Models\UserModel;
use App\Services\Commission\SellerDueService;

class DueController extends BaseController
{
    public function index()
    {
        $dues = new SellerDueService();
        $filled = 0;
        try {
            $filled = $dues->backfillMissing();
        } catch (\Throwable $e) {
            log_message('error', 'COD due backfill: ' . $e->getMessage());
        }

        if ($filled > 0) {
            session()->setFlashdata('success', $filled . ' past COD self-ship deliveries added to dues.');
        }

        return view('admin/dues/index', [
            'title'   => 'COD seller dues — Solqam Admin',
            'sellers' => $dues->unpaidSellers(),
            'limit'   => SellerDueService::SUSPEND_AT,
        ]);
    }

    public function markPaid($id)
    {
        try {
            $note   = trim((string) $this->request->getPost('paid_note'));
            $amount = $this->request->getPost('amount');
            $amt    = $amount === null || $amount === '' ? null : (float) $amount;
            (new SellerDueService())->markPaid((int) $id, $note, $amt);
        } catch (\Throwable $e) {
            return redirect()->back()->with('error', $e->getMessage());
        }

        return redirect()->back()->with('success', 'COD commission/cashback receipt saved.');
    }

    public function offsetWallet($sellerId)
    {
        try {
            $cut = (new SellerDueService())->tryOffsetFromWallet((int) $sellerId);
        } catch (\Throwable $e) {
            return redirect()->back()->with('error', $e->getMessage());
        }

        if ($cut <= 0) {
            return redirect()->back()->with('error', 'Seller payout wallet has no balance to cut.');
        }

        return redirect()->back()->with('success', 'Cut Rs. ' . number_format($cut, 0) . ' from seller payout wallet.');
    }

    public function receipt($id)
    {
        $row = (new SellerCodDueModel())
            ->select('seller_cod_dues.*, orders.order_number, orders.total_amount, orders.final_payable')
            ->join('orders', 'orders.id = seller_cod_dues.order_id', 'left')
            ->where('seller_cod_dues.id', (int) $id)
            ->first();
        if (! $row) {
            return redirect()->back()->with('error', 'Due record not found.');
        }
        $profile = (new SellerProfileModel())->getByUserId((int) $row['seller_id']);
        $user    = (new UserModel())->find((int) $row['seller_id']);

        return view('admin/dues/receipt', [
            'title'   => 'COD due receipt',
            'due'     => $row,
            'remain'  => SellerDueService::remaining($row),
            'profile' => $profile,
            'seller'  => $user,
        ]);
    }
}
