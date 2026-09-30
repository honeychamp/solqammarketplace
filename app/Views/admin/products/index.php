<?= $this->extend('layouts/admin') ?>

<?= $this->section('content') ?>
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h4 class="fw-bold mb-1">Marketplace Products Overseer</h4>
        <p class="text-secondary small mb-0">Review all merchant listings, verify pricing, and moderate catalog items</p>
    </div>
</div>

<div class="card-custom p-4">
    <div class="table-responsive">
        <table class="table align-middle">
            <thead class="table-light small">
                <tr>
                    <th>Product</th>
                    <th>Vendor / Store</th>
                    <th>Category</th>
                    <th>Price</th>
                    <th>Stock</th>
                    <th>Status</th>
                    <th class="text-end">Moderation</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($products as $p): ?>
                    <tr>
                        <td>
                            <div class="fw-bold text-dark"><?= esc($p['name']) ?></div>
                            <small class="text-muted"><a href="<?= site_url('product/' . $p['id']) ?>" target="_blank">View Live <i class="bi bi-box-arrow-up-right"></i></a></small>
                        </td>
                        <td>
                            <span class="badge bg-light text-dark border"><i class="bi bi-shop me-1"></i> <?= esc($p['store_name'] ?? 'Vendor') ?></span>
                        </td>
                        <td><?= esc($p['category_name'] ?? 'General') ?></td>
                        <td class="fw-bold">Rs. <?= number_format($p['price'], 0) ?></td>
                        <td><?= $p['stock'] ?> units</td>
                        <td>
                            <span class="badge bg-<?= ($p['status'] === 'active') ? 'success' : 'secondary' ?>">
                                <?= ucfirst($p['status']) ?>
                            </span>
                        </td>
                        <td class="text-end">
                            <form action="<?= site_url('admin/products/' . $p['id'] . '/toggle') ?>" method="POST" class="d-inline">
                                <?= csrf_field() ?>
                                <?php if ($p['status'] === 'active'): ?>
                                    <button type="submit" class="btn btn-outline-danger btn-sm rounded-pill px-3" onclick="return confirm('Disable this product from public view?');">
                                        <i class="bi bi-eye-slash"></i> Disable
                                    </button>
                                <?php else: ?>
                                    <button type="submit" class="btn btn-outline-success btn-sm rounded-pill px-3">
                                        <i class="bi bi-eye"></i> Enable
                                    </button>
                                <?php endif; ?>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?= view('shared/_pager', ['pager' => $pager ?? null]) ?>
</div>
<?= $this->endSection() ?>
