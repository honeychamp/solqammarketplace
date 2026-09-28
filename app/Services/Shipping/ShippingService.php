<?php

namespace App\Services\Shipping;

use App\Models\ShippingZoneModel;

class ShippingService
{
    protected ShippingZoneModel $zoneModel;

    public function __construct()
    {
        $this->zoneModel = new ShippingZoneModel();
    }

    public function quote(?string $city, ?string $province, float $subtotal): array
    {
        $city = trim((string) $city);
        $zone = null;

        if ($city !== '') {
            $zone = $this->zoneModel->where('city', $city)->first();
            if (!$zone) {
                $zone = $this->zoneModel->like('city', $city, 'both')->first();
            }
        }

        if (!$zone && $province) {
            $zone = $this->zoneModel->where('province', $province)->first();
        }

        $rate      = $zone ? (float) $zone['rate'] : 249.00;
        $freeAbove = $zone ? (float) $zone['free_above'] : 3000.00;
        $eta       = $zone['eta_days'] ?? '3-5';
        $amount    = $subtotal >= $freeAbove ? 0.0 : $rate;

        return [
            'amount'     => $amount,
            'rate'       => $rate,
            'free_above' => $freeAbove,
            'eta_days'   => $eta,
            'is_free'    => $amount <= 0,
            'city'       => $city,
        ];
    }
}
