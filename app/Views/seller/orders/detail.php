<?= $this->extend('layouts/seller') ?>

<?= $this->section('content') ?>
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h4 class="fw-bold mb-1">Order #<?= esc($order['order_number']) ?></h4>
        <p class="text-secondary small mb-0">Customer order placed on <?= date('d M Y, h:i A', strtotime($order['created_at'])) ?></p>
    </div>
    <a href="<?= site_url('seller/orders') ?>" class="btn btn-light btn-sm rounded-pill border px-3">
        <i class="bi bi-arrow-left me-1"></i> Back to Orders
    </a>
    <a href="<?= site_url('seller/orders/' . $order['id'] . '/slip') ?>" class="btn btn-outline-dark btn-sm rounded-pill px-3">Packing slip</a>
</div>

<div class="row g-4">
    <!-- Items & Status Transition -->
    <div class="col-lg-8">
        <!-- Status Action Box -->
        <div class="card border-0 shadow-sm rounded-4 p-4 bg-white mb-4">
            <?php
                $pkgStatus = $shipment['status'] ?? $order['status'];
                $stages = ['placed' => 1, 'confirmed' => 2, 'shipped' => 3, 'delivered' => 4];
                $currentStage = $stages[$pkgStatus] ?? 1;
            ?>
            <div class="d-flex justify-content-between align-items-center mb-3 border-bottom pb-2">
                <h5 class="fw-bold mb-0">Fulfillment Pipeline &amp; Action</h5>
                <span class="badge fs-6 bg-<?= match($pkgStatus) { 'delivered' => 'success', 'shipped' => 'primary', 'confirmed' => 'info', 'cancelled' => 'danger', 'undelivered' => 'danger', default => 'warning text-dark' } ?> px-3 py-2 rounded-pill">
                    <i class="bi <?= match($pkgStatus) {
                        'delivered' => 'bi-check-circle-fill',
                        'shipped' => 'bi-truck',
                        'confirmed' => 'bi-hand-thumbs-up-fill',
                        'cancelled' => 'bi-x-circle-fill',
                        'undelivered' => 'bi-x-octagon-fill',
                        default => 'bi-clock-history'
                    } ?> me-1"></i> Your package: <?= $pkgStatus === 'undelivered' ? 'Not received' : ucfirst($pkgStatus) ?>
                </span>
            </div>
            <?php $fulfillBy = $shipment['fulfill_by'] ?? 'seller'; ?>
            <div class="alert alert-light border small mb-3">
                <strong>Who delivers:</strong>
                <?= $fulfillBy === 'admin' ? 'Solqam (you send the parcel to admin). Delivery fee stays with Solqam. Category commission still goes to Solqam from product value only.' : 'You (self-courier). After delivery you keep the delivery fee. Category commission on product value goes to Solqam.' ?>
            </div>
            <?php if (in_array($pkgStatus, ['placed', 'confirmed'], true) && ($shipment['status'] ?? '') !== 'shipped'): ?>
                <form action="<?= site_url('seller/orders/' . $order['id'] . '/fulfill') ?>" method="POST" class="d-flex flex-wrap gap-2 mb-3">
                    <?= csrf_field() ?>
                    <button name="fulfill_by" value="seller" class="btn btn-sm <?= $fulfillBy !== 'admin' ? 'btn-solqam' : 'btn-outline-secondary' ?>">I will deliver</button>
                    <button name="fulfill_by" value="admin" class="btn btn-sm <?= $fulfillBy === 'admin' ? 'btn-solqam' : 'btn-outline-secondary' ?>">Send to Solqam to deliver</button>
                </form>
            <?php endif; ?>
            <?php if ($fulfillBy === 'admin'): ?>
                <div class="alert alert-warning small">Handoff: <?= esc($shipment['handoff_status'] ?? 'pending_admin') ?>. Wait for admin to receive and deliver. You cannot mark shipped/delivered.</div>
            <?php endif; ?>

            <div class="position-relative m-4">
                <div class="progress" style="height: 4px;">
                    <div class="progress-bar bg-success" role="progressbar" style="width: <?= ($currentStage - 1) * 33.33 ?>%;"></div>
                </div>
                <div class="position-absolute top-0 start-0 translate-middle btn btn-sm <?= $currentStage >= 1 ? 'btn-success text-white' : 'btn-light border' ?> rounded-pill" style="width: 2rem; height:2rem; padding: 0.25rem;">1</div>
                <div class="position-absolute top-0 start-33 translate-middle btn btn-sm <?= $currentStage >= 2 ? 'btn-success text-white' : 'btn-light border' ?> rounded-pill" style="width: 2rem; height:2rem; padding: 0.25rem; left: 33.33%;">2</div>
                <div class="position-absolute top-0 start-66 translate-middle btn btn-sm <?= $currentStage >= 3 ? 'btn-success text-white' : 'btn-light border' ?> rounded-pill" style="width: 2rem; height:2rem; padding: 0.25rem; left: 66.66%;">3</div>
                <div class="position-absolute top-0 start-100 translate-middle btn btn-sm <?= $currentStage >= 4 ? 'btn-success text-white' : 'btn-light border' ?> rounded-pill" style="width: 2rem; height:2rem; padding: 0.25rem;">4</div>
            </div>
            <div class="d-flex justify-content-between text-center small text-muted px-2 mb-4">
                <div class="<?= $currentStage >= 1 ? 'fw-bold text-dark' : '' ?>">Placed</div>
                <div class="<?= $currentStage >= 2 ? 'fw-bold text-dark' : '' ?>">Confirmed</div>
                <div class="<?= $currentStage >= 3 ? 'fw-bold text-dark' : '' ?>">Shipped</div>
                <div class="<?= $currentStage >= 4 ? 'fw-bold text-success' : '' ?>">Delivered</div>
            </div>

            <?php if (in_array($pkgStatus, ['placed', 'confirmed', 'shipped'], true) && ($shipment['fulfill_by'] ?? 'seller') !== 'admin'): ?>
                <div class="p-3 bg-light rounded-3 border">
                    <form action="<?= site_url('seller/orders/' . $order['id'] . '/status') ?>" method="POST" class="d-flex flex-wrap align-items-center gap-3">
                        <?= csrf_field() ?>
                        <label class="small fw-bold text-dark mb-0">Update your package only:</label>
                        <select name="status" class="form-select form-select-sm" style="max-width: 240px;" required>
                            <?php if ($pkgStatus === 'placed'): ?>
                                <option value="confirmed">Confirmed (Accept Order)</option>
                                <option value="shipped">Shipped (Dispatched to Courier)</option>
                                <option value="delivered">Delivered (Handed to Customer)</option>
                            <?php elseif ($pkgStatus === 'confirmed'): ?>
                                <option value="shipped">Shipped (Dispatched to Courier)</option>
                                <option value="delivered">Delivered (Handed to Customer)</option>
                            <?php elseif ($pkgStatus === 'shipped'): ?>
                                <option value="delivered">Delivered (Handed to Customer)</option>
                                <option value="undelivered">Not received (charge courier fee to buyer wallet)</option>
                            <?php endif; ?>
                        </select>
                        <input type="text" name="courier" class="form-control form-control-sm" style="max-width: 160px;" placeholder="TCS / Leopard" value="<?= esc($shipment['courier'] ?? $order['courier'] ?? '') ?>">
                        <input type="text" name="tracking_number" class="form-control form-control-sm" style="max-width: 180px;" placeholder="Tracking #" value="<?= esc($shipment['tracking_number'] ?? $order['tracking_number'] ?? '') ?>">
                        <button type="submit" class="btn btn-primary btn-sm rounded-pill px-4" onclick="return confirm('Update this order status?');">
                            <i class="bi bi-arrow-right-circle me-1"></i> Apply Status
                        </button>
                    </form>
                </div>
            <?php elseif ($pkgStatus === 'undelivered'): ?>
                <div class="alert alert-danger border-0 small mb-0 rounded-3">
                    <strong>Not received:</strong> Buyer did not take the parcel. Courier fee was charged to their wallet (wallet may go minus). Product stock was restored.
                </div>
            <?php elseif ($pkgStatus === 'delivered'): ?>
                <div class="alert alert-success border-0 bg-success-subtle text-success small mb-0 rounded-3 d-flex align-items-center gap-2">
                    <i class="bi bi-check-circle-fill fs-5"></i>
                    <div>
                        <strong>Order Completed:</strong> This package has been delivered successfully. Customer received their purchase and wallet cashback was credited.
                    </div>
                </div>
            <?php elseif ($fulfillBy === 'admin'): ?>
                <p class="small text-muted mb-0">Solqam will update shipped / delivered after the inbound parcel is received.</p>
            <?php else: ?>
                <div class="alert alert-danger-subtle border-0 text-danger small mb-0 rounded-3">
                    This order is <strong><?= ucfirst($order['status']) ?></strong>.
                </div>
            <?php endif; ?>
        </div>

        <!-- Seller Items Table -->
        <div class="card border-0 shadow-sm rounded-4 p-4 bg-white">
            <h5 class="fw-bold mb-3 border-bottom pb-2">Your Items in this Order</h5>
            <div class="table-responsive">
                <table class="table align-middle">
                    <thead class="table-light small">
                        <tr>
                            <th>Item Name</th>
                            <th>Unit Price</th>
                            <th>Qty</th>
                        <th>Subtotal</th>
                        <th>Cashback</th>
                        <th>Commission</th>
                        <th>Net to you</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($sellerItems as $item): ?>
                            <tr>
                                <td class="fw-semibold text-dark"><?= esc($item['product_name']) ?></td>
                                <td>Rs. <?= number_format($item['price'], 0) ?></td>
                                <td><?= $item['quantity'] ?></td>
                                <td class="fw-bold">Rs. <?= number_format($item['subtotal'], 2) ?></td>
                                <td class="text-success small">- Rs. <?= number_format(item_cashback($item), 2) ?></td>
                                <td class="text-danger small">- Rs. <?= number_format($item['commission_amount'], 2) ?></td>
                                <td class="text-success fw-bold">
                                    Rs. <?= number_format(item_seller_net($item), 2) ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Shipping & Buyer Details -->
    <div class="col-lg-4">
        <div class="card border-0 shadow-sm rounded-4 p-4 bg-white mb-4">
            <h5 class="fw-bold mb-3 border-bottom pb-2"><i class="bi bi-geo-alt text-success me-1"></i> Delivery Destination</h5>
            <div class="small">
                <strong class="d-block text-dark mb-1"><?= esc($order['recipient_name']) ?></strong>
                <div class="text-secondary mb-1"><?= esc($order['street_address']) ?></div>
                <div class="text-secondary mb-2"><?= esc($order['city']) ?>, <?= esc($order['province']) ?> <?= esc($order['postal_code']) ?></div>
                <div class="text-secondary"><i class="bi bi-telephone me-1"></i> <?= esc($order['recipient_phone']) ?></div>
                <?php if (!empty($order['user_id'])): ?>
                    <a class="btn btn-sm btn-outline-primary rounded-pill mt-3" href="<?= site_url('seller/customers/' . $order['user_id']) ?>">Buyer 360 — wallet &amp; commission</a>
                <?php endif; ?>
            </div>

            <?php if (!empty($order['notes'])): ?>
                <div class="alert alert-light border mt-3 mb-0 small">
                    <strong>Buyer Note:</strong> <?= esc($order['notes']) ?>
                </div>
            <?php endif; ?>
        </div>

        <div class="card border-0 shadow-sm rounded-4 p-4 bg-white">
            <h6 class="fw-bold mb-3 border-bottom pb-2">Payment Details</h6>
            <div class="small">
                <div class="mb-1"><strong>Payment Method:</strong> <?= strtoupper($order['payment_method'] ?? 'COD') ?></div>
                <div class="mb-1"><strong>Payment Status:</strong> <span class="badge bg-<?= ($order['payment_status'] === 'paid') ? 'success' : 'warning text-dark' ?>"><?= strtoupper($order['payment_status'] ?? 'PENDING') ?></span></div>
                <?php if (!empty($order['transaction_ref'])): ?>
                    <div class="text-muted">Ref: <code><?= esc($order['transaction_ref']) ?></code></div>
                <?php endif; ?>
                <p class="text-muted mt-2 mb-0">Net = goods − product cashback − platform commission. Online: net auto-credited. COD: collect cash, mark delivered — commission to admin, cashback to buyer.</p>
            </div>
        </div>
    </div>
</div>
<?= $this->endSection() ?>
