<?php

namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use App\Models\DukandarProfileModel;
use App\Services\Card\CardNetworkService;

class DukandarController extends BaseController
{
    public function index()
    {
        \App\Services\Platform\SchemaHeal::ensureCardNetwork();
        $status = (string) $this->request->getGet('status');
        $model  = new DukandarProfileModel();
        $builder = $model->select('dukandar_profiles.*, users.email as owner_email, users.phone as owner_phone, users.name as user_name')
            ->join('users', 'users.id = dukandar_profiles.user_id', 'left')
            ->orderBy('dukandar_profiles.id', 'DESC');
        if (in_array($status, ['pending', 'approved', 'rejected'], true)) {
            $builder->where('dukandar_profiles.approval_status', $status);
        }
        hub_apply_search($builder, [
            'dukandar_profiles.shop_name',
            'dukandar_profiles.owner_name',
            'dukandar_profiles.city',
            'users.phone',
            'users.email',
        ], 'dukandar_profiles.id');
        $rows = $builder->paginate(40);
        $ids  = array_map(static fn ($r) => (int) ($r['id'] ?? 0), $rows ?: []);
        $byId = [];
        if ($ids !== []) {
            $agg = \Config\Database::connect()->query(
                'SELECT dukandar_id,
                        COALESCE(SUM(CASE WHEN status <> "rejected" THEN GREATEST(credit_amount - COALESCE(paid_amount, 0) - COALESCE(queued_amount, 0), 0) ELSE 0 END), 0) AS pending,
                        COALESCE(SUM(CASE WHEN status <> "rejected" THEN COALESCE(queued_amount, 0) ELSE 0 END), 0) AS processing,
                        COALESCE(SUM(CASE WHEN status <> "rejected" THEN credit_amount ELSE 0 END), 0) AS credits
                 FROM dukandar_scans
                 WHERE dukandar_id IN (' . implode(',', $ids) . ')
                 GROUP BY dukandar_id'
            )->getResultArray();
            foreach ($agg as $a) {
                $byId[(int) $a['dukandar_id']] = $a;
            }
        }
        foreach ($rows as &$row) {
            $t = $byId[(int) $row['id']] ?? [];
            $row['credits_pending']    = (float) ($t['pending'] ?? 0);
            $row['credits_processing'] = (float) ($t['processing'] ?? 0);
            $row['credits_total']      = (float) ($t['credits'] ?? 0);
        }
        unset($row);

        return view('admin/dukandar/index', [
            'title'         => 'Merchants — Solqam Admin',
            'shops'         => $rows,
            'pager'         => $model->pager,
            'currentStatus' => $status,
        ]);
    }

    public function credits()
    {
        $net    = new CardNetworkService();
        $status = (string) ($this->request->getGet('status') ?: 'processing');

        return view('admin/dukandar/credits', [
            'title'       => 'Solqam Card credits — Admin',
            'rows'        => $net->adminQueue($status),
            'settlements' => $net->adminShopSettlements(),
            'payouts'     => $net->pendingPayouts(),
            'status'      => $status,
        ]);
    }

    public function show($id)
    {
        \App\Services\Platform\SchemaHeal::ensureCardNetwork();
        $shop = (new DukandarProfileModel())
            ->select('dukandar_profiles.*, users.email as owner_email, users.phone as owner_phone, users.name as user_name, users.city as user_city, users.province as user_province')
            ->join('users', 'users.id = dukandar_profiles.user_id', 'left')
            ->find((int) $id);
        if (! $shop) {
            return redirect()->to(merchant_admin_url())->with('error', 'Merchant not found.');
        }
        $net = new CardNetworkService();
        $scans = (new \App\Models\DukandarScanModel())
            ->select('dukandar_scans.*, users.name as customer_name, users.phone as customer_phone')
            ->join('users', 'users.id = dukandar_scans.customer_user_id', 'left')
            ->where('dukandar_scans.dukandar_id', (int) $id)
            ->orderBy('dukandar_scans.id', 'DESC')
            ->findAll(200);

        $card = null;
        try {
            $card = (new \App\Models\SolqamCardModel())->forUser((int) ($shop['user_id'] ?? 0));
        } catch (\Throwable $e) {
        }

        return view('admin/dukandar/show', [
            'title'     => ($shop['shop_name'] ?? 'Merchant') . ' — Solqam Admin',
            'shop'      => $shop,
            'scans'     => $scans,
            'card'      => $card,
            'customers' => $net->shopCustomerLedger((int) $id),
            'totals'    => $net->shopCreditTotals((int) $id),
        ]);
    }

