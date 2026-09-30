<?php

namespace App\Controllers\Admin;

use App\Controllers\BaseController;
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

        return view('admin/sellers/show', [
            'title'  => "Seller Application: {$seller['store_name']} — Solqam Admin",
            'seller' => $seller,
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

        return $this->redirectAfterHub('/admin/sellers', 'info', "Seller '{$profile['store_name']}' has been suspended.");
    }
}
