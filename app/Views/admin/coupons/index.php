<?= $this->extend('layouts/admin') ?>
<?= $this->section('content') ?>
<div class="d-flex justify-content-between align-items-center mb-4">
    <h4 class="fw-bold mb-0">Vouchers</h4>
</div>
<div class="card-custom p-4 mb-4">
    <form action="<?= site_url('admin/coupons/store') ?>" method="POST" class="row g-2 align-items-end">
        <?= csrf_field() ?>
        <div class="col-md-2"><label class="small fw-bold">Code</label><input name="code" class="form-control" required></div>
        <div class="col-md-2">
            <label class="small fw-bold">Type</label>
            <select name="type" class="form-select"><option value="percent">%</option><option value="fixed">Fixed PKR</option></select>
        </div>
        <div class="col-md-2"><label class="small fw-bold">Value</label><input type="number" step="0.01" name="value" class="form-control" required></div>
        <div class="col-md-2"><label class="small fw-bold">Min order</label><input type="number" step="0.01" name="min_order" class="form-control" value="0"></div>
        <div class="col-md-2"><label class="small fw-bold">Max uses</label><input type="number" name="max_uses" class="form-control"></div>
        <div class="col-md-2"><button class="btn btn-primary w-100">Create</button></div>
    </form>
</div>
<div class="card-custom p-4">
    <table class="table align-middle">
        <thead><tr><th>Code</th><th>Type</th><th>Value</th><th>Used</th><th>Active</th></tr></thead>
        <tbody>
        <?php foreach ($coupons as $c): ?>
            <tr>
                <td><code><?= esc($c['code']) ?></code></td>
                <td><?= esc($c['type']) ?></td>
                <td><?= esc($c['value']) ?></td>
                <td><?= (int) $c['used_count'] ?> / <?= $c['max_uses'] ?? '∞' ?></td>
                <td><?= $c['is_active'] ? 'Yes' : 'No' ?></td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</div>
<?= $this->endSection() ?>
