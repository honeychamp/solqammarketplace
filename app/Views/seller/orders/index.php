<?= $this->extend('layouts/seller') ?>

<?= $this->section('content') ?>
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h4 class="fw-bold mb-1">Incoming Orders</h4>
        <p class="text-secondary small mb-0">Review orders, verify buyer details, and update shipment status</p>
    </div>
</div>

<div class="card border-0 shadow-sm rounded-4 p-4 bg-white">
    <?php if (empty($items)): ?>
        <div class="text-center py-5 text-secondary">
            <i class="bi bi-receipt fs-1 text-muted mb-2 d-block"></i>
            <h5 class="fw-bold">No incoming orders yet</h5>
            <p class="small mb-0">When customers order your products, they will immediately appear in this list.</p>
        </div>
    <?php else: ?>
        <div class="table-responsive">
            <table class="table align-middle">
                <thead class="table-light small">
                    <tr>
                        <th>Order #</th>
                        <th>Ordered Item</th>
                        <th>Customer</th>
                        <th>Subtotal</th>
                        <th>Cashback</th>
                        <th>Commission</th>
                        <th>Order Status</th>
                        <th class="text-end">Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($items as $item): ?>
                        <tr>
                            <td><strong><?= esc($item['order_number']) ?></strong></td>
                            <td>
                                <div class="fw-semibold text-dark"><?= esc($item['product_name']) ?></div>
                                <small class="text-muted">Qty: <?= $item['quantity'] ?> &times; Rs. <?= number_format($item['price'], 0) ?></small>
                            </td>
                            <td>
                                <div><?= esc($item['customer_name']) ?></div>
                                <small class="text-muted"><i class="bi bi-telephone me-1"></i> <?= esc($item['customer_phone']) ?></small>
                                <?php if (!empty($item['customer_id'])): ?>
                                    <div><a class="small" href="<?= site_url('seller/customers/' . $item['customer_id']) ?>">360 profile</a></div>
                                <?php endif; ?>
                            </td>
                            <td class="fw-bold text-dark">Rs. <?= number_format($item['subtotal'], 2) ?></td>
                            <td class="text-success small">- Rs. <?= number_format(item_cashback($item), 2) ?></td>
                            <td class="text-danger small">- Rs. <?= number_format($item['commission_amount'], 2) ?></td>
                            <td>
                                <span class="badge bg-<?= match($item['order_status']) { 'delivered' => 'success', 'shipped' => 'primary', 'confirmed' => 'info', 'cancelled' => 'danger', default => 'warning text-dark' } ?> rounded-pill px-3 py-1">
                                    <i class="bi <?= match($item['order_status']) {
                                        'delivered' => 'bi-check2-all',
                                        'shipped' => 'bi-truck',
                                        'confirmed' => 'bi-hand-thumbs-up',
                                        'cancelled' => 'bi-x-circle',
                                        default => 'bi-clock-history'
                                    } ?> me-1"></i>
                                    <?= ucfirst($item['order_status']) ?>
                                </span>
                            </td>
                            <td class="text-end">
                                <a href="<?= site_url('seller/orders/' . $item['order_id']) ?>" class="btn btn-outline-dark btn-sm rounded-pill px-3">
                                    Manage Order
                                </a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>
<?= $this->endSection() ?>
