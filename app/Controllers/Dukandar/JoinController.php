<?php

namespace App\Controllers\Dukandar;

use App\Controllers\BaseController;
use App\Services\Card\CardNetworkService;
use App\Services\Card\SolqamGeoCatalog;

class JoinController extends BaseController
{
    public function index()
    {
        if (! session()->get('user.isLoggedIn')) {
            session()->set('login_intent', 'merchant');
            session()->set('redirect_url', merchant_url('join'));

            return redirect()->to('/login');
        }
        $uid = (int) session()->get('user.id');
        $net = new CardNetworkService();
        if ($net->profileForUser($uid)) {
            return redirect()->to(merchant_url('dashboard'));
        }
        if ($this->request->is('post')) {
            $shopName = trim((string) $this->request->getPost('shop_name'));
            $address  = trim((string) $this->request->getPost('address'));
            $geo      = SolqamGeoCatalog::cityByCodes(
                (string) $this->request->getPost('division_code'),
                (string) $this->request->getPost('city_code')
            );
            if ($shopName === '' || strlen($shopName) < 2) {
                return redirect()->back()->withInput()->with('error', 'Please enter your shop name.');
            }
            if (! $geo) {
                return redirect()->back()->withInput()->with('error', 'Please select your division and city.');
            }
            if ($address === '' || strlen($address) < 8) {
                return redirect()->back()->withInput()->with('error', 'Please enter the complete shop address.');
            }
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
            $cat = SolqamGeoCatalog::resolveMerchantCategory((string) $this->request->getPost('category'));
            try {
                $path   = $this->storeShopPhoto('shop_image');
                $pathIn = $this->storeShopPhoto('shop_image_in');
                $net->upsertProfile($uid, [
                    'owner_name'     => $this->request->getPost('owner_name') ?: session()->get('user.name'),
                    'shop_name'      => $shopName,
                    'phone'          => $this->request->getPost('phone') ?: session()->get('user.phone'),
                    'email'          => $this->request->getPost('email') ?: session()->get('user.email'),
                    'city'           => $geo['city_name'],
                    'province'       => $geo['province_name'],
                    'whatsapp'       => $whatsapp,
                    'opening_hours'  => $this->request->getPost('opening_hours'),
                    'area'           => $this->request->getPost('area'),
                    'address'        => $address,
                    'category'       => $cat['name'],
                    'category_code'  => $cat['code'],
                    'shop_image'     => $path,
                    'shop_image_in'  => $pathIn,
                    'occupancy_type' => $occupancy,
                    'business_year'  => $bizYear,
                ]);
                try {
                    (new \App\Models\UserModel())->skipValidation()->update($uid, [
                        'city'           => $geo['city_name'],
                        'province'       => $geo['province_name'],
                        'division_code'  => $geo['division_code'],
                        'city_code'      => $geo['city_code'],
                    ]);
                } catch (\Throwable $e) {
                }
            } catch (\Throwable $e) {
                return redirect()->back()->withInput()->with('error', $e->getMessage());
            }

            return redirect()->to(merchant_url('dashboard'))->with('success', 'Shop submitted. Scan cards after Solqam approves you as a merchant.');
        }

        return view('dukandar/join', [
            'title'      => 'Join as a Merchant — Solqam',
            'categories' => SolqamGeoCatalog::merchantCategories(),
            'divisions'  => SolqamGeoCatalog::divisions(),
            'geoCities'  => SolqamGeoCatalog::citiesGrouped(),
        ]);
    }

    protected function storeShopPhoto(string $field): ?string
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
        $name = ($field === 'shop_image_in' ? 'shopin_' : 'shop_') . $img->getRandomName();
        $img->move($dir, $name);

        return 'uploads/dukandar/' . $name;
    }
}
