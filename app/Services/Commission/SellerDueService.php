<?php

namespace App\Services\Commission;

use App\Models\SellerCodDueModel;
use App\Models\SellerProfileModel;
use App\Models\UserModel;
use RuntimeException;

class SellerDueService
{
    public const SUSPEND_AT = 5000.0;

    public function unpaidTotal(int $sellerId, ?int $exceptOrderId = null): float
    {
        if (! $this->ready()) {
            return 0.0;
        }
        $model = new SellerCodDueModel();
        $builder = $model->where('seller_id', $sellerId)->whereIn('status', ['unpaid', 'processing']);
        if ($exceptOrderId) {
            $builder->where('order_id !=', $exceptOrderId);
        }
        $sum = 0.0;
        foreach ($builder->findAll() as $row) {
            $sum += self::remaining($row);
        }

        return round($sum, 2);
    }

    public static function remaining(array $row): float
    {
        $due  = (float) ($row['due_amount'] ?? 0);
        $paid = (float) ($row['paid_amount'] ?? 0);

        return round(max(0.0, $due - $paid), 2);
    }

    public function assertCanDeliver(int $sellerId, ?int $exceptOrderId = null): void
    {
        $profile = (new SellerProfileModel())->getByUserId($sellerId);
        $status  = (string) ($profile['approval_status'] ?? '');
        if ($status === 'suspended') {
            throw new RuntimeException('Store suspended: unpaid COD commission/cashback. Pay Solqam first, then admin can reopen.');
        }
        $unpaid = $this->unpaidTotal($sellerId, $exceptOrderId);
        if ($unpaid > 0) {
            throw new RuntimeException(
                'Pay the previous COD amount due to Solqam first (Rs. ' . number_format($unpaid, 0) . '). After confirm, commission is due on Pay Solqam even if you do not deliver.'
            );
        }
    }

    /**
     * COD self-ship: commission bill on Confirm. Must be paid even if never delivered.
     */
    public function recordSellerCodCommission(
        int $sellerId,
        int $orderId,
        int $shipmentId,
        array $totals,
        float $shipAmt,
        string $method,
        bool $notify = true
    ): void {
        $commission = round((float) ($totals['commission'] ?? 0), 2);
        $goods      = round((float) ($totals['goods'] ?? 0), 2);
        if ($commission <= 0 || ! $this->ready()) {
            return;
        }

        $model    = new SellerCodDueModel();
        $existing = $model->where('seller_id', $sellerId)->where('order_id', $orderId)->first();
        $cashback = round((float) ($existing['cashback_amount'] ?? 0), 2);
        $due      = round($commission + $cashback, 2);
        $payload  = [
            'seller_id'         => $sellerId,
            'order_id'          => $orderId,
            'shipment_id'       => $shipmentId ?: null,
            'goods_amount'      => $goods,
            'shipping_amount'   => round($shipAmt, 2),
            'commission_amount' => $commission,
            'due_amount'        => $due,
            'collected_cash'    => round($goods + $shipAmt, 2),
            'payment_method'    => $method,
            'fulfill_by'        => 'seller',
        ];
        if ($existing) {
            $st = (string) ($existing['status'] ?? '');
            if (in_array($st, ['paid', 'waived'], true)) {
                $model->update((int) $existing['id'], [
                    'shipment_id'       => $shipmentId ?: ($existing['shipment_id'] ?? null),
                    'goods_amount'      => $goods,
                    'shipping_amount'   => round($shipAmt, 2),
                    'commission_amount' => $commission,
                ]);

                return;
            }
            $model->update((int) $existing['id'], $payload);
        } else {
            $payload['cashback_amount'] = 0;
            $payload['status']          = 'unpaid';
            $payload['paid_amount']     = 0;
            $payload['created_at']      = date('Y-m-d H:i:s');
            $model->insert($payload);
            if ($notify) {
                $this->notifySeller(
                    $sellerId,
                    'Solqam: order confirmed. Pay commission Rs. ' . number_format($commission, 0) . ' on Pay Solqam (due even if you do not deliver).',
                    'cod_due'
                );
            }
        }
        $this->tryOffsetFromWallet($sellerId);
        $this->applyHoldRules($sellerId);
    }

