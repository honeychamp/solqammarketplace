<?php

namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use App\Models\OrderModel;
use App\Models\ReturnRefundModel;
use App\Services\Wallet\WalletService;

class ReturnController extends BaseController
{
    protected ReturnRefundModel $returnModel;
    protected OrderModel $orderModel;
    protected WalletService $walletService;

    public function __construct()
    {
        $this->returnModel   = new ReturnRefundModel();
        $this->orderModel    = new OrderModel();
        $this->walletService = new WalletService();
    }

    public function index()
    {
        $returns = $this->returnModel->getAllWithDetails();

        return view('admin/returns/index', [
            'title'   => 'Returns & Refunds Manager — Solqam Admin Console',
            'returns' => $returns,
        ]);
    }

    public function approve($id)
    {
        $return = $this->returnModel->find((int) $id);
        if (!$return) {
            return redirect()->to('/admin/returns')->with('error', 'Return request not found.');
        }

        if ($return['status'] === 'refunded') {
            return redirect()->to('/admin/returns')->with('error', 'This request has already been refunded.');
        }

        $order = $this->orderModel->find($return['order_id']);
        $refundAmount = (float) $return['refund_amount'];

        // 1. Credit customer wallet ledger directly
        $this->walletService->credit(
            (int) $return['user_id'],
            $refundAmount,
            'refund',
            (int) $id,
            "Refund for Return Request #{$id} on Order {$order['order_number']}"
        );

        // 2. Update return status
        $this->returnModel->update($id, [
            'status'       => 'refunded',
            'admin_note'   => $this->request->getPost('admin_note') ?: 'Approved and refunded to Solqam Wallet Ledger.',
            'processed_at' => date('Y-m-d H:i:s'),
        ]);

        return $this->redirectAfterHub('/admin/returns', 'success', "Return approved! Rs. " . number_format($refundAmount, 2) . " has been credited to the customer's wallet ledger.");
    }

    public function reject($id)
    {
        $return = $this->returnModel->find((int) $id);
        if (!$return) {
            return redirect()->to('/admin/returns')->with('error', 'Return request not found.');
        }

        $this->returnModel->update($id, [
            'status'       => 'rejected',
            'admin_note'   => $this->request->getPost('admin_note') ?: 'Return request does not comply with return policy.',
            'processed_at' => date('Y-m-d H:i:s'),
        ]);

        return $this->redirectAfterHub('/admin/returns', 'info', 'Return request rejected.');
    }
}
