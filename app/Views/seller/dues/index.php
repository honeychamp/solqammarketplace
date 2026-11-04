<?= $this->extend('layouts/seller') ?>
<?= $this->section('content') ?>
<?php
$accounts = $accounts ?? [];
$dueSummary = $dueSummary ?? [];
$dueLines = $dueLines ?? [];
$unpaid = (float) ($dueSummary['unpaid'] ?? 0);
?>
<h4 class="fw-bold mb-1">Pay Solqam</h4>
<p class="text-secondary small mb-4">Commission is due as soon as an order is <strong>Confirmed</strong> — even if you do not deliver. Cashback is added after <strong>Delivered</strong>. Send JazzCash / bank, upload a screenshot, then wait for admin to mark it paid.</p>

<div class="row g-3 mb-4">
    <div class="col-md-4">
        <div class="card border-0 shadow-sm rounded-4 p-3">
            <div class="small text-muted">Still unpaid</div>
            <div class="fs-4 fw-bold text-danger">Rs. <?= number_format($unpaid, 0) ?></div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card border-0 shadow-sm rounded-4 p-3">
            <div class="small text-muted">Commission part</div>
            <div class="fs-5 fw-bold">Rs. <?= number_format((float) ($dueSummary['commission'] ?? 0), 0) ?></div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card border-0 shadow-sm rounded-4 p-3">
            <div class="small text-muted">Cashback part</div>
            <div class="fs-5 fw-bold">Rs. <?= number_format((float) ($dueSummary['cashback'] ?? 0), 0) ?></div>
        </div>
    </div>
</div>

<div class="card border-0 shadow-sm rounded-4 p-4 mb-4">
    <h5 class="fw-bold mb-2">Send money here</h5>
    <?php if (empty($hasPayTo)): ?>
        <p class="text-muted mb-0">Solqam has not published JazzCash / bank details yet. Ask admin (SLA &amp; limits → collection accounts).</p>
    <?php else: ?>
        <div class="row g-3">
            <?php if (($accounts['jazzcash_number'] ?? '') !== ''): ?>
                <div class="col-md-4">
                    <div class="border rounded-4 p-3 h-100 bg-light">
                        <div class="small text-muted">JazzCash</div>
                        <div class="fw-bold"><?= esc($accounts['jazzcash_name'] ?: 'Solqam') ?></div>
                        <div class="fs-5 font-monospace"><?= esc($accounts['jazzcash_number']) ?></div>
                    </div>
                </div>
            <?php endif; ?>
            <?php if (($accounts['easypaisa_number'] ?? '') !== ''): ?>
                <div class="col-md-4">
                    <div class="border rounded-4 p-3 h-100 bg-light">
                        <div class="small text-muted">EasyPaisa</div>
                        <div class="fw-bold"><?= esc($accounts['easypaisa_name'] ?: 'Solqam') ?></div>
                        <div class="fs-5 font-monospace"><?= esc($accounts['easypaisa_number']) ?></div>
                    </div>
                </div>
            <?php endif; ?>
            <?php if (($accounts['bank_account'] ?? '') !== ''): ?>
                <div class="col-md-4">
                    <div class="border rounded-4 p-3 h-100 bg-light">
                        <div class="small text-muted"><?= esc($accounts['bank_name'] ?: 'Bank') ?></div>
                        <div class="fw-bold"><?= esc($accounts['bank_title'] ?: 'Solqam') ?></div>
                        <div class="fs-6 font-monospace"><?= esc($accounts['bank_account']) ?></div>
                    </div>
                </div>
            <?php endif; ?>
        </div>
        <p class="small text-muted mt-3 mb-0">Send the full unpaid total in one transfer, or pay order by order. One screenshot can cover every open due.</p>
    <?php endif; ?>
</div>