    /** Cashback is added to the same Solqam Pay bill only after Delivered. */
    public function addSellerCodCashback(int $sellerId, int $orderId, float $cashback): void
    {
        $cashback = round($cashback, 2);
        if ($cashback <= 0 || ! $this->ready()) {
            return;
        }
        $model = new SellerCodDueModel();
        $row   = $model->where('seller_id', $sellerId)->where('order_id', $orderId)->first();
        if (! $row || ($row['status'] ?? '') === 'waived') {
            return;
        }
        $already = round((float) ($row['cashback_amount'] ?? 0), 2);
        if ($cashback <= $already) {
            return;
        }
        $commission = round((float) ($row['commission_amount'] ?? 0), 2);
        $due        = round($commission + $cashback, 2);
        $paid       = round((float) ($row['paid_amount'] ?? 0), 2);
        $open       = $paid + 0.009 < $due;
        $st         = (string) ($row['status'] ?? 'unpaid');
        if ($open && $st === 'paid') {
            $st = 'unpaid';
        }
        $model->update((int) $row['id'], [
            'cashback_amount' => $cashback,
            'due_amount'      => $due,
            'status'          => $open ? $st : 'paid',
        ]);
        $this->tryOffsetFromWallet($sellerId);
        $this->applyHoldRules($sellerId);
    }

    public function recordSellerCodDelivery(
        int $sellerId,
        int $orderId,
        int $shipmentId,
        array $totals,
        float $shipAmt,
        string $method,
        bool $notify = true
    ): void {
        $this->recordSellerCodCommission($sellerId, $orderId, $shipmentId, $totals, $shipAmt, $method, $notify);
        $this->addSellerCodCashback($sellerId, $orderId, (float) ($totals['cashback'] ?? 0));
    }

    /** Keep commission due if the parcel never went out. Drop cashback that was not earned. */
    public function waiveForOrder(int $orderId): void
    {
        if (! $this->ready()) {
            return;
        }
        $model = new SellerCodDueModel();
        $rows  = $model->where('order_id', $orderId)->findAll();
        foreach ($rows as $row) {
            $st = (string) ($row['status'] ?? '');
            if ($st === 'waived') {
                continue;
            }
            $commission = round((float) ($row['commission_amount'] ?? 0), 2);
            $paid       = round((float) ($row['paid_amount'] ?? 0), 2);
            $done       = $paid + 0.009 >= $commission;
            $model->update((int) $row['id'], [
                'cashback_amount' => 0,
                'due_amount'      => $commission,
                'status'          => $done ? 'paid' : ($st === 'processing' ? 'processing' : 'unpaid'),
                'paid_note'       => substr(trim(($row['paid_note'] ?? '') . ' Cashback dropped on void; commission still due.'), -240),
            ]);
            $this->liftHoldIfCleared((int) $row['seller_id']);
        }
    }

    public function markPaid(int $dueId, string $note = '', ?float $amount = null): void
    {
        $model = new SellerCodDueModel();
        $row   = $model->find($dueId);
        if (! $row) {
            throw new RuntimeException('Due record not found.');
        }
        if (in_array((string) ($row['status'] ?? ''), ['paid', 'waived'], true) && self::remaining($row) <= 0) {
            return;
        }
        $remain = self::remaining($row);
        if ($remain <= 0) {
            $model->update($dueId, ['status' => 'paid', 'paid_at' => date('Y-m-d H:i:s')]);
            $this->liftHoldIfCleared((int) $row['seller_id']);

            return;
        }
        $take = $amount === null || $amount <= 0 ? $remain : min($remain, round($amount, 2));
        $paid = round((float) ($row['paid_amount'] ?? 0) + $take, 2);
        $done = $paid + 0.009 >= (float) $row['due_amount'];
        $model->update($dueId, [
            'paid_amount' => $paid,
            'status'      => $done ? 'paid' : 'unpaid',
            'paid_at'     => $done ? date('Y-m-d H:i:s') : ($row['paid_at'] ?? null),
            'paid_note'   => substr(trim(($row['paid_note'] ?? '') . ' ' . ($note !== '' ? $note : 'Received Rs. ' . number_format($take, 0))), -240),
        ]);
        $this->liftHoldIfCleared((int) $row['seller_id']);
    }

