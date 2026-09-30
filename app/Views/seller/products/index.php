<?= $this->extend('layouts/seller') ?>

<?= $this->section('content') ?>
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h4 class="fw-bold mb-1">My Store Products</h4>
        <p class="text-secondary small mb-0">Manage pricing, inventory stock, and product visibility</p>
    </div>
    <div class="d-flex gap-2 flex-wrap">
        <a href="<?= site_url('seller/products/csv-template') ?>" class="btn btn-outline-secondary rounded-pill px-3">CSV template</a>
        <a href="<?= site_url('seller/products/create') ?>" class="btn btn-success rounded-pill px-4">
            <i class="bi bi-plus-lg me-1"></i> Add New Product
        </a>
    </div>
</div>

<div class="card border-0 shadow-sm rounded-4 p-4 bg-white mb-3">
    <form action="<?= site_url('seller/products/import') ?>" method="POST" enctype="multipart/form-data" class="row g-2 align-items-end">
        <?= csrf_field() ?>
        <div class="col-md-8">
            <label class="form-label small">Bulk import CSV</label>
            <input type="file" name="csv" class="form-control" accept=".csv" required>
        </div>
        <div class="col-md-4">
            <button class="btn btn-outline-primary w-100">Import</button>
        </div>
    </form>
</div>

<div class="card border-0 shadow-sm rounded-4 p-4 bg-white">
    <?php if (empty($products)): ?>
        <div class="text-center py-5">
            <i class="bi bi-box-seam fs-1 text-muted mb-2 d-block"></i>
            <h5 class="fw-bold">No products in your catalog yet</h5>
            <p class="text-secondary small mb-3">Add your first item to begin receiving customer orders.</p>
            <a href="<?= site_url('seller/products/create') ?>" class="btn btn-success btn-sm rounded-pill px-3">Add Product Now</a>
        </div>
    <?php else: ?>
        <div class="table-responsive">
            <table class="table align-middle">
                <thead class="table-light small">
                    <tr>
                        <th>Product</th>
                        <th>Category</th>
                        <th>Price</th>
                        <th>Stock</th>
                        <th>SKU</th>
                        <th>Status</th>
                        <th class="text-end">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($products as $p): ?>
                        <tr>
                            <td>
                                <div class="d-flex align-items-center">
                                    <img src="<?= esc($p['primary_image'] ?: base_url('assets/images/product-placeholder.svg')) ?>" class="rounded-3 me-3" style="width: 50px; height: 50px; object-fit: cover;">
                                    <div>
                                        <h6 class="mb-0 fw-semibold text-dark"><?= esc($p['name']) ?></h6>
                                        <small class="text-muted"><a href="<?= site_url('product/' . $p['id']) ?>" target="_blank" class="text-secondary">View in Store <i class="bi bi-box-arrow-up-right"></i></a></small>
                                    </div>
                                </div>
                            </td>
                            <td><span class="badge bg-light text-dark border"><?= esc($p['category_name'] ?? 'General') ?></span></td>
                            <td class="fw-bold text-dark">Rs. <?= number_format($p['price'], 0) ?></td>
                            <td>
                                <?php if ($p['stock'] <= 5): ?>
                                    <span class="badge bg-danger"><?= $p['stock'] ?> (Low)</span>
                                <?php else: ?>
                                    <span class="badge bg-success-subtle text-success border border-success-subtle"><?= $p['stock'] ?> units</span>
                                <?php endif; ?>
                            </td>
                            <td><code><?= esc($p['sku'] ?? 'N/A') ?></code></td>
                            <td>
                                <span class="badge bg-<?= ($p['status'] === 'active') ? 'success' : 'secondary' ?>">
                                    <?= ucfirst($p['status']) ?>
                                </span>
                            </td>
                            <td class="text-end">
                                <a href="<?= site_url('seller/products/edit/' . $p['id']) ?>" class="btn btn-outline-primary btn-sm rounded-pill px-3 me-1">
                                    <i class="bi bi-pencil"></i> Edit
                                </a>
                                <a href="<?= site_url('seller/products/delete/' . $p['id']) ?>" class="btn btn-outline-danger btn-sm rounded-pill" onclick="return confirm('Delete this product?');">
                                    <i class="bi bi-trash"></i>
                                </a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?= view('shared/_pager', ['pager' => $pager ?? null]) ?>
    <?php endif; ?>
</div>
<?= $this->endSection() ?>
