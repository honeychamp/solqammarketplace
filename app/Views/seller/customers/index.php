<?= $this->extend('layouts/seller') ?>
<?= $this->section('content') ?>
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h4 class="fw-black mb-1">Your buyers</h4>
        <p class="text-muted small mb-0">Open a 360 profile: items bought from you, wallet used, commission, cashback.</p>
    </div>
</div>
<div class="command-panel p-0 overflow-hidden">
    <div class="table-responsive">
        <table class="table align-middle mb-0">
            <thead class="table-light small">
                <tr>
                    <th>Buyer</th>
                    <th>Phone</th>
                    <th>Lines</th>
                    <th>Goods</th>
                    <th>Cashback</th>
                    <th>Commission</th>
                    <th>Net</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
            <?php if (empty($buyers)): ?>
                <tr><td colspan="8" class="text-center text-muted py-5">No buyers yet.</td></tr>
            <?php else: ?>
                <?php foreach ($buyers as $b): ?>
                    <tr>
                        <td class="fw-semibold"><?= esc($b['name']) ?></td>
                        <td><?= esc($b['phone']) ?></td>
                        <td><?= (int) ($b['lines'] ?? $b['orders']) ?></td>
                        <td>Rs. <?= number_format($b['goods'], 0) ?></td>
                        <td class="text-success">Rs. <?= number_format($b['cashback'] ?? 0, 0) ?></td>
                        <td class="text-danger">Rs. <?= number_format($b['commission'], 0) ?></td>
                        <td class="text-success">Rs. <?= number_format($b['goods'] - $b['commission'] - ($b['cashback'] ?? 0), 0) ?></td>
                        <td class="text-end"><a class="btn btn-sm btn-solqam rounded-pill" href="<?= site_url('seller/customers/' . $b['id']) ?>">360 view</a></td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
<?= $this->endSection() ?>
