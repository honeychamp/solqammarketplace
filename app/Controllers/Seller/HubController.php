<?php

namespace App\Controllers\Seller;

use App\Controllers\BaseController;
use App\Models\ProductQuestionModel;
use App\Models\SellerPayoutModel;

class HubController extends BaseController
{
    public function questions()
    {
        $sellerId = (int) session()->get('user.id');
        $questions = (new ProductQuestionModel())
            ->select('product_questions.*, products.name as product_name, users.name as asker_name')
            ->join('products', 'products.id = product_questions.product_id')
            ->join('users', 'users.id = product_questions.user_id', 'left')
            ->where('products.seller_id', $sellerId)
            ->orderBy('product_questions.id', 'DESC')
            ->findAll();

        return view('seller/questions/index', [
            'title'     => 'Product Q&A — Seller Hub',
            'questions' => $questions,
        ]);
    }

    public function answer($id)
    {
        $sellerId = (int) session()->get('user.id');
        $qModel = new ProductQuestionModel();
        $row = $qModel->select('product_questions.*, products.seller_id')
            ->join('products', 'products.id = product_questions.product_id')
            ->where('product_questions.id', (int) $id)
            ->first();

        if (!$row || (int) $row['seller_id'] !== $sellerId) {
            return $this->redirectAfterHub('/seller/questions', 'error', 'Question not found.');
        }

        $qModel->update((int) $id, [
            'answer'      => $this->request->getPost('answer'),
            'answered_by' => $sellerId,
            'answered_at' => date('Y-m-d H:i:s'),
        ]);

        return $this->redirectAfterHub('/seller/questions', 'success', 'Answer published.');
    }

    public function payouts()
    {
        $sellerId = (int) session()->get('user.id');
        $payouts = (new SellerPayoutModel())
            ->select('seller_payouts.*, orders.order_number')
            ->join('orders', 'orders.id = seller_payouts.order_id', 'left')
            ->where('seller_payouts.seller_id', $sellerId)
            ->orderBy('seller_payouts.id', 'DESC')
            ->findAll();

        $pending = 0.0;
        $paid = 0.0;
        foreach ($payouts as $p) {
            if ($p['status'] === 'paid') {
                $paid += (float) $p['amount'];
            } else {
                $pending += (float) $p['amount'];
            }
        }

        return view('seller/payouts/index', [
            'title'   => 'Payouts — Seller Hub',
            'payouts' => $payouts,
            'pending' => $pending,
            'paid'    => $paid,
        ]);
    }

    public function payoutStatement()
    {
        $sellerId = (int) session()->get('user.id');
        $payouts = (new SellerPayoutModel())
            ->select('seller_payouts.*, orders.order_number')
            ->join('orders', 'orders.id = seller_payouts.order_id', 'left')
            ->where('seller_payouts.seller_id', $sellerId)
            ->orderBy('seller_payouts.id', 'DESC')
            ->findAll();

        return view('seller/payouts/statement', [
            'title'   => 'Payout statement',
            'payouts' => $payouts,
            'seller'  => session()->get('user'),
        ]);
    }
}
