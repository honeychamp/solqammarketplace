<?php

namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use App\Models\SolqamDivisionModel;
use App\Models\SolqamGeoCityModel;
use App\Models\SolqamGoldenNumberModel;
use App\Models\SolqamMerchantCategoryModel;
use App\Models\UserModel;
use App\Services\Card\CardNetworkService;
use App\Services\Card\SolqamGeoCatalog;

class CardGeoController extends BaseController
{
    public function index()
    {
        \App\Services\Platform\SchemaHeal::ensureCardNetwork();
        $tab = (string) ($this->request->getGet('tab') ?: 'divisions');
        $divFilter = (int) ($this->request->getGet('division_id') ?: 0);
        $divisions = (new SolqamDivisionModel())->orderBy('code', 'ASC')->findAll();
        $citiesQ   = (new SolqamGeoCityModel())->orderBy('code', 'ASC');
        if ($divFilter > 0) {
            $citiesQ->where('division_id', $divFilter);
        }
        $cities = $citiesQ->findAll();
        $divById = [];
        foreach ($divisions as $d) {
            $divById[(int) $d['id']] = $d;
        }
        foreach ($cities as &$c) {
            $c['division_name'] = $divById[(int) $c['division_id']]['name'] ?? '';
            $c['division_code'] = $divById[(int) $c['division_id']]['code'] ?? '';
        }
        unset($c);

        return view('admin/cardgeo/index', [
            'title'      => 'Card IDs — Solqam Admin',
            'tab'        => $tab,
            'divisions'  => $divisions,
            'cities'     => $cities,
            'divFilter'  => $divFilter,
            'categories' => (new SolqamMerchantCategoryModel())->orderBy('code', 'ASC')->findAll(),
            'goldens'    => (new SolqamGoldenNumberModel())->orderBy('member_type', 'ASC')->orderBy('serial', 'ASC')->findAll(),
            'nextDiv'    => SolqamGeoCatalog::nextCode('solqam_divisions', 'code', 2),
            'nextCat'    => SolqamGeoCatalog::nextCode('solqam_merchant_categories', 'code', 3),
        ]);
    }

    public function storeDivision()
    {
        $code = SolqamGeoCatalog::pad((string) $this->request->getPost('code'), 2);
        $name = trim((string) $this->request->getPost('name'));
        $prov = trim((string) $this->request->getPost('province_name'));
        if ($code === '' || $name === '' || $prov === '') {
            return redirect()->to('/admin/card-ids?tab=divisions')->with('error', 'Code, name and province are required.');
        }
        try {
            (new SolqamDivisionModel())->insert([
                'code'          => $code,
                'name'          => $name,
                'province_name' => $prov,
                'is_active'     => 1,
            ]);
        } catch (\Throwable $e) {
            return redirect()->to('/admin/card-ids?tab=divisions')->with('error', 'Could not save division. Code may already exist.');
        }

        return redirect()->to('/admin/card-ids?tab=divisions')->with('success', 'Division added.');
    }

    public function updateDivision(int $id)
    {
        $m = new SolqamDivisionModel();
        if (! $m->find($id)) {
            return redirect()->to('/admin/card-ids?tab=divisions')->with('error', 'Division not found.');
        }
        $m->update($id, [
            'code'          => SolqamGeoCatalog::pad((string) $this->request->getPost('code'), 2),
            'name'          => trim((string) $this->request->getPost('name')),
            'province_name' => trim((string) $this->request->getPost('province_name')),
            'is_active'     => $this->request->getPost('is_active') ? 1 : 0,
        ]);

        return redirect()->to('/admin/card-ids?tab=divisions')->with('success', 'Division updated.');
    }

    public function deleteDivision(int $id)
    {
        (new SolqamGeoCityModel())->where('division_id', $id)->delete();
        (new SolqamDivisionModel())->delete($id);

        return redirect()->to('/admin/card-ids?tab=divisions')->with('success', 'Division deleted.');
    }

    public function storeCity()
    {
        $divId = (int) $this->request->getPost('division_id');
        $name  = trim((string) $this->request->getPost('name'));
        $code  = SolqamGeoCatalog::pad((string) $this->request->getPost('code'), 2);
        if ($divId < 1 || $name === '') {
            return redirect()->to('/admin/card-ids?tab=cities')->with('error', 'Division and city name are required.');
        }
        if ($code === '') {
            $code = SolqamGeoCatalog::nextCityCode($divId);
        }
        try {
            (new SolqamGeoCityModel())->insert([
                'division_id' => $divId,
                'code'        => $code,
                'name'        => $name,
                'is_active'   => 1,
            ]);
        } catch (\Throwable $e) {
            return redirect()->to('/admin/card-ids?tab=cities&division_id=' . $divId)->with('error', 'City code already exists in this division.');
        }

        return redirect()->to('/admin/card-ids?tab=cities&division_id=' . $divId)->with('success', 'City added.');
    }

