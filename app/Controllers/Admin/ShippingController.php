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
            'title' => 'City delivery charges — Solqam Admin',
            'zones' => $zones,
        ]);
    }

    public function store()
    {
        $city = trim((string) $this->request->getPost('city'));
        if ($city === '') {
            return redirect()->back()->with('error', 'City name is required.');
        }

        $model = new ShippingZoneModel();
        $payload = [
            'city'       => $city,
            'province'   => trim((string) $this->request->getPost('province')) ?: null,
            'rate'       => max(0, (float) $this->request->getPost('rate')),
            'free_above' => max(0, (float) $this->request->getPost('free_above')),
            'eta_days'   => $this->request->getPost('eta_days') ?: '2-4',
        ];

        $existing = $model->where('city', $city)->first();
        if (! $existing) {
            foreach ($model->findAll() as $row) {
                if (strcasecmp(trim((string) $row['city']), $city) === 0) {
                    $existing = $row;
                    break;
                }
            }
        }

        if ($existing) {
            $model->update($existing['id'], $payload);

            return redirect()->to('/admin/shipping')->with('success', 'Delivery charges updated for ' . $city . '.');
        }

        $model->insert($payload);

        return redirect()->to('/admin/shipping')->with('success', 'Delivery charges saved for ' . $city . '. Checkout will use this rate when the customer city matches.');
    }

    public function delete($id)
    {
        (new ShippingZoneModel())->delete((int) $id);

        return redirect()->to('/admin/shipping')->with('success', 'City delivery rate removed.');
    }
}