    public static function isOpen(?string $status): bool
    {
        return in_array((string) $status, ['unpaid', 'processing'], true);
    }

    public function submitProof(int $sellerId, int $dueId, string $proofPath, string $note = '', bool $notify = true): void
    {
        $model = new SellerCodDueModel();
        $row   = $model->find($dueId);
        if (! $row || (int) ($row['seller_id'] ?? 0) !== $sellerId) {
            throw new RuntimeException('Due record not found.');
        }
        if (! self::isOpen($row['status'] ?? '') || self::remaining($row) <= 0) {
            throw new RuntimeException('This due is already cleared.');
        }
        $path = trim($proofPath);
        if ($path === '') {
            throw new RuntimeException('Upload a screenshot of the payment.');
        }
        $model->update($dueId, [
            'status'       => 'processing',
            'proof_path'   => $path,
            'proof_note'   => substr(trim($note), 0, 240),
            'submitted_at' => date('Y-m-d H:i:s'),
        ]);
        if ($notify) {
            $this->notifySeller(
                $sellerId,
                'Solqam: payment screenshot received. COD due #' . $dueId . ' is processing until admin confirms.',
                'cod_due_proof'
            );
        }
    }

    /** One screenshot covers every open COD due for this seller (full total payment). */
    public function submitProofForOpenDues(int $sellerId, string $proofPath, string $note = ''): int
    {
        $path = trim($proofPath);
        if ($path === '') {
            throw new RuntimeException('Upload a screenshot of the payment.');
        }
        $rows = (new SellerCodDueModel())
            ->where('seller_id', $sellerId)
            ->whereIn('status', ['unpaid', 'processing'])
            ->orderBy('id', 'ASC')
            ->findAll();
        $n = 0;
        $total = 0.0;
        foreach ($rows as $row) {
            if (self::remaining($row) <= 0) {
                continue;
            }
            $total += self::remaining($row);
            $this->submitProof($sellerId, (int) $row['id'], $path, $note !== '' ? $note : 'Bulk total payment', false);
            $n++;
        }
        if ($n < 1) {
            throw new RuntimeException('No unpaid Solqam dues to cover.');
        }
        $this->notifySeller(
            $sellerId,
            'Solqam: total payment screenshot received for Rs. ' . number_format($total, 0) . ' across ' . $n . ' dues. Processing until admin confirms.',
            'cod_due_proof_bulk'
        );

        return $n;
    }

    /** Admin confirms money received — apply across all open dues (FIFO), or full remaining if amount is empty. */
    public function markPaidOpenForSeller(int $sellerId, string $note = '', ?float $amount = null, bool $processingOnly = true): float
    {
        $model = new SellerCodDueModel();
        $model->where('seller_id', $sellerId);
        if ($processingOnly) {
            $model->where('status', 'processing');
        } else {
            $model->whereIn('status', ['unpaid', 'processing']);
        }
        $rows = $model->orderBy('id', 'ASC')->findAll();
        $left    = $amount === null || $amount <= 0 ? null : round($amount, 2);
        $cleared = 0.0;
        foreach ($rows as $row) {
            $remain = self::remaining($row);
            if ($remain <= 0) {
                continue;
            }
            if ($left === null) {
                $this->markPaid((int) $row['id'], $note !== '' ? $note : 'Bulk payment confirmed', $remain);
                $cleared += $remain;
                continue;
            }
            if ($left <= 0) {
                break;
            }
            $take = min($remain, $left);
            $this->markPaid((int) $row['id'], $note !== '' ? $note : 'Bulk payment confirmed', $take);
            $left    -= $take;
            $cleared += $take;
        }
        if ($cleared <= 0) {
            throw new RuntimeException('No open dues to mark paid.');
        }

        return round($cleared, 2);
    }

