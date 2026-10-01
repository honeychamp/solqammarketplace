<?php

namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use App\Models\CategoryModel;
use App\Models\CommissionModel;
use App\Services\Commission\CommissionService;

class CommissionController extends BaseController
{
    protected CommissionService $commissionService;
    protected CommissionModel $commissionModel;
    protected CategoryModel $categoryModel;

    public function __construct()
    {
        $this->commissionService = new CommissionService();
        $this->commissionModel   = new CommissionModel();
        $this->categoryModel     = new CategoryModel();
    }

    public function index()
    {
        $categories = $this->categoryModel->withPaths(
            $this->categoryModel->orderBy('parent_id', 'ASC')->orderBy('name', 'ASC')->findAll()
        );
        foreach ($categories as &$cat) {
            $cat['effective_commission'] = $this->commissionService->rateForCategory((int) $cat['id']);
        }
        unset($cat);

        return view('admin/commissions/index', [
            'title'      => 'Category Commission — Solqam Admin Console',
            'rule'       => $this->commissionModel->getActiveRule(),
            'categories' => $categories,
        ]);
    }

    public function update()
    {
        $categoryId = (int) $this->request->getPost('category_id');
        if ($categoryId > 0) {
            $raw = $this->request->getPost('commission_percent');
            $payload = [
                'commission_percent' => ($raw === null || $raw === '') ? null : max(0, min(50, (float) $raw)),
            ];
            $this->categoryModel->update($categoryId, $payload);

            return $this->redirectAfterHub('/admin/commissions', 'success', 'Category commission saved. Admin-store products stay at 0%.');
        }

        $percentage = (float) $this->request->getPost('percentage');
        if ($percentage < 0 || $percentage > 50) {
            return redirect()->back()->with('error', 'Fallback commission must be between 0% and 50%.');
        }

        $adminId = (int) session()->get('user.id');
        $this->commissionService->updateCommissionRate($percentage, $adminId);

        return $this->redirectAfterHub('/admin/commissions', 'success', "Fallback rate for uncategorized items set to {$percentage}%.");
    }
}
