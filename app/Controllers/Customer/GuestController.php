<?php

namespace App\Controllers\Customer;

use App\Controllers\BaseController;
use App\Models\CartItemModel;
use App\Models\CartModel;

class GuestController extends BaseController
{
    public function start()
    {
        if (session()->get('user.isLoggedIn')) {
            return redirect()->to('/checkout');
        }
        session()->set('guest_mode', 1);
        $cart = (new CartModel())->getOrCreateCart(null, session_id());
        $count = 0;
        if (! empty($cart['id'])) {
            $count = (new CartItemModel())->where('cart_id', (int) $cart['id'])->countAllResults();
        }
        if ($count > 0) {
            return redirect()->to('/checkout')->with('info', 'Guest checkout — enter your name, phone and address to place the order. No sign up needed.');
        }

        return redirect()->to('/shop')->with('info', 'You are shopping as a guest. Add items, then fill your details at checkout.');
    }
}
