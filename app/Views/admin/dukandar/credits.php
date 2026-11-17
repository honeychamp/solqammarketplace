<?= $this->extend('layouts/admin') ?>
<?= $this->section('content') ?>
<div class="d-flex justify-content-between mb-3">
    <h4 class="fw-bold">Solqam Card credit queue</h4>
    <div class="d-flex gap-2 flex-wrap">
        <a class="btn btn-sm <?= ($status ?? '') === 'processing' ? 'btn-solqam' : 'btn-outline-secondary' ?>" href="<?= merchant_admin_url('credits') ?>?status=processing">Processing</a>
        <a class="btn btn-sm <?= ($status ?? '') === 'pending_payment' ? 'btn-solqam' : 'btn-outline-secondary' ?>" href="<?= merchant_admin_url('credits') ?>?status=pending_payment">Unpaid</a>
        <a class="btn btn-sm <?= ($status ?? '') === 'released' ? 'btn-solqam' : 'btn-outline-secondary' ?>" href="<?= merchant_admin_url('credits') ?>?status=released">Released</a>
        <a class="btn btn-sm <?= ($status ?? '') === 'rejected' ? 'btn-solqam' : 'btn-outline-secondary' ?>" href="<?= merchant_admin_url('credits') ?>?status=rejected">Rejected</a>
        <a class="btn btn-sm <?= ($status ?? '') === 'all' ? 'btn-solqam' : 'btn-outline-secondary' ?>" href="<?= merchant_admin_url('credits') ?>?status=all">All</a>
    </div>
</div>
<?php foreach (($payouts ?? []) as $pay): ?>
    <div class="card border-0 shadow-sm rounded-4 p-3 mb-3" style="border:1px solid #FDE68A !important;">
        <div class="d-flex flex-wrap justify-content-between gap-2 mb-2">
            <div>
                <div class="fw-bold"><?= esc($pay['shop_name'] ?? 'Merchant') ?> · screenshot in</div>
                <div class="small text-muted"><?= esc($pay['owner_name'] ?? '') ?> · <?= esc($pay['created_at'] ?? '') ?> · Rs. <?= number_format((float) ($pay['allocated'] ?? $pay['amount'] ?? 0), 0) ?> FIFO</div>
            </div>
            <div class="text-nowrap">
                <?php if (! empty($pay['proof_path'])): ?>
                    <a href="<?= base_url($pay['proof_path']) ?>" target="_blank" class="btn btn-sm btn-outline-dark">View screenshot</a>
                <?php endif; ?>
                <form class="d-inline" method="post" action="<?= merchant_admin_url('payouts/' . (int) $pay['id'] . '/approve') ?>"><?= csrf_field() ?><button class="btn btn-sm btn-success">Approve &amp; release</button></form>
                <form class="d-inline" method="post" action="<?= merchant_admin_url('payouts/' . (int) $pay['id'] . '/reject') ?>" onsubmit="var r=prompt('Reject reason'); if(!r) return false; this.querySelector('[name=reason]').value=r;"><?= csrf_field() ?><input type="hidden" name="reason" value="Proof not clear"><button class="btn btn-sm btn-outline-danger">Reject</button></form>
            </div>
        </div>
        <div class="table-responsive">
            <table class="table table-sm mb-0">
                <thead><tr><th>When</th><th>Customer</th><th>This payment</th></tr></thead>
                <tbody>
                <?php foreach (($pay['lines'] ?? []) as $ln): ?>
                    <tr>
                        <td class="small text-muted"><?= esc($ln['created_at'] ?? '') ?></td>
                        <td><?= esc($ln['customer_name'] ?? 'Customer') ?><div class="small font-monospace"><?= esc(merchant_scan_pan($ln)) ?></div></td>
                        <td class="fw-bold">Rs. <?= number_format((float) ($ln['amount'] ?? 0), 0) ?></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php if (! empty($pay['proof_note'])): ?><div class="small text-muted mt-2">Note <?= esc($pay['proof_note']) ?></div><?php endif; ?>
    </div>
