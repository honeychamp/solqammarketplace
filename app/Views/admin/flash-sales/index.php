<?= $this->extend('layouts/admin') ?>
<?= $this->section('content') ?>
<h4 class="fw-bold mb-4">Flash Sales</h4>
<div class="card-custom p-4 mb-4">
    <form action="<?= site_url('admin/flash-sales/store') ?>" method="POST" class="row g-2">
        <?= csrf_field() ?>
        <div class="col-md-3"><input name="title" class="form-control" placeholder="Campaign title" value="Mega Flash Sale"></div>
        <div class="col-md-3"><input type="datetime-local" name="starts_at" class="form-control"></div>
        <div class="col-md-3"><input type="datetime-local" name="ends_at" class="form-control"></div>
        <div class="col-md-3"><button class="btn btn-primary w-100">Create sale</button></div>
    </form>
</div>
<?php if (!empty($active)): ?>
<div class="card-custom p-4 mb-4">
    <h6 class="fw-bold">Add product to: <?= esc($active['title']) ?></h6>
    <form action="<?= site_url('admin/flash-sales/item') ?>" method="POST" class="row g-2">
        <?= csrf_field() ?>
        <input type="hidden" name="flash_sale_id" value="<?= $active['id'] ?>">
        <div class="col-md-6">
            <select name="product_id" class="form-select" required>
                <?php foreach ($products as $p): ?>
                    <option value="<?= $p['id'] ?>"><?= esc($p['name']) ?> (Rs. <?= number_format($p['price'], 0) ?>)</option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-md-3"><input type="number" step="0.01" name="sale_price" class="form-control" placeholder="Sale price" required></div>
        <div class="col-md-3"><button class="btn btn-warning w-100">Add deal</button></div>
    </form>
    <ul class="mt-3 mb-0">
        <?php foreach ($items as $item): ?>
            <li><?= esc($item['name']) ?> — Rs. <?= number_format($item['sale_price'], 0) ?></li>
        <?php endforeach; ?>
    </ul>
</div>
<?php endif; ?>
<div class="card-custom p-4">
    <table class="table">
        <thead><tr><th>Title</th><th>Starts</th><th>Ends</th><th>Active</th></tr></thead>
        <tbody>
        <?php foreach ($sales as $s): ?>
            <tr>
                <td><?= esc($s['title']) ?></td>
                <td><?= esc($s['starts_at']) ?></td>
                <td><?= esc($s['ends_at']) ?></td>
                <td><?= $s['is_active'] ? 'Yes' : 'No' ?></td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</div>
<?= $this->endSection() ?>
