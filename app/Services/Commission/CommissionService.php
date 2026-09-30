<?php

namespace App\Services\Commission;

use App\Models\CategoryModel;
use App\Models\CommissionModel;

class CommissionService
{
    protected CommissionModel $commissionModel;
    protected CategoryModel $categoryModel;
    /** @var array<int, array|null> */
    protected array $categoryCache = [];

    public function __construct()
    {
        $this->commissionModel = new CommissionModel();
        $this->categoryModel   = new CategoryModel();
    }

    public function getCommissionRate(): float
    {
        $rule = $this->commissionModel->getActiveRule();

        return $this->clamp((float) ($rule['percentage'] ?? 10));
    }

    public function rateForCategory(?int $categoryId): float
    {
        return $this->resolveCategoryRate($categoryId, 0);
    }

    public function calculateCommission(float $amount, ?int $categoryId = null): float
    {
        $rate = $categoryId ? $this->rateForCategory($categoryId) : $this->getCommissionRate();

        return round(($amount * $rate) / 100, 2);
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
            'name'       => 'Uncategorized fallback commission',
            'percentage' => $percentage,
            'is_active'  => 1,
            'updated_by' => $adminId,
        ]);
    }

    protected function resolveCategoryRate(?int $categoryId, int $depth): float
    {
        if ($depth > 8 || !$categoryId) {
            return $this->getCommissionRate();
        }

        if (! array_key_exists($categoryId, $this->categoryCache)) {
            $this->categoryCache[$categoryId] = $this->categoryModel->find($categoryId);
        }

        $cat = $this->categoryCache[$categoryId];
        if (! $cat) {
            return $this->getCommissionRate();
        }

        $raw = $cat['commission_percent'] ?? null;
        if ($raw !== null && $raw !== '') {
            return $this->clamp((float) $raw);
        }

        $parentId = (int) ($cat['parent_id'] ?? 0);

        return $parentId > 0
            ? $this->resolveCategoryRate($parentId, $depth + 1)
            : $this->getCommissionRate();
    }

    protected function clamp(float $percentage): float
    {
        return max(0.0, min(50.0, $percentage));
    }
}
