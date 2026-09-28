<?= $this->extend('layouts/admin') ?>
<?= $this->section('content') ?>
<h4 class="fw-bold mb-4">Seller Payouts</h4>
<div class="card-custom p-4">
    <table class="table align-middle">
        <thead><tr><th>Store</th><th>Order</th><th>Amount</th><th>Status</th><th></th></tr></thead>
        <tbody>
        <?php foreach ($payouts as $p): ?>
            <tr>
                <td><?= esc($p['store_name'] ?? $p['seller_name'] ?? 'Seller') ?></td>
                <td><?= esc($p['order_number'] ?? '-') ?></td>
                <td>Rs. <?= number_format($p['amount'], 2) ?></td>
                <td><span class="badge bg-<?= $p['status'] === 'paid' ? 'success' : 'warning text-dark' ?>"><?= esc($p['status']) ?></span></td>
                <td class="text-end">
                    <?php if ($p['status'] !== 'paid'): ?>
                        <form action="<?= site_url('admin/payouts/' . $p['id'] . '/paid') ?>" method="POST" class="d-inline">
                            <?= csrf_field() ?>
                            <button class="btn btn-sm btn-success">Mark paid</button>
                        </form>
                    <?php endif; ?>
                </td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</div>
<?= $this->endSection() ?>
