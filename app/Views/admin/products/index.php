<?= $this->extend('layouts/admin') ?>
<?php $oosOnly = ! empty($oosOnly); ?>

<?= $this->section('content') ?>
<div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
    <div>
        <h4 class="fw-bold mb-1"><?= $oosOnly ? 'Out of stock products' : 'Marketplace Products Overseer' ?></h4>
        <p class="text-secondary small mb-0"><?= $oosOnly
            ? 'Marketplace listings with 0 stock. Sellers should restock so they can deliver. Shoppers can still buy these.'
            : 'Out of stock rows are red — sellers should restock so they can deliver customer orders. Shoppers can still buy OOS items.' ?></p>
    </div>
    <div>
        <?php if ($oosOnly): ?>
            <a href="<?= site_url('admin/products') ?>" class="btn btn-outline-secondary rounded-pill px-3">All products</a>
        <?php else: ?>
            <a href="<?= site_url('admin/inventory') ?>" class="btn btn-outline-primary rounded-pill px-3">Inventory</a>
            <a href="<?= site_url('admin/products/out-of-stock') ?>" class="btn btn-outline-danger rounded-pill px-3">Out of stock</a>
        <?php endif; ?>
    </div>
</div>

<?php if (! $oosOnly): ?>
<?= view('admin/_price_adjust', [
    'adjustAction' => site_url('admin/products/adjust-prices'),
    'adjustLabel'  => 'ALL marketplace products',
]) ?>
<?php endif; ?>
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
                <?php if (empty($products)): ?>
                    <tr>
                        <td colspan="7" class="text-center py-5 text-muted">
                            <?= $oosOnly ? 'No out of stock products right now.' : 'No products found.' ?>
                        </td>
                    </tr>
                <?php endif; ?>
                <?php foreach ($products as $p): ?>
                    <tr class="<?= (int) ($p['stock'] ?? 0) <= 0 ? 'table-danger' : '' ?>" data-product-url="<?= esc(site_url('product/' . $p['id'])) ?>" data-product-image="<?= esc(function_exists('media_url') ? media_url($p['primary_image'] ?? '') : ($p['primary_image'] ?? '')) ?>">
                        <td>
                            <div class="fw-bold text-dark"><?= esc($p['name']) ?></div>
                            <small class="text-muted"><a href="<?= site_url('product/' . $p['id']) ?>" target="_blank">View Live <i class="bi bi-box-arrow-up-right"></i></a></small>
                        </td>
                        <td>
                            <span class="badge bg-light text-dark border"><i class="bi bi-shop me-1"></i> <?= esc($p['store_name'] ?? 'Vendor') ?></span>
                        </td>
                        <td><?= esc($p['category_name'] ?? 'General') ?></td>
                        <td class="fw-bold">Rs. <?= number_format($p['price'], 0) ?></td>
                        <td><?= hub_stock_cell($p['stock'] ?? 0) ?></td>
                        <td>
                            <span class="badge bg-<?= ($p['status'] === 'active') ? 'success' : 'secondary' ?>">
                                <?= ucfirst($p['status']) ?>
                            </span>
                        </td>
                        <td class="text-end">
                            <a href="<?= site_url('admin/products/edit/' . $p['id']) ?>" class="btn btn-outline-primary btn-sm rounded-pill px-3 me-1">
                                <i class="bi bi-pencil"></i> Edit
                            </a>
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
                            <a href="<?= site_url('admin/products/delete/' . $p['id']) ?>" class="btn btn-outline-danger btn-sm rounded-pill px-3" onclick="return confirm('Delete this product?');">
                                <i class="bi bi-trash"></i>
                            </a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?= view('shared/_pager', ['pager' => $pager ?? null]) ?>
</div>
<?= $this->endSection() ?>
