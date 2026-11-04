<?= $this->extend('layouts/admin') ?>
<?php $oosOnly = ! empty($oosOnly); ?>

<?= $this->section('content') ?>
<div class="d-flex flex-wrap justify-content-between align-items-center mb-4 gap-3">
    <div>
        <h4 class="fw-bold mb-1" style="color: #0F172A;"><?= $oosOnly ? 'My out of stock products' : 'Admin First-Party Store Products' ?></h4>
        <p class="text-secondary small mb-0"><?= $oosOnly ? 'Your admin-store listings with 0 stock. Edit a product to restock.' : 'Products listed directly by the platform administration' ?></p>
    </div>
    <div class="d-flex gap-2 flex-wrap">
        <?php if ($oosOnly): ?>
            <a href="<?= site_url('admin/my-products') ?>" class="btn btn-outline-secondary rounded-pill px-4">All my products</a>
        <?php else: ?>
            <a href="<?= site_url('admin/inventory?mine=1') ?>" class="btn btn-outline-primary rounded-pill px-4">Inventory</a>
            <a href="<?= site_url('admin/my-products/out-of-stock') ?>" class="btn btn-outline-danger rounded-pill px-4">Out of stock</a>
        <?php endif; ?>
        <a href="<?= site_url('admin/my-orders') ?>" class="btn btn-outline-primary rounded-pill px-4">
            <i class="bi bi-bag-check me-1"></i> My Orders
        </a>
        <a href="<?= site_url('admin/my-products/create') ?>" class="btn btn-primary rounded-pill px-4 shadow-sm">
            <i class="bi bi-plus-circle me-1"></i> Add Direct Product
        </a>
    </div>
</div>

<?= view('admin/_price_adjust', [
    'adjustAction' => site_url('admin/my-products/adjust-prices'),
    'adjustLabel'  => 'YOUR admin products only',
]) ?>
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
                            <h6 class="fw-bold text-dark"><?= $oosOnly ? 'No out of stock products' : 'No direct admin products yet' ?></h6>
                            <p class="small mb-3"><?= $oosOnly ? 'Every admin listing currently has stock.' : 'You can list first-party products directly on the marketplace.' ?></p>
                            <?php if ($oosOnly): ?>
                            <a href="<?= site_url('admin/my-products') ?>" class="btn btn-sm btn-outline-secondary rounded-pill px-3">Back to my products</a>
                            <?php else: ?>
                            <a href="<?= site_url('admin/my-products/create') ?>" class="btn btn-sm btn-primary rounded-pill px-3">
                                <i class="bi bi-plus me-1"></i> Add Your First Product
                            </a>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($products as $p): ?>
                        <tr class="<?= (int) ($p['stock'] ?? 0) <= 0 ? 'table-danger' : '' ?>" data-product-url="<?= esc(site_url('product/' . $p['id'])) ?>" data-product-image="<?= esc(function_exists('media_url') ? media_url($p['primary_image'] ?? '') : ($p['primary_image'] ?? '')) ?>">
                            <td>
                                <div class="d-flex align-items-center gap-3">
                                    <img src="<?= esc($p['primary_image'] ?: base_url('assets/images/product-placeholder.svg')) ?>"
                                         class="rounded-3 object-fit-cover border" style="width: 48px; height: 48px;" alt="Product">
                                    <div>
                                        <a href="<?= site_url('product/' . $p['id']) ?>" target="_blank" class="fw-semibold text-dark text-decoration-none">
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
                                <?= hub_stock_cell($p['stock'] ?? 0) ?>
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
    <?= view('shared/_pager', ['pager' => $pager ?? null]) ?>
</div>
<?= $this->endSection() ?>
