<?php

namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use App\Models\OrderItemModel;
use App\Models\SellerProfileModel;
use App\Models\UserModel;

class SellerController extends BaseController
{
    protected SellerProfileModel $sellerProfileModel;
    protected UserModel $userModel;

    public function __construct()
    {
        $this->sellerProfileModel = new SellerProfileModel();
        $this->userModel          = new UserModel();
    }

    public function index()
    {
        $status = $this->request->getGet('status');
        $builder = $this->sellerProfileModel
            ->select('seller_profiles.*, users.name as owner_name, users.email as owner_email, users.phone as owner_phone, users.status as user_status')
            ->join('users', 'users.id = seller_profiles.user_id')
            ->orderBy('seller_profiles.id', 'DESC');

        if (!empty($status)) {
            $builder->where('seller_profiles.approval_status', $status);
        }

        $sellers = $builder->paginate(50);
        $sellerUserIds = array_map(static fn (array $s): int => (int) ($s['user_id'] ?? 0), $sellers);
        $customerCounts = (new OrderItemModel())->countCustomersBySellerIds($sellerUserIds);
        foreach ($sellers as &$s) {
            $s['customer_count'] = $customerCounts[(int) ($s['user_id'] ?? 0)] ?? 0;
        }
        unset($s);

        return view('admin/sellers/index', [
            'title'   => 'Seller Management — Solqam Admin Console',
            'sellers' => $sellers,
            'currentStatus' => $status,
            'pager'   => $this->sellerProfileModel->pager,
        ]);
    }

    public function show($id)
    {
        $seller = $this->sellerProfileModel
            ->select('seller_profiles.*, users.name as owner_name, users.email as owner_email, users.phone as owner_phone, users.status as user_status, users.created_at as registered_date')
            ->join('users', 'users.id = seller_profiles.user_id')
            ->where('seller_profiles.id', (int) $id)
            ->first();

        if (!$seller) {
            return redirect()->to('/admin/sellers')->with('error', 'Seller profile not found.');
        }

        $buyers = (new OrderItemModel())->getSellerBuyers((int) $seller['user_id']);
        $dues   = new \App\Services\Commission\SellerDueService();
        $items  = (new OrderItemModel())->getSellerOrderItems((int) $seller['user_id']);
        $life   = ['goods' => 0.0, 'commission' => 0.0, 'cashback' => 0.0, 'orders' => []];
        foreach ($items as $it) {
            $life['goods'] += (float) ($it['subtotal'] ?? 0);
            $life['commission'] += (float) ($it['commission_amount'] ?? 0);
            $life['cashback'] += function_exists('item_cashback') ? item_cashback($it) : (float) ($it['cashback_amount'] ?? 0);
            $life['orders'][(int) ($it['order_id'] ?? 0)] = true;
        }
        $life['net'] = round($life['goods'] - $life['commission'] - $life['cashback'], 2);
        $life['order_count'] = count($life['orders']);
        unset($life['orders']);

        return view('admin/sellers/show', [
            'title'      => "Seller Application: {$seller['store_name']} — Solqam Admin",
            'seller'     => $seller,
            'buyers'     => $buyers,
            'dueSummary' => $dues->summary((int) $seller['user_id']),
            'dueLines'   => $dues->linesForSeller((int) $seller['user_id']),
            'life'       => $life,
        ]);
    }

    public function approve($id)
    {
        $profile = $this->sellerProfileModel->find((int) $id);
        if (!$profile) {
            return redirect()->to('/admin/sellers')->with('error', 'Seller profile not found.');
        }

        $this->sellerProfileModel->update($id, [
            'approval_status'  => 'approved',
            'rejection_reason' => null,
        ]);

        $this->userModel->update($profile['user_id'], [
            'status' => 'active',
        ]);

        $owner = $this->userModel->find((int) $profile['user_id']);
        if ($owner) {
            try {
                \App\Services\Mail\MailService::sendSellerApproved($owner, $profile);
            } catch (\Throwable $e) {
                log_message('error', 'Seller approved email failed: ' . $e->getMessage());
            }
        }

        return $this->redirectAfterHub('/admin/sellers', 'success', "Seller '{$profile['store_name']}' has been activated successfully.");
    }

    public function reject($id)
    {
        $profile = $this->sellerProfileModel->find((int) $id);
        if (!$profile) {
            return redirect()->to('/admin/sellers')->with('error', 'Seller profile not found.');
        }

        $reason = $this->request->getPost('rejection_reason') ?: 'Account suspended by administrator.';

        $this->sellerProfileModel->update($id, [
            'approval_status'  => 'rejected',
            'rejection_reason' => $reason,
        ]);

        $this->userModel->update($profile['user_id'], [
            'status' => 'suspended',
        ]);

        $owner = $this->userModel->find((int) $profile['user_id']);
        if ($owner) {
            try {
                \App\Services\Mail\MailService::sendSellerRejected($owner, $profile, $reason);
            } catch (\Throwable $e) {
                log_message('error', 'Seller rejected email failed: ' . $e->getMessage());
            }
        }

        return $this->redirectAfterHub('/admin/sellers', 'info', "Seller '{$profile['store_name']}' has been suspended.");
    }
}
