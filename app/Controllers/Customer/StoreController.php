<?php

namespace App\Controllers\Customer;

use App\Controllers\BaseController;
use App\Models\ProductModel;
use App\Models\SellerProfileModel;
use App\Models\StoreFollowModel;

class StoreController extends BaseController
{
    public function show($sellerId)
    {
        $sellerId = (int) $sellerId;
        $profile  = $this->resolvePublicStore($sellerId);
        if ($profile === null) {
            throw \CodeIgniter\Exceptions\PageNotFoundException::forPageNotFound('Store not found');
        }

        $products = (new ProductModel())->getCatalog(['seller_id' => $sellerId, 'limit' => 48]);
        $followerCount = (new StoreFollowModel())->followerCount($sellerId);
        $isFollowing = false;
        $userId = (int) session()->get('user.id');
        if ($userId) {
            $isFollowing = (new StoreFollowModel())->isFollowing($userId, $sellerId);
        }

        return view('customer/store', [
            'title'         => esc($profile['store_name']) . ' — Solqam Store',
            'profile'       => $profile,
            'products'      => $products,
            'followerCount' => $followerCount,
            'isFollowing'   => $isFollowing,
            'sellerId'      => $sellerId,
        ]);
    }

    /**
     * Official mall listings use the admin user id as seller_id and often
     * have no seller_profiles row, so /store/{adminId} must still resolve.
     */
    protected function resolvePublicStore(int $sellerId): ?array
    {
        if ($sellerId < 1) {
            return null;
        }

        $profile = null;
        try {
            $profile = (new SellerProfileModel())->where('user_id', $sellerId)->first();
        } catch (\Throwable $e) {
            $profile = null;
        }

        if (is_array($profile) && ($profile['approval_status'] ?? '') === 'approved') {
            return $profile;
        }

        $user = null;
        try {
            $user = (new \App\Models\UserModel())->find($sellerId);
        } catch (\Throwable $e) {
            $user = null;
        }
        if (! is_array($user) || ($user['status'] ?? '') === 'blocked') {
            return null;
        }

        $role = (string) ($user['role'] ?? '');
        if ($role === 'admin') {
            return [
                'user_id'         => $sellerId,
                'store_name'      => $profile['store_name'] ?? 'Solqam Mall',
                'city'            => $profile['city'] ?? 'Pakistan',
                'approval_status' => 'approved',
            ];
        }

        return null;
    }
}
