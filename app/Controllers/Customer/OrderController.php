<?php

namespace App\Controllers\Customer;

use App\Controllers\BaseController;
use App\Models\OrderModel;
use App\Models\ReturnRefundModel;
use App\Models\ReviewModel;

class OrderController extends BaseController
{
    protected OrderModel $orderModel;
    protected ReturnRefundModel $returnModel;
    protected ReviewModel $reviewModel;

    public function __construct()
    {
        $this->orderModel  = new OrderModel();
        $this->returnModel = new ReturnRefundModel();
        $this->reviewModel = new ReviewModel();
    }

    public function index()
    {
        $userId = (int) session()->get('user.id');
        $orders = $this->orderModel->getCustomerOrders($userId);

        return view('customer/orders', [
            'title'  => 'My Orders — Solqam Marketplace',
            'orders' => $orders,
        ]);
    }

    public function show($id)
    {
        $userId = (int) session()->get('user.id');
        $order = $this->orderModel->getOrderDetail((int) $id);

        if (!$order || (int) $order['user_id'] !== $userId) {
            throw \CodeIgniter\Exceptions\PageNotFoundException::forPageNotFound('Order not found');
        }

        $existingReturn = $this->returnModel->where('order_id', $id)->first();

        return view('customer/order_detail', [
            'title'          => "Order {$order['order_number']} — Solqam Marketplace",
            'order'          => $order,
            'existingReturn' => $existingReturn,
        ]);
    }

    public function requestReturn($orderId)
    {
        $userId = (int) session()->get('user.id');
        $order = $this->orderModel->find($orderId);

        if (!$order || (int) $order['user_id'] !== $userId) {
            return redirect()->back()->with('error', 'Order not found.');
        }

        if ($order['status'] !== 'delivered') {
            return redirect()->back()->with('error', 'Only delivered orders are eligible for return requests.');
        }

        $existing = $this->returnModel->where('order_id', $orderId)->first();
        if ($existing) {
            return redirect()->back()->with('error', 'A return request has already been submitted for this order.');
        }

        $reason       = $this->request->getPost('reason');
        $customerNote = $this->request->getPost('customer_note');
        $photoPath = null;
        $file = $this->request->getFile('return_photo');
        if ($file && $file->isValid() && ! $file->hasMoved()) {
            $dir = FCPATH . 'uploads/returns';
            if (! is_dir($dir)) {
                mkdir($dir, 0755, true);
            }
            $name = 'ret_' . time() . '_' . $file->getRandomName();
            $file->move($dir, $name);
            $photoPath = base_url('uploads/returns/' . $name);
        }

        $this->returnModel->insert([
            'order_id'      => $orderId,
            'user_id'       => $userId,
            'reason'        => $reason,
            'customer_note' => $customerNote,
            'photo_path'    => $photoPath,
            'refund_amount' => $order['final_payable'] > 0 ? $order['final_payable'] : $order['total_amount'],
            'status'        => 'requested',
        ]);

        return redirect()->back()->with('success', 'Return request submitted. Our team will review and approve your refund into your wallet ledger.');
    }

    public function cancel($orderId)
    {
        try {
            (new \App\Services\Order\OrderService())->cancelByBuyer((int) $orderId, (int) session()->get('user.id'));
            return redirect()->to('/account/orders')->with('success', 'Order cancelled.');
        } catch (\Throwable $e) {
            return redirect()->back()->with('error', $e->getMessage());
        }
    }

    public function invoice($orderId)
    {
        $userId = (int) session()->get('user.id');
        $order = $this->orderModel->getOrderDetail((int) $orderId);
        if (! $order || (int) $order['user_id'] !== $userId) {
            throw \CodeIgniter\Exceptions\PageNotFoundException::forPageNotFound('Order not found');
        }
        return view('customer/invoice', ['order' => $order]);
    }

    public function submitReview($orderId)
    {
        $userId    = (int) session()->get('user.id');
        $productId = (int) $this->request->getPost('product_id');
        $rating    = (int) $this->request->getPost('rating');
        $comment   = $this->request->getPost('comment');

        $order = $this->orderModel->find($orderId);
        if (!$order || (int) $order['user_id'] !== $userId || $order['status'] !== 'delivered') {
            return redirect()->back()->with('error', 'You can only review delivered orders.');
        }

        $imagePath = null;
        $img = $this->request->getFile('review_image');
        if ($img && $img->isValid() && !$img->hasMoved()) {
            $uploadDir = FCPATH . 'uploads/reviews';
            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0755, true);
            }
            $newName = $img->getRandomName();
            $img->move($uploadDir, $newName);
            $imagePath = base_url('uploads/reviews/' . $newName);
        }

        $this->reviewModel->insert([
            'product_id'  => $productId,
            'user_id'     => $userId,
            'order_id'    => $orderId,
            'rating'      => max(1, min(5, $rating)),
            'comment'     => $comment,
            'image_path'  => $imagePath,
            'is_approved' => 1,
        ]);

        return redirect()->back()->with('success', 'Thank you! Your product review has been submitted.');
    }
}
