<?php

namespace App\Controllers\Dukandar;

use App\Controllers\BaseController;
use App\Services\Card\CardNetworkService;
use App\Services\Platform\SettingService;
use App\Models\SellerProfileModel;

class HubController extends BaseController
{
    protected CardNetworkService $net;

    public function __construct()
    {
        $this->net = new CardNetworkService();
    }

    public function dashboard()
    {
        $uid  = (int) session()->get('user.id');
        $pack = $this->net->dukandarOverview($uid);

        $merchantCard = null;
        try {
            $merchantCard = $this->net->issueCard($uid);
        } catch (\Throwable $e) {
        }

        return view('dukandar/dashboard', [
            'title'         => 'Merchant Hub — Solqam',
            'shop'          => $pack['shop'],
            'scans'         => $pack['scans'],
            'summary'       => $pack['summary'],
            'today'         => $pack['today'] ?? [],
            'customers'     => $pack['customers'] ?? [],
            'fifo'          => $pack['fifo'] ?? [],
            'isSeller'      => (bool) (new SellerProfileModel())->getByUserId($uid),
            'merchantCard'  => $merchantCard,
            'accounts'      => SettingService::collectionAccounts(),
            'hasPayTo'      => SettingService::hasCollectionAccounts(),
        ]);
    }

    public function profile()
    {
        $uid  = (int) session()->get('user.id');
        if ($this->request->is('post')) {
            try {
                $img   = $this->storeShopImage('shop_image');
                $imgIn = $this->storeShopImage('shop_image_in');
                $geo = \App\Services\Card\SolqamGeoCatalog::cityByCodes(
                    (string) $this->request->getPost('division_code'),
                    (string) $this->request->getPost('city_code')
                );
                $cat = \App\Services\Card\SolqamGeoCatalog::resolveMerchantCategory((string) $this->request->getPost('category'));
                $whatsapp = preg_replace('/[^0-9]/', '', (string) $this->request->getPost('whatsapp'));
                if ($whatsapp !== '' && strlen($whatsapp) !== 11) {
                    return redirect()->back()->withInput()->with('error', 'WhatsApp must be exactly 11 digits (numbers only).');
                }
                $occupancy = \App\Services\Card\CardNetworkService::normalizeOccupancy($this->request->getPost('occupancy_type'));
                $bizYear   = \App\Services\Card\CardNetworkService::normalizeBusinessYear($this->request->getPost('business_year'));
                if ($occupancy === null) {
                    return redirect()->back()->withInput()->with('error', 'Select shop owner type (Owner or Rent).');
                }
                if ($bizYear === null) {
                    return redirect()->back()->withInput()->with('error', 'Select the year this business started.');
                }
                $this->net->upsertProfile($uid, [
                    'owner_name' => $this->request->getPost('owner_name'),
                    'shop_name'  => $this->request->getPost('shop_name'),
                    'phone'      => $this->request->getPost('phone'),
                    'email'      => $this->request->getPost('email'),
                    'city'           => $geo['city_name'] ?? $this->request->getPost('city'),
                    'province'       => $geo['province_name'] ?? $this->request->getPost('province'),
                    'whatsapp'       => $whatsapp,
                    'opening_hours'  => $this->request->getPost('opening_hours'),
                    'area'           => $this->request->getPost('area'),
                    'address'    => $this->request->getPost('address'),
                    'category'       => $cat['name'],
                    'category_code'  => $cat['code'],
                    'shop_image'     => $img,
                    'shop_image_in'  => $imgIn,
                    'occupancy_type' => $occupancy,
                    'business_year'  => $bizYear,
                    'lat'        => $this->request->getPost('lat') !== '' ? (float) $this->request->getPost('lat') : null,
                    'lng'        => $this->request->getPost('lng') !== '' ? (float) $this->request->getPost('lng') : null,
                ], true);
                if ($geo) {
                    try {
                        (new \App\Models\UserModel())->skipValidation()->update($uid, [
                            'city'           => $geo['city_name'],
                            'province'       => $geo['province_name'],
                            'division_code'  => $geo['division_code'],
                            'city_code'      => $geo['city_code'],
                        ]);
                    } catch (\Throwable $e) {
                    }
                }
            } catch (\Throwable $e) {
                return redirect()->back()->withInput()->with('error', $e->getMessage());
            }

            return redirect()->to(merchant_url('profile'))->with('success', 'Shop profile saved.');
        }

        $user = (new \App\Models\UserModel())->find($uid) ?: [];

        return view('dukandar/profile', [
            'title'      => 'Shop profile — Merchant Hub',
            'shop'       => $this->net->profileForUser($uid),
            'userGeo'    => $user,
            'categories' => \App\Services\Card\SolqamGeoCatalog::merchantCategories(),
            'divisions'  => \App\Services\Card\SolqamGeoCatalog::divisions(),
            'geoCities'  => \App\Services\Card\SolqamGeoCatalog::citiesGrouped(),
        ]);
    }