    public function processingLines(): array
    {
        if (! $this->ready()) {
            return [];
        }
        $rows = (new SellerCodDueModel())
            ->select('seller_cod_dues.*, orders.order_number')
            ->join('orders', 'orders.id = seller_cod_dues.order_id', 'left')
            ->where('seller_cod_dues.status', 'processing')
            ->orderBy('seller_cod_dues.submitted_at', 'DESC')
            ->orderBy('seller_cod_dues.id', 'DESC')
            ->findAll();
        foreach ($rows as &$row) {
            $p = (new SellerProfileModel())->getByUserId((int) $row['seller_id']);
            $u = (new UserModel())->find((int) $row['seller_id']);
            $row['store_name']  = $p['store_name'] ?? 'Seller';
            $row['profile_id']  = (int) ($p['id'] ?? 0);
            $row['seller_name'] = $u['name'] ?? '';
        }
        unset($row);

        return $rows;
    }

    /** Use seller online-payout wallet to clear COD dues automatically. */
    public function tryOffsetFromWallet(int $sellerId): float
    {
        if (! $this->ready() || $sellerId < 1) {
            return 0.0;
        }
        $wallet = new \App\Services\Wallet\WalletService();
        $left   = $wallet->getBalance($sellerId);
        if ($left <= 0) {
            return 0.0;
        }
        $cleared = 0.0;
        $rows    = (new SellerCodDueModel())
            ->where('seller_id', $sellerId)
            ->whereIn('status', ['unpaid', 'processing'])
            ->orderBy('id', 'ASC')
            ->findAll();
        foreach ($rows as $row) {
            if ($left <= 0) {
                break;
            }
            $remain = self::remaining($row);
            if ($remain <= 0) {
                continue;
            }
            $take = min($remain, $left);
            try {
                $wallet->debit(
                    $sellerId,
                    $take,
                    'cod_due_offset',
                    (int) $row['id'],
                    'Auto-cut COD commission/cashback from payout wallet, due #' . $row['id']
                );
            } catch (\Throwable $e) {
                break;
            }
            $this->markPaid((int) $row['id'], 'Wallet auto-offset Rs. ' . number_format($take, 0), $take);
            $left    -= $take;
            $cleared += $take;
        }
        if ($cleared > 0) {
            $this->notifySeller(
                $sellerId,
                'Solqam: Rs. ' . number_format($cleared, 0) . ' COD due auto-cut from your payout wallet.',
                'cod_due_offset'
            );
        }

        return round($cleared, 2);
    }

    public function backfillMissing(): int
    {
        if (! $this->ready()) {
            return 0;
        }
        $db = \Config\Database::connect();
        if (! $db->tableExists('shipments') || ! $db->tableExists('payments')) {
            return 0;
        }
        $created = 0;
        $rows    = $db->table('shipments s')
            ->select('s.id as shipment_id, s.seller_id, s.order_id, s.shipping_amount, s.status, p.payment_method')
            ->join('payments p', 'p.order_id = s.order_id', 'left')
            ->where('s.fulfill_by', 'seller')
            ->whereIn('s.status', ['confirmed', 'shipped', 'delivered'])
            ->whereIn('p.payment_method', ['cod', 'pay_later'])
            ->get()
            ->getResultArray();
        foreach ($rows as $row) {
            $sid = (int) $row['seller_id'];
            $oid = (int) $row['order_id'];
            if ($sid < 1 || $oid < 1) {
                continue;
            }
            $exists = $db->table('seller_cod_dues')->where('seller_id', $sid)->where('order_id', $oid)->countAllResults();
            if ($exists > 0) {
                continue;
            }
            $items  = $db->table('order_items')->where('order_id', $oid)->where('seller_id', $sid)->get()->getResultArray();
            $totals = ['goods' => 0.0, 'commission' => 0.0, 'cashback' => 0.0];
            foreach ($items as $item) {
                $totals['goods'] += (float) ($item['subtotal'] ?? 0) + (float) ($item['gift_wrap_fee'] ?? 0);
                $totals['commission'] += (float) ($item['commission_amount'] ?? 0);
                $totals['cashback'] += function_exists('item_cashback') ? item_cashback($item) : (float) ($item['cashback_amount'] ?? 0);
            }
            $shipStatus = (string) ($row['status'] ?? '');
            $this->recordSellerCodCommission(
                $sid,
                $oid,
                (int) $row['shipment_id'],
                $totals,
                (float) ($row['shipping_amount'] ?? 0),
                (string) ($row['payment_method'] ?? 'cod'),
                false
            );
            if ($shipStatus === 'delivered') {
                $this->addSellerCodCashback($sid, $oid, (float) $totals['cashback']);
            }
            $created++;
        }

        return $created;
    }

