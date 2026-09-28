<?= $this->extend('layouts/admin') ?>

<?= $this->section('content') ?>
<?php $mineOnly = !empty($mineOnly); $listBase = $mineOnly ? 'admin/my-orders' : 'admin/orders'; ?>
<div class="d-flex flex-wrap justify-content-between align-items-center mb-4 gap-3">
    <div>
        <h4 class="fw-bold mb-1" style="color: #0F172A;"><?= $mineOnly ? 'My Orders' : 'Orders Live Monitor' ?></h4>
        <p class="text-secondary small mb-0"><?= $mineOnly
            ? 'Orders for your admin-store products. Confirm, ship, and deliver these packages. No commission on your items — cashback is the % you set on the product.'
            : 'Track all marketplace orders, buyer details, associated sellers, and fulfillment pipeline' ?></p>
    </div>
    <div class="btn-group">
        <a href="<?= site_url($listBase) ?>" class="btn btn-sm <?= empty($currentStatus) ? 'btn-dark' : 'btn-outline-secondary' ?>">All</a>
        <a href="<?= site_url($listBase . '?status=placed') ?>" class="btn btn-sm <?= $currentStatus === 'placed' ? 'btn-warning text-dark' : 'btn-outline-warning' ?>">Placed</a>
        <a href="<?= site_url($listBase . '?status=confirmed') ?>" class="btn btn-sm <?= $currentStatus === 'confirmed' ? 'btn-info text-white' : 'btn-outline-info' ?>">Confirmed</a>
        <a href="<?= site_url($listBase . '?status=shipped') ?>" class="btn btn-sm <?= $currentStatus === 'shipped' ? 'btn-primary' : 'btn-outline-primary' ?>">Shipped</a>
        <a href="<?= site_url($listBase . '?status=delivered') ?>" class="btn btn-sm <?= $currentStatus === 'delivered' ? 'btn-success' : 'btn-outline-success' ?>">Delivered</a>
        <a href="<?= site_url($listBase . '?status=cancelled') ?>" class="btn btn-sm <?= $currentStatus === 'cancelled' ? 'btn-danger' : 'btn-outline-danger' ?>">Cancelled</a>
    </div>
</div>

<div class="card-custom p-4">
    <div class="table-responsive">
        <table class="table align-middle">
            <thead class="table-light small">
                <tr>
                    <th>Order #</th>
                    <th>Customer (Buyer)</th>
                    <th>Seller / Store</th>
                    <th>Date Placed</th>
                    <th>Total Value</th>
                    <th>Cashback</th>
                    <th>Commission</th>
                    <th>Fulfillment Status</th>
                    <th class="text-end">Action</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($orders)): ?>
                    <tr>
                        <td colspan="9" class="text-center py-5 text-muted">
                            <i class="bi bi-inbox display-5 d-block mb-2 text-secondary opacity-50"></i>
                            No orders found for this status.
                        </td>
                    </tr>
                <?php else: ?>
                    <?php 
                    $adminId = (int) session()->get('user.id');
                    foreach ($orders as $o): 
                    ?>
                        <tr>
                            <td>
                                <strong class="text-dark"><?= esc($o['order_number']) ?></strong>
                            </td>
                            <td>
                                <div class="fw-semibold text-dark"><?= esc($o['customer_name'] ?? 'Guest Buyer') ?></div>
                                <small class="text-muted"><i class="bi bi-telephone me-1"></i><?= esc($o['customer_phone'] ?? 'N/A') ?></small>
                            </td>
                            <td>
                                <?php if (!empty($o['sellers'])): ?>
                                    <div class="d-flex flex-column gap-1">
                                        <?php foreach ($o['sellers'] as $s): ?>
                                            <?php if ((int)($s['seller_id'] ?? 0) === $adminId): ?>
                                                <span class="badge bg-danger-subtle text-danger border border-danger-subtle px-2 py-1 text-start" style="font-size: 11px;">
                                                    <i class="bi bi-shield-check me-1"></i> Admin Direct Store
                                                </span>
                                            <?php else: ?>
                                                <span class="badge bg-light text-dark border px-2 py-1 text-start text-truncate" style="max-width: 170px; font-size: 11px;">
                                                    <i class="bi bi-shop me-1 text-primary"></i> <?= esc($s['store_name'] ?: ($s['seller_name'] ?? 'Seller #' . $s['seller_id'])) ?>
                                                </span>
                                            <?php endif; ?>
                                        <?php endforeach; ?>
                                    </div>
                                <?php else: ?>
                                    <span class="text-muted small">Standard Vendor</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <div class="small text-dark"><?= date('d M Y', strtotime($o['created_at'])) ?></div>
                                <small class="text-muted"><?= date('h:i A', strtotime($o['created_at'])) ?></small>
                            </td>
                            <td class="fw-bold text-dark">Rs. <?= number_format($o['total_amount'], 2) ?></td>
                            <td class="text-success small">Rs. <?= number_format((float) ($o['cashback_amount'] ?? 0), 2) ?></td>
                            <td class="text-primary fw-bold small">Rs. <?= number_format($o['commission_amount'], 2) ?></td>
                            <td>
                                <span class="badge bg-<?= match($o['status']) { 
                                    'delivered' => 'success', 
                                    'shipped' => 'primary', 
                                    'confirmed' => 'info', 
                                    'cancelled' => 'danger',
                                    default => 'warning' 
                                } ?> rounded-pill px-3 py-1 mb-1">
                                    Order: <?= ucfirst($o['status']) ?>
                                </span>
                                <?php foreach ($o['sellers'] ?? [] as $s): ?>
                                    <div class="small text-muted"><?= esc($s['store_name'] ?: ($s['seller_name'] ?? 'Seller')) ?>: <?= esc(ucfirst((string) ($s['fulfillment_status'] ?? ''))) ?></div>
                                <?php endforeach; ?>
                            </td>
                            <td class="text-end">
                                <a href="<?= site_url('admin/orders/' . $o['id'] . ($mineOnly ? '?from=mine' : '')) ?>" class="btn btn-sm <?= $mineOnly ? 'btn-primary' : 'btn-outline-dark' ?> rounded-pill px-3">
                                    <?= $mineOnly ? 'Manage' : 'Details' ?> <i class="bi bi-arrow-right ms-1"></i>
                                </a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
<?= $this->endSection() ?>