    public function scan()
    {
        $uid     = (int) session()->get('user.id');
        $pos     = $this->net->posContext($uid);
        $preview = null;
        $code    = trim((string) ($this->request->getGet('code') ?? $this->request->getPost('card_code') ?? ''));
        if ($code !== '' && $this->request->getMethod() !== 'post') {
            try {
                $preview = $this->net->lookupCard($code);
                $preview['card'] = $this->net->redactForPos($preview['card'] ?? []);
                $preview['shop_history'] = $this->net->shopHistoryForCustomer($uid, (int) ($preview['customer']['id'] ?? 0));
            } catch (\Throwable $e) {
                return redirect()->to(merchant_url('scan'))->with('error', $e->getMessage());
            }
        }

        return view('dukandar/scan', [
            'title'   => 'Scan Solqam Card — Merchant Hub',
            'preview' => $preview,
            'code'    => $code,
            'percent' => CardNetworkService::CASHBACK_PERCENT,
            'cvvAt'   => CardNetworkService::CVV_REQUIRED_AT,
            'pos'     => $pos,
        ]);
    }

    public function lookup()
    {
        $code = trim((string) $this->request->getPost('card_code'));
        try {
            $this->net->lookupCard($code);
        } catch (\Throwable $e) {
            return redirect()->to(merchant_url('scan'))->with('error', $e->getMessage());
        }

        return redirect()->to(merchant_url('scan') . '?code=' . urlencode($this->net->extractCardCode($code)));
    }

    public function sale()
    {
        $code = (string) $this->request->getPost('card_code');
        $bill = (float) str_replace(',', '', (string) $this->request->getPost('bill_amount'));
        try {
            $row = $this->net->recordSale(
                (int) session()->get('user.id'),
                $code,
                $bill,
                (string) $this->request->getPost('cvv'),
                (string) $this->request->getPost('pin')
            );
        } catch (\Throwable $e) {
            return redirect()->to(merchant_url('scan') . '?code=' . urlencode($code))->with('error', $e->getMessage());
        }

        $receipt = merchant_receipt($row['id'] ?? 0);
        $panShow = \App\Services\Card\SolqamCardNumber::display((string) ($row['pan'] ?? $code));

        return redirect()->to(merchant_url('credits'))->with('success', 'Receipt ' . $receipt . ' · ' . $panShow . ' · bill Rs. ' . number_format((float) $row['bill_amount'], 0) . ' · pending 5% Rs. ' . number_format((float) $row['credit_amount'], 2) . '. Pay Solqam and upload the screenshot to release it.');
    }

    public function credits()
    {
        $uid  = (int) session()->get('user.id');
        $pack = $this->net->dukandarOverview($uid);

        return view('dukandar/credits', [
            'title'     => 'Credits & Pay Solqam — Merchant Hub',
            'shop'      => $pack['shop'],
            'scans'     => $pack['scans'],
            'summary'   => $pack['summary'],
            'customers' => $pack['customers'] ?? [],
            'fifo'      => $pack['fifo'] ?? [],
            'accounts'  => SettingService::collectionAccounts(),
            'hasPayTo'  => SettingService::hasCollectionAccounts(),
        ]);
    }

    public function customers()
    {
        $pack = $this->net->dukandarOverview((int) session()->get('user.id'));

        return view('dukandar/customers', [
            'title'     => 'Customer history — Merchant Hub',
            'customers' => $pack['customers'] ?? [],
            'summary'   => $pack['summary'] ?? [],
            'approved'  => (($pack['shop']['approval_status'] ?? '') === 'approved'),
        ]);
    }

