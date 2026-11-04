<?= $this->extend('layouts/seller') ?>
<?php $oosOnly = ! empty($oosOnly); ?>

<?= $this->section('content') ?>
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h4 class="fw-bold mb-1"><?= $oosOnly ? 'Out of stock' : 'My Store Products' ?></h4>
        <p class="text-secondary small mb-0"><?= $oosOnly
            ? 'Only products with 0 stock. Restock from Edit so you can deliver pending orders. Customers can still buy these.'
            : 'Out of stock listings stay on the shop so customers can still order. Restock to deliver those orders. Red rows need stock.' ?></p>
    </div>
    <div class="d-flex gap-2 flex-wrap">
        <?php if ($oosOnly): ?>
            <a href="<?= site_url('seller/products') ?>" class="btn btn-outline-secondary rounded-pill px-3">All products</a>
        <?php else: ?>
            <a href="<?= site_url('seller/inventory') ?>" class="btn btn-outline-primary rounded-pill px-3">Inventory</a>
            <a href="<?= site_url('seller/products/out-of-stock') ?>" class="btn btn-outline-danger rounded-pill px-3">Out of stock</a>
            <a href="<?= site_url('seller/products/csv-template') ?>" class="btn btn-outline-secondary rounded-pill px-3">CSV template</a>
        <?php endif; ?>
        <a href="<?= site_url('seller/products/create') ?>" class="btn btn-success rounded-pill px-4">
            <i class="bi bi-plus-lg me-1"></i> Add New Product
        </a>
    </div>
</div>

<?php if (! $oosOnly): ?>
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
<?php endif; ?>

<div class="card border-0 shadow-sm rounded-4 p-4 bg-white">
    <?php if (empty($products)): ?>
        <div class="text-center py-5">
            <i class="bi bi-box-seam fs-1 text-muted mb-2 d-block"></i>
            <h5 class="fw-bold"><?= $oosOnly ? 'No out of stock products' : 'No products in your catalog yet' ?></h5>
            <p class="text-secondary small mb-3"><?= $oosOnly ? 'Every listing currently has stock.' : 'Add your first item to begin receiving customer orders.' ?></p>
            <?php if ($oosOnly): ?>
            <a href="<?= site_url('seller/products') ?>" class="btn btn-outline-secondary btn-sm rounded-pill px-3">Back to products</a>
            <?php else: ?>
            <a href="<?= site_url('seller/products/create') ?>" class="btn btn-success btn-sm rounded-pill px-3">Add Product Now</a>
            <?php endif; ?>
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
                        <tr class="<?= (int) ($p['stock'] ?? 0) <= 0 ? 'table-danger' : '' ?>" data-product-url="<?= esc(site_url('product/' . $p['id'])) ?>" data-product-image="<?= esc(function_exists('media_url') ? media_url($p['primary_image'] ?? '') : ($p['primary_image'] ?? '')) ?>">
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
                            <td><?= hub_stock_cell($p['stock'] ?? 0) ?></td>
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