    public function approve($id)
    {
        (new CardNetworkService())->setApproval((int) $id, 'approved');

        return redirect()->to(merchant_admin_url())->with('success', 'Merchant approved. They can scan Solqam Cards.');
    }

    public function reject($id)
    {
        $reason = trim((string) $this->request->getPost('reason'));
        (new CardNetworkService())->setApproval((int) $id, 'rejected', $reason !== '' ? $reason : 'Not approved');

        return redirect()->to(merchant_admin_url())->with('success', 'Merchant rejected.');
    }

    public function release($id)
    {
        try {
            (new CardNetworkService())->release((int) $id, (int) session()->get('user.id'), (string) $this->request->getPost('admin_note'));
        } catch (\Throwable $e) {
            return redirect()->back()->with('error', $e->getMessage());
        }

        return redirect()->to(merchant_admin_url('credits'))->with('success', 'Credits released to the customer wallet.');
    }

    public function cards()
    {
        \App\Services\Platform\SchemaHeal::ensureCardNetwork();
        $model = new \App\Models\SolqamCardModel();
        $builder = $model->select('solqam_cards.*, users.name as holder, users.phone, users.email')
            ->join('users', 'users.id = solqam_cards.user_id', 'left')
            ->orderBy('solqam_cards.id', 'DESC');
        hub_apply_search($builder, [
            'solqam_cards.card_code',
            'solqam_cards.pan',
            'solqam_cards.last4',
            'solqam_cards.holder_name',
            'users.name',
            'users.phone',
            'users.email',
        ], 'solqam_cards.id');
        $q = trim((string) ($this->request->getGet('q') ?? ''));
        $compact = \App\Services\Card\SolqamCardNumber::normalize($q);
        if ($compact !== '') {
            $builder->orWhere('solqam_cards.pan', $compact);
        }

        return view('admin/dukandar/cards', [
            'title' => 'Solqam Cards — Admin',
            'cards' => $builder->paginate(40),
            'pager' => $model->pager,
        ]);
    }

    public function freezeCard($id)
    {
        $model = new \App\Models\SolqamCardModel();
        $card  = $model->find((int) $id);
        if (! $card) {
            return redirect()->back()->with('error', 'Card not found.');
        }
        $frozen = empty($card['frozen_at']);
        $model->update((int) $id, [
            'frozen_at' => $frozen ? date('Y-m-d H:i:s') : null,
            'status'    => $frozen ? 'frozen' : 'active',
        ]);

        return redirect()->to('/admin/cards')->with('success', $frozen ? 'Card frozen.' : 'Card unfrozen.');
    }

    public function approvePayout($id)
    {
        try {
            $n = (new CardNetworkService())->approvePayout(
                (int) $id,
                (int) session()->get('user.id'),
                (string) $this->request->getPost('admin_note')
            );
        } catch (\Throwable $e) {
            return redirect()->back()->with('error', $e->getMessage());
        }

        return redirect()->to(merchant_admin_url('credits'))->with('success', 'Payment approved. ' . $n . ' credit line(s) released in visit order.');
    }

    public function rejectPayout($id)
    {
        try {
            (new CardNetworkService())->rejectPayout((int) $id, trim((string) $this->request->getPost('reason')) ?: 'Proof not accepted');
        } catch (\Throwable $e) {
            return redirect()->back()->with('error', $e->getMessage());
        }

        return redirect()->to(merchant_admin_url('credits'))->with('success', 'Payment rejected. Those credits went back to unpaid.');
    }

    public function releaseShop($id)
    {
        try {
            $n = (new CardNetworkService())->releaseShopProcessing(
                (int) $id,
                (int) session()->get('user.id'),
                (string) $this->request->getPost('admin_note')
            );
        } catch (\Throwable $e) {
            return redirect()->back()->with('error', $e->getMessage());
        }

        return redirect()->to(merchant_admin_url('credits'))->with('success', 'Released ' . $n . ' customer credit(s) from this merchant payment.');
    }

    public function rejectCredit($id)
    {
        try {
            (new CardNetworkService())->reject((int) $id, trim((string) $this->request->getPost('reason')) ?: 'Proof not accepted');
        } catch (\Throwable $e) {
            return redirect()->back()->with('error', $e->getMessage());
        }

        return redirect()->to(merchant_admin_url('credits'))->with('success', 'Credit request rejected. Customer pending amount is cleared.');
    }
}
