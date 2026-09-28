<?php

namespace App\Services\Commission;

use App\Models\CommissionModel;

class CommissionService
{
    protected CommissionModel $commissionModel;

    public function __construct()
    {
        $this->commissionModel = new CommissionModel();
    }

    public function getCommissionRate(): float
    {
        $rule = $this->commissionModel->getActiveRule();

        return max(0.0, min(50.0, (float) ($rule['percentage'] ?? 10)));
    }

    public function calculateCommission(float $amount): float
    {
        return round(($amount * $this->getCommissionRate()) / 100, 2);
    }

    public function updateCommissionRate(float $percentage, ?int $adminId = null): bool
    {
        $rule = $this->commissionModel->where('is_active', 1)->first();
        if ($rule) {
            return $this->commissionModel->update($rule['id'], [
                'percentage' => $percentage,
                'updated_by' => $adminId,
            ]);
        }

        return (bool) $this->commissionModel->insert([
            'name'       => 'Global Marketplace Commission',
            'percentage' => $percentage,
            'is_active'  => 1,
            'updated_by' => $adminId,
        ]);
    }
}