<?php if ($unpaid > 0): ?>
<div class="card border-0 shadow-sm rounded-4 p-4 mb-4" style="border:1px solid #FDE68A !important;background:#FFFBEB;">
    <h5 class="fw-bold mb-1">Pay total now — Rs. <?= number_format($unpaid, 0) ?></h5>
    <p class="small text-secondary mb-3">Send the <strong>full unpaid amount</strong> in one JazzCash/bank transfer, then upload one screenshot. All open order dues move to Processing. You can still pay a single order below.</p>
    <form action="<?= site_url('seller/dues/proof-all') ?>" method="POST" enctype="multipart/form-data" class="row g-2 align-items-end">
        <?= csrf_field() ?>
        <div class="col-md-5">
            <label class="form-label small mb-1">Total payment screenshot</label>
            <input type="file" name="proof_image" class="form-control" accept="image/jpeg,image/png,image/webp" required>
        </div>
        <div class="col-md-4">
            <label class="form-label small mb-1">TID / note (optional)</label>
            <input type="text" name="proof_note" class="form-control" maxlength="240" placeholder="JazzCash TID — total payment">
        </div>
        <div class="col-md-3">
            <button class="btn btn-success rounded-pill w-100" type="submit">Submit total payment</button>
        </div>
    </form>
</div>
<?php endif; ?>

<?php if ($dueLines === []): ?>
    <div class="card border-0 shadow-sm rounded-4 p-4 text-secondary">No COD dues yet.</div>
<?php else: ?>
    <?php foreach ($dueLines as $d): ?>
        <?php
        $left = \App\Services\Commission\SellerDueService::remaining($d);
        $st = (string) ($d['status'] ?? 'unpaid');
        $proof = trim((string) ($d['proof_path'] ?? ''));
        ?>
        <div class="card border-0 shadow-sm rounded-4 p-4 mb-3">
            <div class="d-flex justify-content-between flex-wrap gap-2 mb-2">
                <div>
                    <div class="fw-bold"><?= esc($d['order_number'] ?? ('#' . $d['order_id'])) ?></div>
                    <div class="small text-success">Order <?= esc($d['order_status'] ?? 'confirmed') ?> — commission Rs. <?= number_format((float) $d['commission_amount'], 0) ?> is due to Solqam (even if not delivered).</div>
                    <div class="small text-muted">
                        <?php if ((float) ($d['cashback_amount'] ?? 0) > 0): ?>
                            Cashback Rs. <?= number_format((float) $d['cashback_amount'], 0) ?> (added after Delivered)
                        <?php else: ?>
                            Cashback is added after Delivered
                        <?php endif; ?>
                    </div>
                </div>
                <div class="text-end">
                    <div class="fw-bold <?= $left > 0 ? 'text-danger' : 'text-success' ?>">Due Rs. <?= number_format($left, 0) ?></div>
                    <?php if ($st === 'paid'): ?>
                        <span class="badge bg-success">Paid</span>
                    <?php elseif ($st === 'processing'): ?>
                        <span class="badge bg-warning text-dark">Processing</span>
                    <?php elseif ($st === 'waived'): ?>
                        <span class="badge bg-secondary">Waived</span>
                    <?php else: ?>
                        <span class="badge bg-danger">Unpaid</span>
                    <?php endif; ?>
                </div>
            </div>

            <?php if ($proof !== ''): ?>
                <div class="mb-3">
                    <div class="small text-muted mb-1">Your screenshot</div>
                    <a href="<?= esc(media_url($proof)) ?>" target="_blank">
                        <img src="<?= esc(media_url($proof)) ?>" alt="Payment screenshot" style="max-height:160px;max-width:100%;border-radius:12px;border:1px solid #e2e8f0;">
                    </a>
                </div>
            <?php endif; ?>

            <?php if ($st === 'processing'): ?>
                <p class="small text-warning mb-2">Waiting for Solqam to confirm this transfer. You can replace the screenshot if you sent the wrong photo.</p>
            <?php endif; ?>

            <?php if (\App\Services\Commission\SellerDueService::isOpen($st) && $left > 0): ?>
                <form action="<?= site_url('seller/dues/' . (int) $d['id'] . '/proof') ?>" method="POST" enctype="multipart/form-data" class="row g-2 align-items-end">
                    <?= csrf_field() ?>
                    <div class="col-md-5">
                        <label class="form-label small mb-1">Payment screenshot</label>
                        <input type="file" name="proof_image" class="form-control" accept="image/jpeg,image/png,image/webp" required>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label small mb-1">TID / note (optional)</label>
                        <input type="text" name="proof_note" class="form-control" maxlength="240" placeholder="JazzCash TID or bank ref">
                    </div>
                    <div class="col-md-3">
                        <button class="btn btn-primary rounded-pill w-100" type="submit">OK — submit</button>
                    </div>
                </form>
            <?php endif; ?>
        </div>
    <?php endforeach; ?>
<?php endif; ?>
<?= $this->endSection() ?>
