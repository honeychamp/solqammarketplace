<?php

namespace App\Controllers\Customer;

use App\Controllers\BaseController;
use App\Services\Card\CardNetworkService;

class CardController extends BaseController
{
    public function index()
    {
        $userId = (int) session()->get('user.id');
        if ($userId < 1) {
            return view('customer/card/landing', [
                'title' => 'Solqam Marketplace Card — Credit for shopping',
            ]);
        }
        $net  = new CardNetworkService();
        $pack = $net->customerDashboard($userId);

        return view('customer/card/dashboard', array_merge($pack, [
            'title'   => 'Solqam Marketplace Card — Solqam',
            'filters' => ['q' => '', 'city' => '', 'category' => ''],
            'shops'   => $net->shops(),
            'cities'  => $net->cities(),
            'holder'  => (string) session()->get('user.name'),
        ]));
    }

    public function freeze()
    {
        $userId = (int) session()->get('user.id');
        if ($userId < 1) {
            return redirect()->to('/login');
        }
        $on = (string) $this->request->getPost('frozen') === '1';
        try {
            (new CardNetworkService())->freeze($userId, $on);
        } catch (\Throwable $e) {
            return redirect()->to('/card')->with('error', $e->getMessage());
        }

        return redirect()->to('/card')->with('success', $on ? 'Card frozen. Shops cannot add credits until you unfreeze.' : 'Card is active again.');
    }

    public function pin()
    {
        $userId = (int) session()->get('user.id');
        if ($userId < 1) {
            return redirect()->to('/login');
        }
        try {
            (new CardNetworkService())->setPin($userId, (string) $this->request->getPost('pin'));
        } catch (\Throwable $e) {
            return redirect()->to('/card')->with('error', $e->getMessage());
        }

        return redirect()->to('/card')->with('success', 'Shop PIN saved. Merchants must enter it when they scan your card.');
    }

    public function rotateCvv()
    {
        $userId = (int) session()->get('user.id');
        if ($userId < 1) {
            return redirect()->to('/login');
        }
        try {
            (new CardNetworkService())->rotateCvv($userId);
        } catch (\Throwable $e) {
            return redirect()->to('/card')->with('error', $e->getMessage());
        }

        return redirect()->to('/card')->with('success', 'New CVV issued. The old code no longer works.');
    }

    public function shops()
    {
        $net     = new CardNetworkService();
        $filters = [
            'q'        => trim((string) $this->request->getGet('q')),
            'city'     => trim((string) $this->request->getGet('city')),
            'category' => trim((string) $this->request->getGet('category')),
            'lat'      => $this->request->getGet('lat'),
            'lng'      => $this->request->getGet('lng'),
        ];

        return view('customer/card/shops', [
            'title'      => 'Shops that accept Solqam Card — Solqam Marketplace',
            'shops'      => $net->shops($filters),
            'filters'    => $filters,
            'cities'     => $net->cities(),
            'categories' => \App\Services\Card\SolqamGeoCatalog::merchantCategories() ?: CardNetworkService::CATEGORIES,
        ]);
    }

    public function cardLink($code = '')
    {
        $net  = new CardNetworkService();
        $code = $net->extractCardCode((string) $code);
        $role = (string) session()->get('user.role');
        if (session()->get('user.isLoggedIn') && $role !== 'admin') {
            try {
                if ((new \App\Models\DukandarProfileModel())->getByUserId((int) session()->get('user.id'))) {
                    return redirect()->to(merchant_url('scan') . '?code=' . urlencode($code));
                }
            } catch (\Throwable $e) {
            }
        }

        return redirect()->to('/card')->with('info', 'Show this QR in a Solqam partner shop to earn 5% credits.');
    }
}
