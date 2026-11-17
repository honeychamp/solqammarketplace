<?= $this->extend('layouts/admin') ?>
<?= $this->section('content') ?>
<div class="d-flex justify-content-between align-items-center mb-3">
    <h4 class="fw-bold mb-0">Merchants</h4>
    <a class="btn btn-outline-primary btn-sm rounded-pill" href="<?= merchant_admin_url('credits') ?>">Card credit queue</a>
</div>
<div class="mb-3 d-flex gap-2 flex-wrap">
    <a class="btn btn-sm <?= ($currentStatus ?? '') === '' ? 'btn-solqam' : 'btn-outline-secondary' ?>" href="<?= merchant_admin_url() ?>">All</a>
    <a class="btn btn-sm <?= ($currentStatus ?? '') === 'pending' ? 'btn-solqam' : 'btn-outline-secondary' ?>" href="<?= merchant_admin_url() . '?status=pending' ?>">Pending</a>
    <a class="btn btn-sm <?= ($currentStatus ?? '') === 'approved' ? 'btn-solqam' : 'btn-outline-secondary' ?>" href="<?= merchant_admin_url() . '?status=approved' ?>">Approved</a>
    <a class="btn btn-sm <?= ($currentStatus ?? '') === 'rejected' ? 'btn-solqam' : 'btn-outline-secondary' ?>" href="<?= merchant_admin_url() . '?status=rejected' ?>">Rejected</a>
</div>
<div class="card border-0 shadow-sm rounded-4 p-3">
    <div class="table-responsive">
        <table class="table align-middle">
            <thead><tr><th>Shop</th><th>Owner</th><th>City</th><th>Category</th><th>Unpaid 5%</th><th>Status</th><th></th></tr></thead>
            <tbody>
            <?php foreach (($shops ?? []) as $s): ?>
                <tr>
                    <td class="fw-bold">
                        <a href="<?= merchant_admin_url((int) $s['id']) ?>" class="text-decoration-none"><?= esc($s['shop_name']) ?></a>
                        <?php if (! empty($s['merchant_code'])): ?>
                            <div class="small font-monospace text-muted"><?= esc($s['merchant_code']) ?></div>
                        <?php endif; ?>
                        <?php if (! empty($s['shop_image']) || ! empty($s['shop_image_in'])): ?>
                            <div class="d-flex gap-1 mt-1">
                                <?php if (! empty($s['shop_image'])): ?>
                                    <img src="<?= base_url($s['shop_image']) ?>" alt="Out" class="rounded" style="height:40px;width:40px;object-fit:cover;">
                                <?php endif; ?>
                                <?php if (! empty($s['shop_image_in'])): ?>
                                    <img src="<?= base_url($s['shop_image_in']) ?>" alt="In" class="rounded" style="height:40px;width:40px;object-fit:cover;">
                                <?php endif; ?>
                            </div>
                        <?php endif; ?>
                    </td>
                    <td><?= esc($s['owner_name']) ?><div class="small text-muted"><?= esc($s['owner_phone'] ?? $s['phone'] ?? '') ?></div></td>
                    <td><?= esc($s['city']) ?><div class="small text-muted"><?= esc($s['address'] ?? '') ?></div></td>
                    <td><?= esc($s['category']) ?></td>
                    <td>
                        <div class="fw-bold <?= (float) ($s['credits_pending'] ?? 0) > 0 ? 'text-danger' : '' ?>">Rs. <?= number_format((float) ($s['credits_pending'] ?? 0), 0) ?></div>
                        <?php if ((float) ($s['credits_processing'] ?? 0) > 0): ?>
                            <div class="small text-warning">Processing Rs. <?= number_format((float) $s['credits_processing'], 0) ?></div>
                        <?php endif; ?>
                    </td>
                    <td><?= merchant_status_badge((string) ($s['approval_status'] ?? '')) ?></td>
                    <td class="text-nowrap">
                        <?php if (($s['approval_status'] ?? '') !== 'approved'): ?>
                            <form class="d-inline" method="post" action="<?= merchant_admin_url((int) $s['id'] . '/approve') ?>"><?= csrf_field() ?><button class="btn btn-sm btn-success">Approve</button></form>
                        <?php endif; ?>
                        <?php if (($s['approval_status'] ?? '') === 'pending'): ?>
                            <form class="d-inline" method="post" action="<?= merchant_admin_url((int) $s['id'] . '/reject') ?>" onsubmit="var r=prompt('Reject reason'); if(!r) return false; this.querySelector('[name=reason]').value=r;">
                                <?= csrf_field() ?><input type="hidden" name="reason" value="Not approved"><button class="btn btn-sm btn-outline-danger">Reject</button>
                            </form>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
            <?php if (empty($shops)): ?>
                <tr><td colspan="7" class="text-muted">No merchants yet.</td></tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
    <?= $pager ? $pager->links() : '' ?>
</div>
<?= $this->endSection() ?>
