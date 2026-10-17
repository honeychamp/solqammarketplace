<?php

namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use App\Models\CouponModel;

class CouponController extends BaseController
{
    public function index()
    {
        $model   = new CouponModel();
        $coupons = $model->orderBy('id', 'DESC')->paginate(25);
        return view('admin/coupons/index', [
            'title'   => 'Vouchers — Solqam Admin',
            'coupons' => $coupons,
            'pager'   => $model->pager,
        ]);
    }

    public function store()
    {
        (new CouponModel())->insert([
            'code'       => strtoupper(trim((string) $this->request->getPost('code'))),
            'type'       => $this->request->getPost('type') ?: 'percent',
            'value'      => (float) $this->request->getPost('value'),
            'min_order'  => (float) ($this->request->getPost('min_order') ?? 0),
            'max_uses'   => $this->request->getPost('max_uses') !== '' ? (int) $this->request->getPost('max_uses') : null,
            'starts_at'  => $this->request->getPost('starts_at') ?: date('Y-m-d H:i:s'),
            'expires_at' => $this->request->getPost('expires_at') ?: date('Y-m-d H:i:s', strtotime('+30 days')),
            'is_active'  => 1,
        ]);
        return redirect()->to('/admin/coupons')->with('success', 'Voucher created.');
    }

    public function update($id)
    {
        $id    = (int) $id;
        $model = new CouponModel();
        $row   = $model->find($id);
        if (! $row) {
            return redirect()->to('/admin/coupons')->with('error', 'Voucher not found.');
        }

        $code = strtoupper(trim((string) $this->request->getPost('code')));
        $model->update($id, [
            'code'       => $code !== '' ? $code : $row['code'],
            'type'       => $this->request->getPost('type') ?: ($row['type'] ?? 'percent'),
            'value'      => (float) $this->request->getPost('value'),
            'min_order'  => (float) ($this->request->getPost('min_order') ?? 0),
            'max_uses'   => $this->request->getPost('max_uses') !== '' ? (int) $this->request->getPost('max_uses') : null,
            'starts_at'  => $this->request->getPost('starts_at') ?: ($row['starts_at'] ?? date('Y-m-d H:i:s')),
            'expires_at' => $this->request->getPost('expires_at') ?: ($row['expires_at'] ?? null),
            'is_active'  => $this->request->getPost('is_active') === null ? (int) ($row['is_active'] ?? 1) : ((int) $this->request->getPost('is_active') ? 1 : 0),
        ]);

        return redirect()->to('/admin/coupons')->with('success', 'Voucher updated.');
    }

    public function delete($id)
    {
        (new CouponModel())->delete((int) $id);

        return redirect()->to('/admin/coupons')->with('success', 'Voucher deleted.');
    }
}
