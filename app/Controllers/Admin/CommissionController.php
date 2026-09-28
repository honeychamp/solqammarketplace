<?php

namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use App\Models\CommissionModel;
use App\Services\Commission\CommissionService;

class CommissionController extends BaseController
{
    protected CommissionService $commissionService;
    protected CommissionModel $commissionModel;

    public function __construct()
    {
        $this->commissionService = new CommissionService();
        $this->commissionModel   = new CommissionModel();
    }

    public function index()
    {
        $rule = $this->commissionModel->getActiveRule();

        return view('admin/commissions/index', [
            'title' => 'Platform Commission Settings — Solqam Admin Console',
            'rule'  => $rule,
        ]);
    }

    public function update()
    {
        $percentage = (float) $this->request->getPost('percentage');
        if ($percentage < 0 || $percentage > 50) {
            return redirect()->back()->with('error', 'Commission percentage must be between 0% and 50%.');
        }

        $adminId = (int) session()->get('user.id');
        $this->commissionService->updateCommissionRate($percentage, $adminId);

        return $this->redirectAfterHub('/admin/commissions', 'success', "Platform commission updated to {$percentage}% on seller items (0% on admin store).");
    }
}
