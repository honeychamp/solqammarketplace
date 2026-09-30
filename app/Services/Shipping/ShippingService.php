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

    public function normalizeCity(?string $city): string
    {
        $city = strtolower(trim((string) $city));
        $city = preg_replace('/\s+/', ' ', $city) ?? $city;

        return $city;
    }

    public function quote(?string $city, ?string $province, float $subtotal): array
    {
        $zone = $this->matchZone($city, $province);

        $rate      = $zone ? (float) $zone['rate'] : 249.00;
        $freeAbove = $zone ? (float) $zone['free_above'] : 3000.00;
        $eta       = $zone['eta_days'] ?? '3-5';
        $matched   = $zone['city'] ?? null;
        $amount    = $subtotal >= $freeAbove ? 0.0 : $rate;

        return [
            'amount'     => $amount,
            'rate'       => $rate,
            'free_above' => $freeAbove,
            'eta_days'   => $eta,
            'is_free'    => $amount <= 0,
            'city'       => trim((string) $city),
            'matched'    => $matched,
            'is_default' => $zone ? $this->isDefaultLabel((string) $zone['city']) : true,
        ];
    }

    public function matchZone(?string $city, ?string $province = null): ?array
    {
        $zones = $this->zoneModel->orderBy('id', 'ASC')->findAll();
        $want  = $this->normalizeCity($city);

        if ($want !== '') {
            foreach ($zones as $zone) {
                if ($this->isDefaultLabel((string) $zone['city'])) {
                    continue;
                }
                if ($this->normalizeCity($zone['city']) === $want) {
                    return $zone;
                }
            }
        }

        $prov = $this->normalizeCity($province);
        if ($prov !== '') {
            foreach ($zones as $zone) {
                if ($this->isDefaultLabel((string) $zone['city'])) {
                    continue;
                }
                if ($this->normalizeCity((string) ($zone['province'] ?? '')) === $prov && $this->normalizeCity($zone['city']) === $prov) {
                    return $zone;
                }
            }
        }

        foreach ($zones as $zone) {
            if ($this->isDefaultLabel((string) $zone['city'])) {
                return $zone;
            }
        }

        return $zones[0] ?? null;
    }

    public function isDefaultLabel(string $city): bool
    {
        return in_array($this->normalizeCity($city), ['default', '*', 'other', 'pakistan', 'all'], true);
    }

    public function cityList(): array
    {
        $rows = $this->zoneModel->orderBy('city', 'ASC')->findAll();

        return array_values(array_filter($rows, fn ($z) => ! $this->isDefaultLabel((string) $z['city'])));
    }
}
