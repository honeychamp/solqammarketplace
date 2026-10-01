<?= $this->extend('layouts/admin') ?>

<?= $this->section('content') ?>
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h4 class="fw-bold mb-1">Category-wise seller commission</h4>
        <p class="text-secondary small mb-0">Each product uses the rate of its category. Admin-store products are always 0%.</p>
    </div>
    <a href="<?= site_url('admin/categories') ?>" class="btn btn-outline-primary rounded-pill">Edit images &amp; categories</a>
</div>

<div class="row g-4">
    <div class="col-lg-8">
        <div class="card-custom p-4">
            <h5 class="fw-bold mb-3 border-bottom pb-2">Rates by category</h5>
            <div class="table-responsive">
                <table class="table align-middle">
                    <thead class="table-light small">
                        <tr>
                            <th>Category</th>
                            <th>Set %</th>
                            <th>Applies</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($categories as $cat): ?>
                            <tr>
                                <td class="fw-semibold"><?= esc($cat['path_label'] ?? $cat['name']) ?></td>
                                <td>
                                    <form action="<?= site_url('admin/commissions') ?>" method="POST" class="d-flex gap-2">
                                        <?= csrf_field() ?>
                                        <input type="hidden" name="category_id" value="<?= (int) $cat['id'] ?>">
                                        <div class="input-group input-group-sm" style="max-width:160px;">
                                            <input type="number" step="0.01" min="0" max="50" name="commission_percent" class="form-control" value="<?= esc($cat['commission_percent'] ?? '') ?>" placeholder="Inherit">
                                            <span class="input-group-text">%</span>
                                        </div>
                                        <button class="btn btn-sm btn-primary">Save</button>
                                    </form>
                                </td>
                                <td><?= number_format((float) ($cat['effective_commission'] ?? 10), 1) ?>%</td>
                                <td class="small text-muted">L<?= (int) ($cat['depth'] ?? 1) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <div class="col-lg-4">
        <div class="card-custom p-4 mb-4">
            <h5 class="fw-bold mb-3">Fallback (no category)</h5>
            <form action="<?= site_url('admin/commissions') ?>" method="POST">
                <?= csrf_field() ?>
                <div class="input-group mb-2">
                    <input type="number" step="0.1" name="percentage" class="form-control fw-bold" value="<?= esc($rule['percentage'] ?? 10) ?>" min="0" max="50" required>
                    <span class="input-group-text">%</span>
                </div>
                <p class="small text-secondary">Used only when a product has no category, or a child category inherits and the parent has no rate.</p>
                <button type="submit" class="btn btn-success rounded-pill px-4 fw-bold">Save fallback</button>
            </form>
        </div>
        <div class="card-custom p-4 bg-light">
            <h5 class="fw-bold mb-3"><i class="bi bi-calculator text-primary me-2"></i> How it is cut</h5>
            <p class="small text-secondary mb-0">
                Seller item: (line subtotal × that product’s category %) / 100. Your own catalog: Rs. 0. Buyer cashback is separate, set on the product.
            </p>
        </div>
    </div>
</div>
<?= $this->endSection() ?>
