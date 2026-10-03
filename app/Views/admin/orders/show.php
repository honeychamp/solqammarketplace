<?= $this->extend('layouts/admin') ?>

<?= $this->section('content') ?>
<?php
$fromMine = !empty($fromMine);
$adminShipment = null;
foreach ($order['shipments'] ?? [] as $pkg) {
    if ((int) ($pkg['seller_id'] ?? 0) === (int) $adminId) {
        $adminShipment = $pkg;
        break;
    }
}
$adminPkgStatus = $adminShipment['status'] ?? 'placed';
?>
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h4 class="fw-bold mb-1">Order #<?= esc($order['order_number']) ?></h4>
        <p class="text-secondary small mb-0">Seller packages are view-only. You may fulfill only Solqam / admin products.</p>
    </div>
    <a href="<?= site_url($fromMine ? 'admin/my-orders' : 'admin/orders') ?>" class="btn btn-light btn-sm rounded-pill border px-3">
        <i class="bi bi-arrow-left me-1"></i> Back to Orders
    </a>
</div>

<div class="row g-4">
    <div class="col-lg-8">
        <?php if (!empty($hasAdminItems)): ?>
        <div class="card-custom p-4 mb-4">
            <h5 class="fw-bold mb-3 border-bottom pb-2">Your products — fulfillment</h5>
            <div class="mb-3">Package status: <span class="badge fs-6 bg-info text-dark"><?= esc(ucfirst((string) $adminPkgStatus)) ?></span></div>
            <form action="<?= site_url('admin/orders/' . $order['id'] . '/status') ?>" method="POST" class="d-flex align-items-center gap-2 flex-wrap">
                <?= csrf_field() ?>
                <?php if ($fromMine): ?><input type="hidden" name="mine" value="1"><?php endif; ?>
                <select name="status" class="form-select form-select-sm" style="max-width: 220px;">
                    <option value="confirmed" <?= $adminPkgStatus === 'confirmed' ? 'selected' : '' ?>>Confirmed</option>
                    <option value="shipped" <?= $adminPkgStatus === 'shipped' ? 'selected' : '' ?>>Shipped</option>
                    <option value="delivered" <?= $adminPkgStatus === 'delivered' ? 'selected' : '' ?>>Delivered</option>
                    <option value="undelivered" <?= $adminPkgStatus === 'undelivered' ? 'selected' : '' ?>>Not received (charge courier fee)</option>
                    <?php if (empty(array_filter($order['items'], static fn ($i) => empty($i['is_admin_item'])))): ?>
                    <option value="cancelled" <?= $order['status'] === 'cancelled' ? 'selected' : '' ?>>Cancelled</option>
                    <?php endif; ?>
                </select>
                <button type="submit" class="btn btn-primary btn-sm rounded-pill px-4">Update my package</button>
            </form>
        </div>
        <?php else: ?>
        <div class="alert alert-secondary">This order has only seller products. You can view details, not change fulfillment.</div>
        <?php endif; ?>

        <div class="card-custom p-4">
            <h5 class="fw-bold mb-3 border-bottom pb-2">Items</h5>
            <div class="table-responsive">
                <table class="table align-middle">
                    <thead class="table-light small">
                        <tr>
                            <th>Product</th>
                            <th>Sold by</th>
                            <th>Qty</th>
                            <th>Subtotal</th>
                            <th>Cashback</th>
                            <th>Commission</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($order['items'] as $item): ?>
                            <tr>
                                <td class="fw-semibold"><?= esc($item['product_name']) ?></td>
                                <td>
                                    <?php if (!empty($item['is_admin_item'])): ?>
                                        <span class="badge bg-danger-subtle text-danger">Admin store</span>
                                    <?php else: ?>
                                        <span class="badge bg-light text-dark border"><?= esc($item['store_name'] ?? 'Seller') ?></span>
                                    <?php endif; ?>
                                </td>
                                <td><?= (int) $item['quantity'] ?></td>
                                <td>Rs. <?= number_format($item['subtotal'], 2) ?></td>
                                <td class="text-success">Rs. <?= number_format(item_cashback($item), 2) ?></td>
                                <td>
                                    <?php if (!empty($item['is_admin_item'])): ?>
                                        <span class="text-muted">Rs. 0.00 <small>(no commission)</small></span>
                                    <?php else: ?>
                                        Rs. <?= number_format($item['commission_amount'], 2) ?>
                                    <?php endif; ?>
                                </td>
                                <td><span class="badge text-bg-light"><?= esc(ucfirst((string) ($item['fulfillment_status'] ?? $order['status']))) ?></span></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="col-lg-4">
        <div class="card-custom p-4 mb-4">
            <h5 class="fw-bold mb-3 border-bottom pb-2">Money</h5>
            <div class="d-flex justify-content-between mb-2"><span class="text-secondary">Gross</span><span>Rs. <?= number_format($order['total_amount'], 2) ?></span></div>
            <div class="d-flex justify-content-between mb-2"><span class="text-secondary">Payable</span><span>Rs. <?= number_format($order['final_payable'], 2) ?></span></div>
            <div class="d-flex justify-content-between mb-2 text-success"><span>Buyer cashback</span><span>Rs. <?= number_format((float) ($order['cashback_amount'] ?? 0), 2) ?></span></div>
            <div class="d-flex justify-content-between mb-3 text-primary"><span>Commission (category rates<?= isset($order['commission_rate']) ? ', avg ' . esc($order['commission_rate']) . '%' : '' ?>)</span><span>Rs. <?= number_format($order['commission_amount'], 2) ?></span></div>
            <div class="small">
                <div><strong>Payment:</strong> <?= strtoupper($order['payment_method'] ?? 'COD') ?></div>
                <div><strong>Status:</strong> <?= strtoupper($order['payment_status'] ?? 'PENDING') ?></div>
                <div class="text-muted mt-2">Seller items: commission from the product’s category. Admin SKUs: 0%. Buyer cashback is the % saved on each product. Prepaid now; COD / Pay later after delivery.</div>
            </div>
        </div>
        <?php if (!empty($order['pay_later'])): ?>
        <div class="card-custom p-4 mb-4">
            <h6 class="fw-bold mb-3">Pay later documents</h6>
            <div class="small mb-2"><strong><?= esc($order['pay_later']['full_name']) ?></strong></div>
            <div class="small">CNIC: <?= esc($order['pay_later']['cnic_number']) ?></div>
            <div class="small">Phone: <?= esc($order['pay_later']['phone'] ?? '') ?></div>
            <div class="small mb-2"><?= esc($order['pay_later']['address_text'] ?? '') ?></div>
            <div class="d-flex flex-column gap-1 small">
                <a href="<?= esc($order['pay_later']['cnic_front_path']) ?>" target="_blank">CNIC front</a>
                <?php if (!empty($order['pay_later']['cnic_back_path'])): ?>
                    <a href="<?= esc($order['pay_later']['cnic_back_path']) ?>" target="_blank">CNIC back</a>
                <?php endif; ?>
                <a href="<?= esc($order['pay_later']['utility_bill_path']) ?>" target="_blank">Utility bill</a>
            </div>
        </div>
        <?php endif; ?>
        <div class="card-custom p-4">
            <h6 class="fw-bold mb-3">Customer</h6>
            <div class="small">
                <strong><?= esc($order['customer_name']) ?></strong>
                <div><?= esc($order['customer_phone']) ?></div>
                <a class="btn btn-sm btn-outline-primary rounded-pill mt-2" href="<?= site_url('admin/customers/' . $order['user_id']) ?>">Customer 360</a>
                <hr>
                <div><?= esc($order['recipient_name']) ?></div>
                <div><?= esc($order['street_address']) ?></div>
                <div><?= esc($order['city']) ?>, <?= esc($order['province']) ?></div>
            </div>
        </div>
    </div>
</div>
<?= $this->endSection() ?>