<?php endforeach; ?>
<?php foreach (($settlements ?? []) as $pack): ?>
    <?php if ((float) ($pack['pending'] ?? 0) <= 0 && (float) ($pack['processing'] ?? 0) <= 0) {
        continue;
    } ?>
    <div class="card border-0 shadow-sm rounded-4 p-3 mb-3">
        <div class="d-flex flex-wrap justify-content-between align-items-start gap-2 mb-2">
            <div>
                <a class="fw-bold text-decoration-none" href="<?= merchant_admin_url((int) $pack['id']) ?>"><?= esc($pack['shop_name'] ?? 'Shop') ?></a>
                <div class="small text-muted"><?= esc($pack['owner_name'] ?? '') ?> · <?= esc($pack['city'] ?? '') ?> · <?= esc($pack['merchant_code'] ?? '') ?></div>
            </div>
            <div class="text-end">
                <div class="small text-muted">Merchant should send once</div>
                <div class="fs-5 fw-bold text-danger">Unpaid Rs. <?= number_format((float) ($pack['pending'] ?? 0), 0) ?></div>
                <div class="small text-warning">Screenshot in Rs. <?= number_format((float) ($pack['processing'] ?? 0), 0) ?></div>
            </div>
        </div>
        <?= view('partials/merchant_customer_credits', ['customers' => $pack['customers'] ?? [], 'compact' => true]) ?>
        <?php if ((float) ($pack['processing'] ?? 0) > 0): ?>
            <form class="mt-3" method="post" action="<?= merchant_admin_url((int) $pack['id'] . '/release-credits') ?>" onsubmit="return confirm('Release every processing credit for this merchant to customer wallets?');">
                <?= csrf_field() ?>
                <button class="btn btn-success rounded-pill">Release this shop payment · Rs. <?= number_format((float) $pack['processing'], 0) ?></button>
            </form>
        <?php endif; ?>
    </div>
<?php endforeach; ?>
<div class="card border-0 shadow-sm rounded-4 p-3">
    <div class="table-responsive">
        <table class="table align-middle">
            <thead><tr><th>Receipt</th><th>Merchant</th><th>Customer</th><th>Bill</th><th>5%</th><th>Proof</th><th></th></tr></thead>
            <tbody>
            <?php foreach (($rows ?? []) as $r): ?>
                <tr>
                    <td class="font-monospace"><?= esc(merchant_receipt($r['id'])) ?><div class="small"><?= merchant_status_badge((string) ($r['status'] ?? '')) ?></div></td>
                    <td><?= esc($r['shop_name'] ?? '') ?><div class="small text-muted"><?= esc($r['city'] ?? '') ?></div></td>
                    <td><?= esc($r['customer_name'] ?? '') ?><div class="small font-monospace"><?= esc(merchant_scan_pan($r)) ?></div></td>
                    <td>Rs. <?= number_format((float) $r['bill_amount'], 0) ?></td>
                    <td class="fw-bold">Rs. <?= number_format((float) $r['credit_amount'], 0) ?></td>
                    <td>
                        <?php if (! empty($r['proof_path'])): ?>
                            <a href="<?= base_url($r['proof_path']) ?>" target="_blank">
                                <img src="<?= base_url($r['proof_path']) ?>" alt="proof" style="height:48px;width:48px;object-fit:cover;" class="rounded">
                            </a>
                            <?php if (! empty($r['proof_note'])): ?><div class="small text-muted"><?= esc($r['proof_note']) ?></div><?php endif; ?>
                        <?php else: ?>—<?php endif; ?>
                    </td>
                    <td class="text-nowrap">
                        <?php if (($r['status'] ?? '') === 'processing'): ?>
                            <form class="d-inline" method="post" action="<?= merchant_admin_url('credits/' . (int) $r['id'] . '/release') ?>"><?= csrf_field() ?><button class="btn btn-sm btn-success">Release</button></form>
                            <form class="d-inline" method="post" action="<?= merchant_admin_url('credits/' . (int) $r['id'] . '/reject') ?>" onsubmit="var r=prompt('Reject reason'); if(!r) return false; this.querySelector('[name=reason]').value=r;">
                                <?= csrf_field() ?><input type="hidden" name="reason" value="Proof not clear"><button class="btn btn-sm btn-outline-danger">Reject</button>
                            </form>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
            <?php if (empty($rows)): ?>
                <tr><td colspan="7" class="text-muted">No rows.</td></tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
<?= $this->endSection() ?>
