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
        if ($this->normalizeCity($city) === '') {
            return [
                'amount'     => 0.0,
                'rate'       => 0.0,
                'free_above' => 0.0,
                'eta_days'   => '',
                'is_free'    => false,
                'city'       => '',
                'matched'    => null,
                'is_default' => false,
                'unlisted'   => false,
                'needs_city' => true,
            ];
        }

        $zone = $this->matchZone($city, $province);
        $fromFallback = false;

        if (! $zone) {
            $fromFallback = true;
            $zone         = $this->unlistedCityFallback();
        }

        $rate      = $zone ? (float) $zone['rate'] : 0.0;
        $freeAbove = $zone ? (float) ($zone['free_above'] ?? 0) : 0.0;
        $eta       = $zone['eta_days'] ?? '3-5';
        $matched   = $fromFallback ? null : ($zone['city'] ?? null);
        $isFree    = $freeAbove > 0 && $subtotal >= $freeAbove;
        $amount    = $isFree ? 0.0 : $rate;
        if ($rate <= 0) {
            $isFree = true;
            $amount = 0.0;
        }

        return [
            'amount'     => round($amount, 2),
            'rate'       => $rate,
            'free_above' => $freeAbove,
            'eta_days'   => $eta,
            'is_free'    => $isFree,
            'city'       => trim((string) $city),
            'matched'    => $matched,
            'is_default' => $zone ? $this->isDefaultLabel((string) $zone['city']) : false,
            'unlisted'   => $fromFallback,
            'needs_city' => false,
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

        return null;
    }

    public function unlistedCityFallback(): ?array
    {
        $zones = $this->zoneModel->orderBy('id', 'ASC')->findAll();
        if ($zones === []) {
            return null;
        }

        $maxRate = 0.0;
        $eta     = '3-5';
        foreach ($zones as $zone) {
            if ($this->isDefaultLabel((string) $zone['city'])) {
                continue;
            }
            $rate = (float) $zone['rate'];
            if ($rate >= $maxRate) {
                $maxRate = $rate;
                $eta     = $zone['eta_days'] ?? $eta;
            }
        }

        return [
            'city'       => 'Default',
            'rate'       => $maxRate,
            'free_above' => 0,
            'eta_days'   => $eta,
        ];
    }

    public function publicHint(): array
    {
        $zones = array_values(array_filter(
            $this->zoneModel->orderBy('city', 'ASC')->findAll(),
            fn ($z) => ! $this->isDefaultLabel((string) $z['city'])
        ));
        if ($zones === []) {
            return [
                'has_rates' => false,
                'min_rate'  => 0.0,
                'max_rate'  => 0.0,
                'label'     => 'At checkout',
            ];
        }

        $rates = array_map(static fn ($z) => (float) $z['rate'], $zones);
        $min   = min($rates);
        $max   = max($rates);

        return [
            'has_rates' => true,
            'min_rate'  => $min,
            'max_rate'  => $max,
            'label'     => $min === $max
                ? 'Rs. ' . number_format($min, 0)
                : 'From Rs. ' . number_format($min, 0),
        ];
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
