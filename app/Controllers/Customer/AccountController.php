<?php

namespace App\Controllers\Customer;

use App\Controllers\BaseController;
use App\Models\StoreFollowModel;
use App\Models\WishlistModel;

class AccountController extends BaseController
{
    public function wallet()
    {
        $userId = (int) session()->get('user.id');
        $walletService = new \App\Services\Wallet\WalletService();
        $balance = $walletService->getBalance($userId);
        $transactions = $walletService->getTransactions($userId, 50);

        return view('customer/wallet', [
            'title'        => 'My Solqam Wallet Ledger — Solqam Marketplace',
            'balance'      => $balance,
            'transactions' => $transactions,
        ]);
    }

    public function addresses()
    {
        $userId = (int) session()->get('user.id');
        $addressModel = new \App\Models\AddressModel();

        if ($this->request->is('post')) {
            $rules = [
                'recipient_name' => 'required',
                'phone'          => 'required',
                'street_address' => 'required',
                'city'           => 'required',
                'province'       => 'required',
            ];
            if ($this->validate($rules)) {
                $addressModel->insert([
                    'user_id'        => $userId,
                    'recipient_name' => $this->request->getPost('recipient_name'),
                    'phone'          => $this->request->getPost('phone'),
                    'street_address' => $this->request->getPost('street_address'),
                    'city'           => $this->request->getPost('city'),
                    'province'       => $this->request->getPost('province'),
                    'postal_code'    => $this->request->getPost('postal_code'),
                    'is_default'     => (int) ($this->request->getPost('is_default') ?? 0),
                ]);
                return redirect()->to('/account/addresses')->with('success', 'Address added successfully.');
            }
        }

        $addresses = $addressModel->getUserAddresses($userId);

        return view('customer/addresses', [
            'title'     => 'My Address Book — Solqam Marketplace',
            'addresses' => $addresses,
        ]);
    }

    public function wishlist()
    {
        $userId = (int) session()->get('user.id');
        $items = (new WishlistModel())->getUserWishlist($userId);

        return view('customer/wishlist', [
            'title' => 'My Wishlist — Solqam',
            'items' => $items,
        ]);
    }

    public function toggleWishlist()
    {
        $userId = (int) session()->get('user.id');
        if (!$userId) {
            return redirect()->to('/login')->with('error', 'Login to save items to wishlist.');
        }
        $productId = (int) $this->request->getPost('product_id');
        $saved = (new WishlistModel())->toggle($userId, $productId);
        return redirect()->back()->with('success', $saved ? 'Saved to wishlist.' : 'Removed from wishlist.');
    }

    public function toggleFollow()
    {
        $userId = (int) session()->get('user.id');
        if (!$userId) {
            return redirect()->to('/login')->with('error', 'Login to follow a store.');
        }
        $sellerId = (int) $this->request->getPost('seller_id');
        $followed = (new StoreFollowModel())->toggle($userId, $sellerId);
        return redirect()->back()->with('success', $followed ? 'You are following this store.' : 'Unfollowed store.');
    }
}
