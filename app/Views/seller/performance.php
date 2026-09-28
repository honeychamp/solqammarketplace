<?= $this->extend('layouts/seller') ?>

<?= $this->section('content') ?>
<div class="card border-0 shadow-sm rounded-4 p-4 bg-white">
    <h5 class="fw-bold mb-3 border-bottom pb-2">Top Selling Products</h5>
    <p class="text-secondary small mb-4">Breakdown of sales and units ordered for your store catalog</p>

    <?php if (empty($productSales)): ?>
        <p class="text-secondary small py-4 text-center">No sales recorded yet. Once orders are fulfilled, your top product rankings will appear here.</p>
    <?php else: ?>
        <div class="table-responsive">
            <table class="table align-middle">
                <thead class="table-light small">
                    <tr>
                        <th>Product Title</th>
                        <th>Units Sold</th>
                        <th>Gross Revenue</th>
                        <th>Avg Price / Unit</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($productSales as $name => $data): ?>
                        <tr>
                            <td class="fw-semibold"><?= esc($name) ?></td>
                            <td><span class="badge bg-primary rounded-pill px-3 py-1"><?= $data['units'] ?> Sold</span></td>
                            <td class="fw-bold text-success fs-6">Rs. <?= number_format($data['revenue'], 2) ?></td>
                            <td class="text-muted">Rs. <?= number_format($data['revenue'] / max(1, $data['units']), 2) ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>
<?= $this->endSection() ?>
