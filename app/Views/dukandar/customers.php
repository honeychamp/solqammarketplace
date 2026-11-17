<?= $this->extend('layouts/dukandar') ?>
<?= $this->section('content') ?>
<?php $s = $summary ?? []; ?>
<div class="d-flex flex-wrap justify-content-between align-items-end gap-2 mb-3">
    <div>
        <h5 class="fw-bold mb-1">Customer credit history</h5>
        <p class="small text-secondary mb-0">Every customer you scanned, how much 5% you created, and what is still unpaid to Solqam.</p>
    </div>
    <?php if (! empty($approved) && (float) ($s['pending'] ?? 0) > 0): ?>
        <a class="btn btn-solqam rounded-pill" href="<?= merchant_url('credits') ?>">Send total unpaid Rs. <?= number_format((float) $s['pending'], 0) ?></a>
    <?php endif; ?>
</div>
<div class="row g-3 mb-3">
    <div class="col-6 col-md-3"><div class="card border-0 shadow-sm rounded-4 p-3"><div class="small text-muted">Customers</div><div class="fs-4 fw-bold"><?= count($customers ?? []) ?></div></div></div>
    <div class="col-6 col-md-3"><div class="card border-0 shadow-sm rounded-4 p-3"><div class="small text-muted">Credits created</div><div class="fs-4 fw-bold">Rs. <?= number_format((float) ($s['credits'] ?? 0), 0) ?></div></div></div>
    <div class="col-6 col-md-3"><div class="card border-0 shadow-sm rounded-4 p-3"><div class="small text-muted">Unpaid to Solqam</div><div class="fs-4 fw-bold text-danger">Rs. <?= number_format((float) ($s['pending'] ?? 0), 0) ?></div></div></div>
    <div class="col-6 col-md-3"><div class="card border-0 shadow-sm rounded-4 p-3"><div class="small text-muted">Released</div><div class="fs-4 fw-bold text-success">Rs. <?= number_format((float) ($s['released'] ?? 0), 0) ?></div></div></div>
</div>
<div class="card border-0 shadow-sm rounded-4 p-3">
    <?= view('partials/merchant_customer_credits', ['customers' => $customers ?? [], 'showPayHint' => ! empty($approved)]) ?>
</div>
<?= $this->endSection() ?>
