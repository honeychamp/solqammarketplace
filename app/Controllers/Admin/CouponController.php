<?php

namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use App\Models\CouponModel;

class CouponController extends BaseController
{
    public function index()
    {
        $coupons = (new CouponModel())->orderBy('id', 'DESC')->findAll();
        return view('admin/coupons/index', [
            'title'   => 'Vouchers — Solqam Admin',
            'coupons' => $coupons,
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
}
