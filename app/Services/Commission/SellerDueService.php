<?php

namespace App\Services\Commission;

use App\Models\SellerCodDueModel;
use App\Models\SellerProfileModel;
use App\Models\UserModel;
use RuntimeException;

class SellerDueService
{
    public const SUSPEND_AT = 1000.0;

    public function unpaidTotal(int $sellerId, ?int $exceptOrderId = null): float
    {
        if (! $this->ready()) {
            return 0.0;
        }
        $model = new SellerCodDueModel();
        $builder = $model->where('seller_id', $sellerId)->where('status', 'unpaid');
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
                'Pehle wale COD order ka commission + cashback (Rs. ' . number_format($unpaid, 0) . ') Solqam ko ada karein. Us ke baad hi naya order Delivered mark ho sakta hai.'
            );
        }
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
        $commission = round((float) ($totals['commission'] ?? 0), 2);
        $cashback   = round((float) ($totals['cashback'] ?? 0), 2);
        $goods      = round((float) ($totals['goods'] ?? 0), 2);
        $due        = round($commission + $cashback, 2);
        if ($due <= 0 || ! $this->ready()) {
            return;
        }

        $model = new SellerCodDueModel();
        $existing = $model->where('seller_id', $sellerId)->where('order_id', $orderId)->first();
        $payload  = [
            'seller_id'          => $sellerId,
            'order_id'           => $orderId,
            'shipment_id'        => $shipmentId ?: null,
            'goods_amount'       => $goods,
            'shipping_amount'    => round($shipAmt, 2),
            'commission_amount'  => $commission,
            'cashback_amount'    => $cashback,
            'due_amount'         => $due,
            'collected_cash'     => round($goods + $shipAmt, 2),
            'payment_method'     => $method,
            'fulfill_by'         => 'seller',
            'status'             => 'unpaid',
        ];
        if ($existing) {
            if (($existing['status'] ?? '') === 'paid') {
                return;
            }
            $model->update((int) $existing['id'], $payload);
        } else {
            $payload['created_at'] = date('Y-m-d H:i:s');
            $payload['paid_amount'] = 0;
            $model->insert($payload);
            if ($notify) {
                $this->notifySeller(
                    $sellerId,
                    'Solqam: COD order #' . $orderId . ' delivered. Pay commission+cashback Rs. ' . number_format($due, 0) . ' before next Delivered.',
                    'cod_due'
                );
            }
        }
        $this->tryOffsetFromWallet($sellerId);
        $this->applyHoldRules($sellerId);
    }

    public function waiveForOrder(int $orderId): void
    {
        if (! $this->ready()) {
            return;
        }
        $model = new SellerCodDueModel();
        $rows  = $model->where('order_id', $orderId)->where('status', 'unpaid')->findAll();
        foreach ($rows as $row) {
            $model->update((int) $row['id'], [
                'status'    => 'waived',
                'paid_note' => 'Waived on return/void',
                'paid_at'   => date('Y-m-d H:i:s'),
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
            ->where('status', 'unpaid')
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
            ->select('s.id as shipment_id, s.seller_id, s.order_id, s.shipping_amount, p.payment_method')
            ->join('payments p', 'p.order_id = s.order_id', 'left')
            ->where('s.fulfill_by', 'seller')
            ->where('s.status', 'delivered')
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
                $totals['goods'] += (float) ($item['subtotal'] ?? 0);
                $totals['commission'] += (float) ($item['commission_amount'] ?? 0);
                $totals['cashback'] += function_exists('item_cashback') ? item_cashback($item) : (float) ($item['cashback_amount'] ?? 0);
            }
            $this->recordSellerCodDelivery(
                $sid,
                $oid,
                (int) $row['shipment_id'],
                $totals,
                (float) ($row['shipping_amount'] ?? 0),
                (string) ($row['payment_method'] ?? 'cod'),
                false
            );
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
            if ($st === 'unpaid') {
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
            if (($row['status'] ?? '') === 'unpaid') {
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
        $sellers = $this->unpaidSellers();
        $amount  = 0.0;
        foreach ($sellers as $s) {
            $amount += (float) $s['unpaid'];
        }

        return ['amount' => round($amount, 2), 'sellers' => count($sellers)];
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
