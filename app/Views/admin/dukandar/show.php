<?= $this->extend('layouts/admin') ?>
<?= $this->section('content') ?>
<?php $s = $shop ?? []; ?>
<div class="d-flex justify-content-between align-items-start mb-3">
    <div>
        <a href="<?= merchant_admin_url() ?>" class="small">← Merchants</a>
        <h4 class="fw-bold mb-1"><?= esc($s['shop_name'] ?? 'Merchant') ?></h4>
        <div class="small text-muted font-monospace"><?= esc($s['merchant_code'] ?? '') ?> · <?= merchant_status_badge((string) ($s['approval_status'] ?? '')) ?></div>
    </div>
    <div class="text-nowrap">
        <?php if (($s['approval_status'] ?? '') !== 'approved'): ?>
            <form class="d-inline" method="post" action="<?= merchant_admin_url((int) $s['id'] . '/approve') ?>"><?= csrf_field() ?><button class="btn btn-sm btn-success">Approve</button></form>
        <?php endif; ?>
        <?php if (($s['approval_status'] ?? '') === 'pending'): ?>
            <form class="d-inline" method="post" action="<?= merchant_admin_url((int) $s['id'] . '/reject') ?>" onsubmit="var r=prompt('Reject reason'); if(!r) return false; this.querySelector('[name=reason]').value=r;">
                <?= csrf_field() ?><input type="hidden" name="reason" value="Not approved"><button class="btn btn-sm btn-outline-danger">Reject</button>
            </form>
        <?php endif; ?>
    </div>
</div>
<div class="row g-3 mb-4">
    <div class="col-lg-7">
        <div class="card border-0 shadow-sm rounded-4 p-4">
            <div class="row g-3">
                <div class="col-md-6"><div class="small text-muted">Owner</div><div class="fw-semibold"><?= esc($s['owner_name'] ?? $s['user_name'] ?? '') ?></div></div>
                <div class="col-md-6"><div class="small text-muted">Phone</div><div><?= esc($s['phone'] ?? $s['owner_phone'] ?? '') ?></div></div>
                <div class="col-md-6"><div class="small text-muted">Email</div><div><?= esc($s['email'] ?? $s['owner_email'] ?? '') ?></div></div>
                <div class="col-md-6"><div class="small text-muted">WhatsApp</div><div><?= esc($s['whatsapp'] ?? '—') ?></div></div>
                <div class="col-md-6"><div class="small text-muted">Category</div><div><?= esc($s['category_code'] ?? '') ?> <?= esc($s['category'] ?? '') ?></div></div>
                <?php if (! empty($card['pan'])): ?>
                <div class="col-12">
                    <div class="small text-muted">Merchant Solqam Card</div>
                    <div class="font-monospace fw-bold"><?= esc(\App\Services\Card\SolqamCardNumber::display((string) $card['pan'])) ?></div>
                </div>
                <?php endif; ?>
                <div class="col-md-6"><div class="small text-muted">Hours</div><div><?= esc($s['opening_hours'] ?? '—') ?></div></div>
                <div class="col-md-6"><div class="small text-muted">Shop owner type</div><div><?= esc(merchant_occupancy_label($s['occupancy_type'] ?? '')) ?></div></div>
                <div class="col-md-6"><div class="small text-muted">Business year</div><div><?= esc($s['business_year'] ?? '—') ?></div></div>
                <div class="col-12"><div class="small text-muted">Address</div><div><?= esc($s['address'] ?? '') ?>, <?= esc($s['city'] ?? '') ?></div></div>
                <?php if (! empty($s['rejection_reason'])): ?>
                    <div class="col-12 text-danger small">Rejected: <?= esc($s['rejection_reason']) ?></div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    <div class="col-lg-5">
        <div class="row g-2">
            <?php if (! empty($s['shop_image'])): ?>
                <div class="col-6">
                    <div class="small text-muted mb-1">Outside</div>
                    <img src="<?= base_url($s['shop_image']) ?>" alt="Outside" class="rounded-4 shadow-sm w-100" style="max-height:240px;object-fit:cover;">
                </div>
            <?php endif; ?>
            <?php if (! empty($s['shop_image_in'])): ?>
                <div class="col-6">
                    <div class="small text-muted mb-1">Inside</div>
                    <img src="<?= base_url($s['shop_image_in']) ?>" alt="Inside" class="rounded-4 shadow-sm w-100" style="max-height:240px;object-fit:cover;">
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>
<?php $tot = $totals ?? []; ?>
<div class="row g-3 mb-4">
    <div class="col-6 col-lg-3"><div class="card border-0 shadow-sm rounded-4 p-3"><div class="small text-muted">Credits created</div><div class="fs-4 fw-bold">Rs. <?= number_format((float) ($tot['credits'] ?? 0), 0) ?></div></div></div>
    <div class="col-6 col-lg-3"><div class="card border-0 shadow-sm rounded-4 p-3"><div class="small text-muted">Unpaid by shop</div><div class="fs-4 fw-bold text-danger">Rs. <?= number_format((float) ($tot['pending'] ?? 0), 0) ?></div></div></div>
    <div class="col-6 col-lg-3"><div class="card border-0 shadow-sm rounded-4 p-3"><div class="small text-muted">Screenshot in</div><div class="fs-4 fw-bold text-warning">Rs. <?= number_format((float) ($tot['processing'] ?? 0), 0) ?></div></div></div>
    <div class="col-6 col-lg-3"><div class="card border-0 shadow-sm rounded-4 p-3"><div class="small text-muted">Released</div><div class="fs-4 fw-bold text-success">Rs. <?= number_format((float) ($tot['released'] ?? 0), 0) ?></div></div></div>
