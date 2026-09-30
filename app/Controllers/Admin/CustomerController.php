<?php

namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use App\Models\UserModel;

class CustomerController extends BaseController
{
    protected UserModel $userModel;

    public function __construct()
    {
        $this->userModel = new UserModel();
    }

    public function index()
    {
        $customers = $this->userModel
            ->where('role', 'customer')
            ->orderBy('id', 'DESC')
            ->paginate(50);

        $insight = new \App\Services\Wallet\WalletService();
        foreach ($customers as &$c) {
            $c['wallet_balance'] = $insight->getBalance((int) $c['id']);
        }
        unset($c);

        return view('admin/customers/index', [
            'title'     => 'Customer Directory — Solqam Admin Console',
            'customers' => $customers,
            'pager'     => $this->userModel->pager,
        ]);
    }

    public function show($id)
    {
        $dossier = (new \App\Services\Customer\CustomerInsightService())->dossier((int) $id);
        if (!$dossier) {
            return redirect()->to('/admin/customers')->with('error', 'Customer not found.');
        }

        return view('shared/customer_dossier', [
            'title'   => 'Customer 360 — ' . $dossier['user']['name'],
            'dossier' => $dossier,
            'mode'    => 'admin',
        ]);
    }

    public function toggleStatus($id)
    {
        $user = $this->userModel->find($id);
        if ($user && $user['role'] === 'customer') {
            $newStatus = ($user['status'] === 'active') ? 'suspended' : 'active';
            $this->userModel->update($id, ['status' => $newStatus]);
            return redirect()->to('/admin/customers')->with('success', "Customer account status changed to {$newStatus}.");
        }
        return redirect()->to('/admin/customers')->with('error', 'Customer not found.');
    }
}
