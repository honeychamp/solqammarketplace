<?= $this->extend('layouts/seller') ?>

<?= $this->section('content') ?>
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h4 class="fw-bold mb-1">Order #<?= esc($order['order_number']) ?></h4>
        <p class="text-secondary small mb-0">Order received <?= esc(pk_datetime($order['created_at'])) ?> PKT</p>
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
                $pkgStatus = (string) (($shipment['status'] ?? null) ?: ($order['status'] ?? 'placed'));
                $fulfillBy = (string) (($shipment['fulfill_by'] ?? null) ?: 'seller');
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
                <div class="alert alert-warning small">
                    <strong>Send to Solqam:</strong> Admin will call, confirm, ship and deliver.
                    You cannot set this status. Handoff: <?= esc($shipment['handoff_status'] ?? 'pending_admin') ?>.
                </div>
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

            <?php $canSellerEdit = ($pkgStatus === 'placed' && $fulfillBy !== 'admin'); ?>
            <?php if ($canSellerEdit): ?>
                <div class="alert alert-info small mb-3">After the call, save qty / address first, then Confirm. Commission is not due until you confirm.</div>
            <?php endif; ?>
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
                <p class="small text-muted mb-0">Admin will call to confirm, then mark shipped and delivered.</p>
            <?php else: ?>
                <div class="alert alert-danger-subtle border-0 text-danger small mb-0 rounded-3">
                    This order is <strong><?= ucfirst($order['status']) ?></strong>.
                </div>
            <?php endif; ?>
        </div>

        <?php if ($canSellerEdit): ?>
        <form method="POST" action="<?= site_url('seller/orders/' . $order['id'] . '/edit') ?>" class="card border-0 shadow-sm rounded-4 p-4 bg-white mb-4">
            <?= csrf_field() ?>
            <h5 class="fw-bold mb-3 border-bottom pb-2">Edit before confirm</h5>
            <div class="table-responsive mb-3">
                <table class="table align-middle">
                    <thead class="table-light small">
                        <tr>
                            <th>Item</th>
                            <th>Price</th>
                            <th style="width:110px">Qty</th>
                            <th>Line</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($sellerItems as $item): ?>
                            <tr>
                                <td class="fw-semibold"><?= esc($item['product_name']) ?><div class="small text-muted">0 = remove</div><?= view('partials/order_item_gift', ['item' => $item]) ?></td>
                                <td>Rs. <?= number_format((float) $item['price'], 0) ?></td>
                                <td>
                                    <input type="number" class="form-control form-control-sm" name="qty[<?= (int) $item['id'] ?>]" value="<?= (int) $item['quantity'] ?>" min="0" max="99">
                                </td>
                                <td>Rs. <?= number_format((float) $item['subtotal'], 0) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <h6 class="fw-bold mb-2">Address (fix on the call)</h6>
            <div class="row g-2 mb-3">
                <div class="col-md-6">
                    <label class="form-label small mb-1">Name</label>
                    <input class="form-control form-control-sm" name="recipient_name" value="<?= esc($order['recipient_name'] ?? '') ?>">
                </div>
                <div class="col-md-6">
                    <label class="form-label small mb-1">Phone</label>
                    <input class="form-control form-control-sm" name="phone" value="<?= esc($order['recipient_phone'] ?? $order['customer_phone'] ?? '') ?>">
                </div>
                <div class="col-12">
                    <label class="form-label small mb-1">Street</label>
                    <textarea class="form-control form-control-sm" name="street_address" rows="2"><?= esc($order['street_address'] ?? '') ?></textarea>
                </div>
                <div class="col-md-4">
                    <label class="form-label small mb-1">City</label>
                    <input class="form-control form-control-sm" name="city" value="<?= esc($order['city'] ?? '') ?>">
                </div>
                <div class="col-md-4">
                    <label class="form-label small mb-1">Province</label>
                    <input class="form-control form-control-sm" name="province" value="<?= esc($order['province'] ?? '') ?>">
                </div>
                <div class="col-md-4">
                    <label class="form-label small mb-1">Postal</label>
                    <input class="form-control form-control-sm" name="postal_code" value="<?= esc($order['postal_code'] ?? '') ?>">
                </div>
                <div class="col-md-6">
                    <label class="form-label small mb-1">WhatsApp / alt</label>
                    <input class="form-control form-control-sm" name="alternate_phone" value="<?= esc($order['alternate_phone'] ?? '') ?>">
                </div>
                <div class="col-md-6">
                    <label class="form-label small mb-1">Area</label>
                    <input class="form-control form-control-sm" name="area" value="<?= esc($order['area'] ?? '') ?>">
                </div>
                <div class="col-12">
                    <label class="form-label small mb-1">Landmark</label>
                    <input class="form-control form-control-sm" name="landmark" value="<?= esc($order['landmark'] ?? '') ?>">
                </div>
                <div class="col-12">
                    <label class="form-label small mb-1">Customer note</label>
                    <textarea class="form-control form-control-sm" name="notes" rows="2"><?= esc($order['notes'] ?? '') ?></textarea>
                </div>
            </div>
            <button type="submit" class="btn btn-outline-dark rounded-pill px-4">Save changes</button>
            <span class="small text-muted ms-2">Then tap Confirm (Accept Order) above.</span>
        </form>
        <?php endif; ?>

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
                                <td class="fw-semibold text-dark">
                                    <?= esc($item['product_name']) ?>
                                    <?php if (! empty($item['variant_label'])): ?>
                                        <div class="small text-solqam fw-normal"><?= esc($item['variant_label']) ?></div>
                                    <?php endif; ?>
                                    <?= view('partials/order_item_gift', ['item' => $item]) ?>
                                </td>
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
        <?= view('partials/order_party_facts', [
            'order'            => $order,
            'showPayLaterDocs' => false,
            'buyer360'         => ! empty($order['user_id'])
                ? '<a class="btn btn-sm btn-outline-primary rounded-pill mt-3" href="' . site_url('seller/customers/' . $order['user_id']) . '">Buyer 360 — wallet &amp; commission</a>'
                : '',
            'payFootnote'      => '<p class="text-muted mt-2 mb-0">Net = goods − product cashback − platform commission. Online: net auto-credited. COD: collect cash, mark delivered — commission to admin, cashback to buyer.</p>',
        ]) ?>
    </div>
</div>
<?= $this->endSection() ?>
