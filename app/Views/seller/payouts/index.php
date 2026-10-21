<?= $this->extend('layouts/seller') ?>
<?= $this->section('content') ?>
<h4 class="fw-bold mb-4">Payouts</h4>
<p class="mb-3"><a href="<?= site_url('seller/payouts/statement') ?>" class="btn btn-sm btn-outline-dark rounded-pill">Print / PDF statement</a></p>
<div class="row g-3 mb-4">
    <div class="col-md-6"><div class="card border-0 shadow-sm rounded-4 p-3"><div class="small text-muted">Pending payout to you</div><div class="fs-4 fw-bold">Rs. <?= number_format($pending, 0) ?></div></div></div>
    <div class="col-md-6"><div class="card border-0 shadow-sm rounded-4 p-3"><div class="small text-muted">Paid to you</div><div class="fs-4 fw-bold text-success">Rs. <?= number_format($paid, 0) ?></div></div></div>
    <?php $dueSummary = $dueSummary ?? []; if ((float) ($dueSummary['unpaid'] ?? 0) > 0): ?>
    <div class="col-12">
        <div class="alert alert-warning rounded-4 mb-0">
            You collected COD cash. Pay Solqam <strong>Rs. <?= number_format((float) $dueSummary['unpaid'], 0) ?></strong>
            (commission Rs. <?= number_format((float) $dueSummary['commission'], 0) ?>
            + cashback Rs. <?= number_format((float) $dueSummary['cashback'], 0) ?>).
            Online payouts auto-cut this first. Next Delivered stays locked until the leftover is paid.
        </div>
    </div>
    <?php endif; ?>
</div>
<?php if (!empty($dueLines)): ?>
<h5 class="fw-bold mb-2">COD dues to Solqam</h5>
<table class="table mb-4">
    <thead><tr><th>Order</th><th>Collected</th><th>Commission</th><th>Cashback</th><th>Due</th><th>Status</th></tr></thead>
    <tbody>
    <?php foreach ($dueLines as $d): ?>
        <tr>
            <td><?= esc($d['order_number'] ?? $d['order_id']) ?></td>
            <td>Rs. <?= number_format((float) $d['collected_cash'], 0) ?></td>
            <td>Rs. <?= number_format((float) $d['commission_amount'], 0) ?></td>
            <td>Rs. <?= number_format((float) $d['cashback_amount'], 0) ?></td>
            <td>Rs. <?= number_format((float) $d['due_amount'], 0) ?> (left <?= number_format(\App\Services\Commission\SellerDueService::remaining($d), 0) ?>)</td>
            <td><?= esc($d['status']) ?></td>
        </tr>
    <?php endforeach; ?>
    </tbody>
</table>
<?php endif; ?>
<table class="table">
    <thead><tr><th>Order</th><th>Amount</th><th>Status</th></tr></thead>
    <tbody>
    <?php foreach ($payouts as $p): ?>
        <tr>
            <td><?= esc($p['order_number'] ?? '-') ?></td>
            <td>Rs. <?= number_format($p['amount'], 2) ?></td>
            <td><?= esc($p['status']) ?></td>
        </tr>
    <?php endforeach; ?>
    </tbody>
</table>
<?= view('shared/_pager', ['pager' => $pager ?? null]) ?>
<?= $this->endSection() ?>
