<?php

namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use App\Models\OrderModel;
use App\Models\ReturnRefundModel;
use App\Services\Order\OrderService;

class ReturnController extends BaseController
{
    protected ReturnRefundModel $returnModel;
    protected OrderModel $orderModel;
    protected OrderService $orderService;

    public function __construct()
    {
        $this->returnModel  = new ReturnRefundModel();
        $this->orderModel   = new OrderModel();
        $this->orderService = new OrderService();
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

        try {
            $this->orderService->settleApprovedReturn($return);
        } catch (\Throwable $e) {
            log_message('error', 'Return approve: ' . $e->getMessage());

            return redirect()->to('/admin/returns')->with('error', $e->getMessage());
        }

        $this->returnModel->update($id, [
            'status'       => 'refunded',
            'admin_note'   => $this->request->getPost('admin_note') ?: 'Approved. Buyer wallet credited; seller/admin wallets debited.',
            'processed_at' => date('Y-m-d H:i:s'),
        ]);

        $refundAmount = (float) $return['refund_amount'];

        return $this->redirectAfterHub('/admin/returns', 'success', "Return approved. Rs. " . number_format($refundAmount, 2) . " credited to the buyer. Matching amount cut from seller/admin wallets.");
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