    protected function notifySeller(int $sellerId, string $message, string $event): void
    {
        try {
            $u = (new UserModel())->find($sellerId);
            $phone = (string) ($u['phone'] ?? '');
            if ($phone !== '') {
                \App\Services\Sms\SmsNotifier::send($phone, $message, $event);
            }
        } catch (\Throwable $e) {
        }
    }

    public function applyHoldRules(int $sellerId): void
    {
        $unpaid = $this->unpaidTotal($sellerId);
        if ($unpaid < self::SUSPEND_AT) {
            return;
        }
        $profiles = new SellerProfileModel();
        $profile  = $profiles->getByUserId($sellerId);
        if (! $profile || ($profile['approval_status'] ?? '') === 'suspended') {
            return;
        }
        $profiles->update((int) $profile['id'], [
            'approval_status'  => 'suspended',
            'rejection_reason' => 'Auto-suspended: unpaid COD commission + cashback Rs. '
                . number_format($unpaid, 0) . ' (limit Rs. ' . number_format(self::SUSPEND_AT, 0) . ').',
        ]);
        (new UserModel())->update($sellerId, ['status' => 'suspended']);
        $this->notifySeller(
            $sellerId,
            'Solqam: Store suspended. Unpaid COD commission+cashback Rs. ' . number_format($unpaid, 0) . '. Pay to reopen.',
            'cod_due_suspend'
        );
    }

    public function liftHoldIfCleared(int $sellerId): void
    {
        $unpaid  = $this->unpaidTotal($sellerId);
        $profile = (new SellerProfileModel())->getByUserId($sellerId);
        if (! $profile) {
            return;
        }
        $reason = (string) ($profile['rejection_reason'] ?? '');
        $auto   = str_contains($reason, 'Auto-suspended: unpaid COD');
        if (($profile['approval_status'] ?? '') !== 'suspended' || ! $auto) {
            return;
        }
        if ($unpaid >= self::SUSPEND_AT) {
            return;
        }
        (new SellerProfileModel())->update((int) $profile['id'], [
            'approval_status'  => 'approved',
            'rejection_reason' => $unpaid > 0 ? 'COD dues still open: Rs. ' . number_format($unpaid, 0) : null,
        ]);
        (new UserModel())->update($sellerId, ['status' => 'active']);
    }

    public function summary(int $sellerId): array
    {
        $empty = [
            'unpaid' => 0.0, 'paid' => 0.0, 'waived' => 0.0, 'commission' => 0.0,
            'cashback' => 0.0, 'collected' => 0.0, 'goods' => 0.0, 'shipping' => 0.0, 'count' => 0,
        ];
        if (! $this->ready()) {
            return $empty;
        }
        $rows = (new SellerCodDueModel())->where('seller_id', $sellerId)->findAll();
        $out  = [
            'unpaid'     => 0.0,
            'paid'       => 0.0,
            'waived'     => 0.0,
            'commission' => 0.0,
            'cashback'   => 0.0,
            'collected'  => 0.0,
            'goods'      => 0.0,
            'shipping'   => 0.0,
            'count'      => count($rows),
        ];
        foreach ($rows as $row) {
            $st = $row['status'] ?? 'unpaid';
            $due = (float) $row['due_amount'];
            if (self::isOpen($st)) {
                $out['unpaid'] += self::remaining($row);
                $out['paid'] += (float) ($row['paid_amount'] ?? 0);
            } elseif ($st === 'paid') {
                $out['paid'] += $due;
            } else {
                $out['waived'] += $due;
            }
            $out['commission'] += (float) $row['commission_amount'];
            $out['cashback'] += (float) $row['cashback_amount'];
            $out['collected'] += (float) $row['collected_cash'];
            $out['goods'] += (float) $row['goods_amount'];
            $out['shipping'] += (float) $row['shipping_amount'];
        }
        foreach ($out as $k => $v) {
            if ($k !== 'count') {
                $out[$k] = round((float) $v, 2);
            }
        }

        return $out;
    }