    public function proof($id)
    {
        try {
            $path = $this->storeProof('scan' . (int) $id . '_');
            $this->net->submitProof((int) session()->get('user.id'), (int) $id, $path, (string) $this->request->getPost('proof_note'));
        } catch (\Throwable $e) {
            return redirect()->back()->with('error', $e->getMessage());
        }

        return redirect()->to(merchant_url('credits'))->with('success', 'Screenshot received. Credits stay pending until Solqam admin approves this payment.');
    }

    public function proofAll()
    {
        try {
            $path = $this->storeProof('scanall' . (int) session()->get('user.id') . '_');
            $amt  = (float) str_replace(',', '', (string) $this->request->getPost('pay_amount'));
            $res  = $this->net->applyMerchantPayout(
                (int) session()->get('user.id'),
                $amt,
                $path,
                (string) $this->request->getPost('proof_note')
            );
        } catch (\Throwable $e) {
            return redirect()->back()->withInput()->with('error', $e->getMessage());
        }

        return redirect()->to(merchant_url('credits'))->with('success', 'Rs. ' . number_format((float) $res['allocated'], 0) . ' queued first-come first-served for ' . (int) $res['customers'] . ' customer(s). Admin must approve the screenshot before wallets are credited.');
    }

    public function becomeSeller()
    {
        if (! $this->request->is('post')) {
            return view('dukandar/become_seller', [
                'title' => 'Also sell on Marketplace — Merchant Hub',
                'shop'  => $this->net->profileForUser((int) session()->get('user.id')),
            ]);
        }
        $uid  = (int) session()->get('user.id');
        $shop = $this->net->profileForUser($uid);
        $seller = new SellerProfileModel();
        if ($seller->getByUserId($uid)) {
            return redirect()->to('/seller/dashboard')->with('success', 'Your marketplace seller store is already attached.');
        }
        $seller->insert([
            'user_id'          => $uid,
            'store_name'       => (string) ($shop['shop_name'] ?? session()->get('user.name') ?? 'Store'),
            'business_address' => (string) ($shop['address'] ?? ''),
            'city'             => (string) ($shop['city'] ?? ''),
            'approval_status'  => 'pending',
        ]);
        try {
            (new \App\Models\UserModel())->skipValidation()->update($uid, ['role' => 'seller']);
            $user = (new \App\Models\UserModel())->find($uid);
            if ($user) {
                (new \App\Services\Auth\AuthService())->setSession($user);
            }
        } catch (\Throwable $e) {
        }

        return redirect()->to('/seller/dashboard')->with('success', 'Marketplace seller profile created. List products after Solqam approves the store. Merchant Hub stays available.');
    }

    protected function storeShopImage(string $field = 'shop_image'): ?string
    {
        $img = $this->request->getFile($field);
        if (! $img || ! $img->isValid() || $img->hasMoved()) {
            return null;
        }
        if ($img->getSize() > 4 * 1024 * 1024) {
            throw new \RuntimeException('Shop photo must be under 4 MB.');
        }
        $ext = strtolower((string) $img->getExtension());
        if (! in_array($ext, ['jpg', 'jpeg', 'png', 'webp'], true)) {
            throw new \RuntimeException('Use JPG, PNG or WEBP.');
        }
        $dir = FCPATH . 'uploads/dukandar';
        if (! is_dir($dir)) {
            mkdir($dir, 0755, true);
        }
        $name = 'shop_' . $img->getRandomName();
        $img->move($dir, $name);

        return 'uploads/dukandar/' . $name;
    }

    protected function storeProof(string $prefix): string
    {
        $img = $this->request->getFile('proof_image');
        if (! $img || ! $img->isValid() || $img->hasMoved()) {
            throw new \RuntimeException('Payment screenshot is required.');
        }
        if ($img->getSize() > 4 * 1024 * 1024) {
            throw new \RuntimeException('Screenshot must be under 4 MB.');
        }
        $ext = strtolower((string) $img->getExtension());
        if (! in_array($ext, ['jpg', 'jpeg', 'png', 'webp'], true)) {
            throw new \RuntimeException('Use JPG, PNG or WEBP.');
        }
        $dir = FCPATH . 'uploads/dukandar/proofs';
        if (! is_dir($dir)) {
            mkdir($dir, 0755, true);
        }
        $name = $prefix . $img->getRandomName();
        $img->move($dir, $name);

        return 'uploads/dukandar/proofs/' . $name;
    }
}
