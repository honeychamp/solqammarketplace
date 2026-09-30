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
        $profile = (new SellerProfileModel())->where('user_id', $sellerId)->first();
        if (!$profile || ($profile['approval_status'] ?? '') !== 'approved') {
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
}