    public function updateCity(int $id)
    {
        $m = new SolqamGeoCityModel();
        $row = $m->find($id);
        if (! $row) {
            return redirect()->to('/admin/card-ids?tab=cities')->with('error', 'City not found.');
        }
        $m->update($id, [
            'code'      => SolqamGeoCatalog::pad((string) $this->request->getPost('code'), 2),
            'name'      => trim((string) $this->request->getPost('name')),
            'is_active' => $this->request->getPost('is_active') ? 1 : 0,
        ]);

        return redirect()->to('/admin/card-ids?tab=cities&division_id=' . (int) $row['division_id'])->with('success', 'City updated.');
    }

    public function deleteCity(int $id)
    {
        $m = new SolqamGeoCityModel();
        $row = $m->find($id);
        $div = (int) ($row['division_id'] ?? 0);
        $m->delete($id);

        return redirect()->to('/admin/card-ids?tab=cities&division_id=' . $div)->with('success', 'City deleted.');
    }

    public function storeCategory()
    {
        $code = SolqamGeoCatalog::pad((string) $this->request->getPost('code'), 3);
        $name = trim((string) $this->request->getPost('name'));
        if ($name === '') {
            return redirect()->to('/admin/card-ids?tab=shops')->with('error', 'Shop type name is required.');
        }
        if ($code === '') {
            $code = SolqamGeoCatalog::nextCode('solqam_merchant_categories', 'code', 3);
        }
        try {
            (new SolqamMerchantCategoryModel())->insert([
                'code'      => $code,
                'name'      => $name,
                'is_active' => 1,
            ]);
        } catch (\Throwable $e) {
            return redirect()->to('/admin/card-ids?tab=shops')->with('error', 'That 3-digit shop code already exists.');
        }

        return redirect()->to('/admin/card-ids?tab=shops')->with('success', 'Shop category added. Every shop of this type in Pakistan shares this code.');
    }

    public function updateCategory(int $id)
    {
        $m = new SolqamMerchantCategoryModel();
        if (! $m->find($id)) {
            return redirect()->to('/admin/card-ids?tab=shops')->with('error', 'Shop type not found.');
        }
        $m->update($id, [
            'code'      => SolqamGeoCatalog::pad((string) $this->request->getPost('code'), 3),
            'name'      => trim((string) $this->request->getPost('name')),
            'is_active' => $this->request->getPost('is_active') ? 1 : 0,
        ]);

        return redirect()->to('/admin/card-ids?tab=shops')->with('success', 'Shop type updated.');
    }

    public function deleteCategory(int $id)
    {
        (new SolqamMerchantCategoryModel())->delete($id);

        return redirect()->to('/admin/card-ids?tab=shops')->with('success', 'Shop type deleted.');
    }

    public function storeGolden()
    {
        $type = strtoupper(trim((string) $this->request->getPost('member_type')));
        $type = $type === 'D' ? 'M' : $type;
        $width = $type === 'M' ? 7 : 8;
        $serial = str_pad(preg_replace('/\D+/', '', (string) $this->request->getPost('serial')) ?? '', $width, '0', STR_PAD_LEFT);
        if (! in_array($type, ['C', 'M'], true) || $serial === str_repeat('0', $width)) {
            return redirect()->to('/admin/card-ids?tab=golden')->with('error', 'Pick Customer or Merchant and a serial.');
        }
        try {
            (new SolqamGoldenNumberModel())->insert([
                'member_type' => $type,
                'serial'      => $serial,
                'status'      => 'reserved',
                'note'        => trim((string) $this->request->getPost('note')),
            ]);
        } catch (\Throwable $e) {
            return redirect()->to('/admin/card-ids?tab=golden')->with('error', 'That golden serial is already reserved.');
        }

        return redirect()->to('/admin/card-ids?tab=golden')->with('success', 'Golden number reserved. It will not auto-issue.');
    }

    public function assignGolden(int $id)
    {
        $lookup = trim((string) $this->request->getPost('member'));
        if ($lookup === '') {
            return redirect()->to('/admin/card-ids?tab=golden')->with('error', 'Enter the favourite member email or phone.');
        }
        $users = new UserModel();
        $user  = $users->findByEmailOrPhone($lookup);
        if (! $user) {
            $user = $users->like('name', $lookup)->orderBy('id', 'DESC')->first();
        }
        if (! $user) {
            return redirect()->to('/admin/card-ids?tab=golden')->with('error', 'No member found with that email, phone or name.');
        }
        try {
            $pan = (new CardNetworkService())->assignGoldenNumber((int) $user['id'], $id);
        } catch (\Throwable $e) {
            return redirect()->to('/admin/card-ids?tab=golden')->with('error', $e->getMessage());
        }

        return redirect()->to('/admin/card-ids?tab=golden')->with('success', 'Golden number gifted to ' . $user['name'] . ': ' . $pan);
    }

    public function deleteGolden(int $id)
    {
        $m = new SolqamGoldenNumberModel();
        $row = $m->find($id);
        if ($row && ($row['status'] ?? '') === 'assigned') {
            return redirect()->to('/admin/card-ids?tab=golden')->with('error', 'Unassign is not available after a golden number is gifted.');
        }
        $m->delete($id);

        return redirect()->to('/admin/card-ids?tab=golden')->with('success', 'Golden number removed from the reserved list.');
    }
}
