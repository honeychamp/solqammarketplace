<?= $this->extend('layouts/dukandar') ?>
<?= $this->section('content') ?>
<?php $accounts = $accounts ?? []; $s = $summary ?? []; ?>
<h5 class="fw-bold">Pay Solqam — full or partial</h5>
<p class="small text-secondary">Unpaid 5% is queued by time. 9am customer is first. Partial payments allowed. Screenshot is required — wallets credit only after Solqam admin approves.</p>
<div class="row g-3 mb-4">
    <div class="col-md-4"><div class="card border-0 shadow-sm rounded-4 p-3"><div class="small text-muted">Still unpaid</div><div class="fs-4 fw-bold text-danger">Rs. <?= number_format((float) ($s['pending'] ?? 0), 0) ?></div><div class="small text-muted">Oldest visit is next in line</div></div></div>
    <div class="col-md-4"><div class="card border-0 shadow-sm rounded-4 p-3"><div class="small text-muted">Waiting admin</div><div class="fs-4 fw-bold text-warning">Rs. <?= number_format((float) ($s['processing'] ?? 0), 0) ?></div></div></div>
    <div class="col-md-4"><div class="card border-0 shadow-sm rounded-4 p-3"><div class="small text-muted">Released after approval</div><div class="fs-4 fw-bold text-success">Rs. <?= number_format((float) ($s['released'] ?? 0), 0) ?></div></div></div>
</div>
<?= view('partials/solqam_collection_accounts', [
    'accounts'       => $accounts ?? [],
    'hasPayTo'       => $hasPayTo ?? false,
    'collectionHint' => 'Same JazzCash / EasyPaisa / bank details as Seller Hub. Partial payments are applied first-come, first-served.',
]) ?>
<div class="card border-0 shadow-sm rounded-4 p-3 mb-4">
    <h6 class="fw-bold mb-2">Release order (first in, first paid)</h6>
    <?php $fifo = $fifo ?? []; ?>
    <?php if ($fifo === []): ?>
        <p class="small text-muted mb-0">No unpaid credits in the queue.</p>
    <?php else: ?>
        <div class="table-responsive">
            <table class="table table-sm align-middle mb-0">
                <thead><tr><th>#</th><th>When</th><th>Customer</th><th>Still unpaid</th></tr></thead>
                <tbody>
                <?php foreach ($fifo as $i => $row): ?>
                    <tr>
                        <td><?= $i + 1 ?></td>
                        <td class="small text-muted"><?= esc($row['created_at'] ?? '') ?></td>
                        <td><?= esc($row['customer_name'] ?? 'Customer') ?><div class="small font-monospace"><?= esc(merchant_scan_pan($row)) ?></div></td>
                        <td class="fw-bold text-danger">Rs. <?= number_format((float) ($row['remaining'] ?? 0), 0) ?></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>
<div class="card border-0 shadow-sm rounded-4 p-3 mb-4">
    <h6 class="fw-bold mb-2">How this total is built</h6>
    <?= view('partials/merchant_customer_credits', ['customers' => $customers ?? [], 'showPayHint' => false, 'compact' => true]) ?>
</div>
<?php if ((float) ($s['pending'] ?? 0) > 0): ?>
<div class="card border-0 shadow-sm rounded-4 p-4 mb-4" style="border:1px solid #FDE68A !important;background:#FFFBEB;">
    <h6 class="fw-bold">Submit payment (partial allowed)</h6>
    <p class="small text-secondary">Enter the amount you sent. Screenshot is mandatory. Admin checks it, then credits release in queue order (9am first).</p>
    <form method="post" action="<?= merchant_url('credits/proof-all') ?>" enctype="multipart/form-data">
        <?= csrf_field() ?>
        <div class="row g-2 align-items-end">
            <div class="col-md-3">
                <label class="form-label small mb-1">Amount sent (PKR)</label>
                <input class="form-control form-control-lg" type="number" name="pay_amount" min="1" step="1" max="<?= (int) ceil((float) $s['pending']) ?>" value="<?= (int) round((float) $s['pending']) ?>" required>
            </div>
            <div class="col-md-4">
                <label class="form-label small mb-1">Payment screenshot</label>
                <input type="file" name="proof_image" class="form-control" accept="image/*" required>
            </div>
            <div class="col-md-3">
                <label class="form-label small mb-1">TID / note</label>
                <input class="form-control" name="proof_note" placeholder="JazzCash TID (optional)">
            </div>
            <div class="col-md-2">
                <button class="btn btn-solqam rounded-pill w-100" type="submit">Submit for admin</button>
            </div>
        </div>
    </form>
</div>
<?php endif; ?>
<div class="card border-0 shadow-sm rounded-4 p-3">
    <div class="table-responsive">
        <table class="table align-middle">
            <thead><tr><th>Receipt</th><th>Card</th><th>Bill</th><th>5%</th><th>Status</th><th></th></tr></thead>
            <tbody>
            <?php foreach (($scans ?? []) as $row): ?>
                <?php $left = max(0.0, (float) $row['credit_amount'] - (float) ($row['paid_amount'] ?? 0)); ?>
                <tr>
                    <td class="font-monospace"><?= esc(merchant_receipt($row['id'])) ?></td>
                    <td class="font-monospace"><?= esc(merchant_scan_pan($row)) ?></td>
                    <td>Rs. <?= number_format((float) $row['bill_amount'], 0) ?></td>
                        <td>Rs. <?= number_format((float) $row['credit_amount'], 0) ?><?php
                            if ($left > 0 && $left < (float) $row['credit_amount']): ?>
                                <div class="small text-muted">Left Rs. <?= number_format($left, 0) ?></div>
                            <?php endif; ?></td>
                        <td><?= merchant_status_badge((string) $row['status']) ?></td>
                        <td>
                            <?php if (in_array((string) $row['status'], ['pending_payment', 'processing'], true) && $left > 0): ?>
                            <form method="post" action="<?= merchant_url('credits/' . (int) $row['id'] . '/proof') ?>" enctype="multipart/form-data" class="d-flex gap-1">
                                <?= csrf_field() ?>
                                <input type="file" name="proof_image" class="form-control form-control-sm" accept="image/*" required>
                                <button class="btn btn-sm btn-outline-primary" type="submit">Upload</button>
                            </form>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
<?= $this->endSection() ?>
<?= $this->section('scripts') ?>
<script>
document.querySelectorAll('.js-copy-btn').forEach(function (btn) {
  btn.addEventListener('click', function () {
    const text = btn.parentElement.querySelector('.js-copy')?.textContent?.trim() || '';
    if (!text || !navigator.clipboard) return;
    navigator.clipboard.writeText(text).then(function () { btn.textContent = 'Copied'; setTimeout(function () { btn.textContent = 'Copy'; }, 1200); });
  });
});
</script>
<?= $this->endSection() ?>
