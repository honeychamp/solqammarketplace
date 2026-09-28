<?= $this->extend('layouts/seller') ?>
<?= $this->section('content') ?>
<h4 class="fw-bold mb-4">Payouts</h4>
<div class="row g-3 mb-4">
    <div class="col-md-6"><div class="card border-0 shadow-sm rounded-4 p-3"><div class="small text-muted">Pending</div><div class="fs-4 fw-bold">Rs. <?= number_format($pending, 0) ?></div></div></div>
    <div class="col-md-6"><div class="card border-0 shadow-sm rounded-4 p-3"><div class="small text-muted">Paid</div><div class="fs-4 fw-bold text-success">Rs. <?= number_format($paid, 0) ?></div></div></div>
</div>
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
<?= $this->endSection() ?>