    public function linesForSeller(int $sellerId): array
    {
        if (! $this->ready()) {
            return [];
        }

        return (new SellerCodDueModel())
            ->select('seller_cod_dues.*, orders.order_number, orders.total_amount, orders.final_payable, orders.wallet_amount_used, orders.status as order_status')
            ->join('orders', 'orders.id = seller_cod_dues.order_id', 'left')
            ->where('seller_cod_dues.seller_id', $sellerId)
            ->orderBy('seller_cod_dues.id', 'DESC')
            ->findAll();
    }

    public function unpaidSellers(): array
    {
        if (! $this->ready()) {
            return [];
        }
        $map = [];
        foreach ((new SellerCodDueModel())->findAll() as $row) {
            $sid = (int) $row['seller_id'];
            if (! isset($map[$sid])) {
                $map[$sid] = [
                    'seller_id'  => $sid,
                    'unpaid'     => 0.0,
                    'commission' => 0.0,
                    'cashback'   => 0.0,
                    'collected'  => 0.0,
                    'rows'       => 0,
                ];
            }
            $map[$sid]['rows']++;
            $map[$sid]['commission'] += (float) $row['commission_amount'];
            $map[$sid]['cashback'] += (float) $row['cashback_amount'];
            $map[$sid]['collected'] += (float) $row['collected_cash'];
            if (self::isOpen($row['status'] ?? '')) {
                $map[$sid]['unpaid'] += self::remaining($row);
            }
        }
        $map = array_values(array_filter($map, static fn ($r) => $r['unpaid'] > 0));
        usort($map, static fn ($a, $b) => $b['unpaid'] <=> $a['unpaid']);
        foreach ($map as &$row) {
            $p = (new SellerProfileModel())->getByUserId((int) $row['seller_id']);
            $u = (new UserModel())->find((int) $row['seller_id']);
            $row['store_name']  = $p['store_name'] ?? 'Seller';
            $row['profile_id']  = (int) ($p['id'] ?? 0);
            $row['seller_name'] = $u['name'] ?? '';
            $row['phone']       = $u['phone'] ?? '';
            $row['approval']    = $p['approval_status'] ?? '';
        }
        unset($row);

        return $map;
    }

    public function unpaidSnapshot(): array
    {
        if (! $this->ready()) {
            return ['amount' => 0.0, 'sellers' => 0];
        }

        try {
            $row = \Config\Database::connect()->query(
                "SELECT COALESCE(SUM(GREATEST(due_amount - paid_amount, 0)), 0) AS amount,
                        COUNT(DISTINCT seller_id) AS sellers
                 FROM seller_cod_dues
                 WHERE status IN ('unpaid', 'processing')
                   AND due_amount > paid_amount"
            )->getRowArray();

            return [
                'amount'  => round((float) ($row['amount'] ?? 0), 2),
                'sellers' => (int) ($row['sellers'] ?? 0),
            ];
        } catch (\Throwable $e) {
            return ['amount' => 0.0, 'sellers' => 0];
        }
    }

    protected function ready(): bool
    {
        try {
            return \Config\Database::connect()->tableExists('seller_cod_dues');
        } catch (\Throwable $e) {
            return false;
        }
    }

    public function forOrder(int $orderId): array
    {
        if (! $this->ready()) {
            return [];
        }

        return (new SellerCodDueModel())
            ->select('seller_cod_dues.*, seller_profiles.store_name')
            ->join('seller_profiles', 'seller_profiles.user_id = seller_cod_dues.seller_id', 'left')
            ->where('seller_cod_dues.order_id', $orderId)
            ->findAll();
    }
}