</div>
<div class="card border-0 shadow-sm rounded-4 p-3 mb-4">
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-2">
        <h6 class="fw-bold mb-0">Customer credits from this shop</h6>
        <?php if ((float) ($tot['processing'] ?? 0) > 0): ?>
            <form method="post" action="<?= merchant_admin_url((int) $s['id'] . '/release-credits') ?>" onsubmit="return confirm('Release all processing credits from this merchant payment to customer wallets?');">
                <?= csrf_field() ?>
                <button class="btn btn-sm btn-success rounded-pill">Release all processing · Rs. <?= number_format((float) $tot['processing'], 0) ?></button>
            </form>
        <?php endif; ?>
    </div>
    <?= view('partials/merchant_customer_credits', ['customers' => $customers ?? [], 'compact' => true]) ?>
</div>
<div class="card border-0 shadow-sm rounded-4 p-3">
    <h6 class="fw-bold">Recent card sales</h6>
    <div class="table-responsive">
        <table class="table table-sm align-middle">
            <thead><tr><th>Receipt</th><th>Customer</th><th>Card</th><th>Bill</th><th>5%</th><th>Status</th><th>When</th></tr></thead>
            <tbody>
            <?php foreach (($scans ?? []) as $row): ?>
                <tr>
                    <td class="font-monospace"><?= esc(merchant_receipt($row['id'])) ?></td>
                    <td><?= esc($row['customer_name'] ?? 'Customer') ?><?php if (! empty($row['customer_phone'])): ?><div class="small text-muted"><?= esc($row['customer_phone']) ?></div><?php endif; ?></td>
                    <td class="font-monospace"><?= esc(merchant_scan_pan($row)) ?></td>
                    <td>Rs. <?= number_format((float) $row['bill_amount'], 0) ?></td>
                    <td>Rs. <?= number_format((float) $row['credit_amount'], 0) ?></td>
                    <td><?= merchant_status_badge((string) $row['status']) ?></td>
                    <td class="small text-muted"><?= esc($row['created_at'] ?? '') ?></td>
                </tr>
            <?php endforeach; ?>
            <?php if (empty($scans)): ?>
                <tr><td colspan="7" class="text-muted">No sales yet.</td></tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
<?= $this->endSection() ?>
