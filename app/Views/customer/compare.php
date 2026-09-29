<?= $this->extend('layouts/main') ?>
<?= $this->section('content') ?>
<div class="container py-4">
    <h3 class="fw-bold mb-3">Compare products</h3>
    <?php if (empty($products)): ?>
        <p>No products in compare. Shop se add karein.</p>
    <?php else: ?>
        <div class="table-responsive">
            <table class="table">
                <thead><tr><th></th><?php foreach ($products as $p): ?><th><?= esc($p['name']) ?></th><?php endforeach; ?></tr></thead>
                <tbody>
                    <tr><th>Price</th><?php foreach ($products as $p): ?><td>Rs. <?= number_format($p['price'], 0) ?></td><?php endforeach; ?></tr>
                    <tr><th>Brand</th><?php foreach ($products as $p): ?><td><?= esc($p['brand'] ?? '—') ?></td><?php endforeach; ?></tr>
                    <tr><th>Stock</th><?php foreach ($products as $p): ?><td><?= esc($p['stock']) ?></td><?php endforeach; ?></tr>
                    <tr><th></th><?php foreach ($products as $p): ?><td><a href="<?= site_url('product/' . $p['id']) ?>">View</a></td><?php endforeach; ?></tr>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>
<?= $this->endSection() ?>
