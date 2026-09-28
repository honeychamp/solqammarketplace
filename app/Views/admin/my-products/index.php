<?= $this->extend('layouts/admin') ?>

<?= $this->section('content') ?>
<div class="d-flex flex-wrap justify-content-between align-items-center mb-4 gap-3">
    <div>
        <h4 class="fw-bold mb-1" style="color: #0F172A;">Admin First-Party Store Products</h4>
        <p class="text-secondary small mb-0">Products listed directly by the platform administration</p>
    </div>
    <div class="d-flex gap-2">
        <a href="<?= site_url('admin/my-orders') ?>" class="btn btn-outline-primary rounded-pill px-4">
            <i class="bi bi-bag-check me-1"></i> My Orders
        </a>
        <a href="<?= site_url('admin/my-products/create') ?>" class="btn btn-primary rounded-pill px-4 shadow-sm">
            <i class="bi bi-plus-circle me-1"></i> Add Direct Product
        </a>
    </div>
</div>

<?php if (session()->getFlashdata('success')): ?>
    <div class="alert alert-success alert-dismissible fade show rounded-3" role="alert">
        <i class="bi bi-check-circle-fill me-2"></i> <?= session()->getFlashdata('success') ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>

<?php if (session()->getFlashdata('error')): ?>
    <div class="alert alert-danger alert-dismissible fade show rounded-3" role="alert">
        <i class="bi bi-exclamation-triangle-fill me-2"></i> <?= session()->getFlashdata('error') ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>

<div class="card-custom p-4">
    <div class="table-responsive">
        <table class="table align-middle">
            <thead class="table-light small">
                <tr>
                    <th>Product</th>
                    <th>Category</th>
                    <th>SKU</th>
                    <th>Price</th>
                    <th>Stock</th>
                    <th>Status</th>
                    <th class="text-end">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($products)): ?>
                    <tr>
                        <td colspan="7" class="text-center py-5 text-muted">
                            <i class="bi bi-box-seam display-4 d-block mb-2 text-secondary opacity-50"></i>
                            <h6 class="fw-bold text-dark">No direct admin products yet</h6>
                            <p class="small mb-3">You can list first-party products directly on the marketplace.</p>
                            <a href="<?= site_url('admin/my-products/create') ?>" class="btn btn-sm btn-primary rounded-pill px-3">
                                <i class="bi bi-plus me-1"></i> Add Your First Product
                            </a>
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($products as $p): ?>
                        <tr>
                            <td>
                                <div class="d-flex align-items-center gap-3">
                                    <img src="<?= esc($p['primary_image'] ?: base_url('assets/images/product-placeholder.svg')) ?>"
                                         class="rounded-3 object-fit-cover border" style="width: 48px; height: 48px;" alt="Product">
                                    <div>
                                        <a href="<?= site_url('product/' . $p['slug']) ?>" target="_blank" class="fw-semibold text-dark text-decoration-none">
                                            <?= esc($p['name']) ?>
                                        </a>
                                        <div class="small text-muted"><span class="badge bg-danger-subtle text-danger border border-danger-subtle py-0">Admin Direct</span></div>
                                    </div>
                                </div>
                            </td>
                            <td>
                                <span class="badge bg-light text-dark border"><?= esc($p['category_name'] ?? 'Uncategorized') ?></span>
                            </td>
                            <td><code class="text-muted small"><?= esc($p['sku'] ?? 'N/A') ?></code></td>
                            <td class="fw-bold text-dark">Rs. <?= number_format($p['price'], 2) ?></td>
                            <td>
                                <span class="badge <?= $p['stock'] > 5 ? 'bg-success-subtle text-success' : ($p['stock'] > 0 ? 'bg-warning-subtle text-warning' : 'bg-danger-subtle text-danger') ?> rounded-pill px-2">
                                    <?= $p['stock'] ?> units
                                </span>
                            </td>
                            <td>
                                <span class="badge bg-<?= $p['status'] === 'active' ? 'success' : ($p['status'] === 'inactive' ? 'secondary' : 'dark') ?> rounded-pill">
                                    <?= ucfirst($p['status']) ?>
                                </span>
                            </td>
                            <td class="text-end">
                                <div class="btn-group">
                                    <a href="<?= site_url('admin/my-products/edit/' . $p['id']) ?>" class="btn btn-sm btn-outline-secondary rounded-start-pill px-3">
                                        <i class="bi bi-pencil me-1"></i> Edit
                                    </a>
                                    <a href="<?= site_url('admin/my-products/delete/' . $p['id']) ?>" class="btn btn-sm btn-outline-danger rounded-end-pill px-3" onclick="return confirm('Are you sure you want to delete this product?');">
                                        <i class="bi bi-trash"></i>
                                    </a>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
<?= $this->endSection() ?>
