<?php

namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use App\Models\ShippingZoneModel;

class ShippingController extends BaseController
{
    public function index()
    {
        $zones = (new ShippingZoneModel())->orderBy('city', 'ASC')->findAll();
        return view('admin/shipping/index', [
            'title' => 'Shipping Zones — Solqam Admin',
            'zones' => $zones,
        ]);
    }

    public function store()
    {
        (new ShippingZoneModel())->insert([
            'city'       => $this->request->getPost('city'),
            'province'   => $this->request->getPost('province'),
            'rate'       => (float) $this->request->getPost('rate'),
            'free_above' => (float) $this->request->getPost('free_above'),
            'eta_days'   => $this->request->getPost('eta_days') ?: '2-4',
        ]);
        return redirect()->to('/admin/shipping')->with('success', 'Shipping zone saved.');
    }
}
