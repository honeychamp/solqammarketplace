<?= $this->extend('layouts/admin') ?>

<?= $this->section('content') ?>
<?php
$fromMine = !empty($fromMine);
$deskSellerIds = array_map('intval', $deskSellerIds ?? []);
$deskPackages = [];
foreach ($order['shipments'] ?? [] as $pkg) {
    $sid = (int) ($pkg['seller_id'] ?? 0);
    if (in_array($sid, $deskSellerIds, true)) {
        $deskPackages[] = $pkg;
    }
}
$canEdit = false;
foreach ($deskPackages as $pkg) {
    if (($pkg['status'] ?? 'placed') === 'placed') {
        $canEdit = true;
        break;
    }
}
if ($deskPackages === [] && $deskSellerIds !== [] && ($order['status'] ?? '') === 'placed') {
    $canEdit = true;
}
$storeBySeller = [];
foreach ($order['items'] ?? [] as $it) {
    $sid = (int) ($it['seller_id'] ?? 0);
    $storeBySeller[$sid] = ! empty($it['is_admin_item'])
        ? 'Solqam store'
        : (string) ($it['store_name'] ?? ('Seller #' . $sid));
}
?>
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h4 class="fw-bold mb-1">Order #<?= esc($order['order_number']) ?></h4>
        <p class="text-secondary small mb-0">Received <?= esc(pk_datetime($order['created_at'])) ?> PKT. Seller self-deliver view-only. <strong>Send to Solqam</strong> packages: admin call, confirm, ship, deliver.</p>
    </div>
    <a href="<?= site_url($fromMine ? 'admin/my-orders' : 'admin/orders') ?>" class="btn btn-light btn-sm rounded-pill border px-3">
        <i class="bi bi-arrow-left me-1"></i> Back to Orders
    </a>
</div>

<?php if ($deskSellerIds === []): ?>
<div class="alert alert-secondary rounded-4">
    The seller will deliver this order — confirm / ship / deliver from <strong>Seller Hub</strong>. Admin does not run it here.
    If the seller chooses <em>Send to Solqam to deliver</em>, admin handles the call, confirm, shipped and delivered here.
</div>
<?php else: ?>
<div class="alert alert-info rounded-4">
    <strong>Solqam delivery:</strong> Call, qty/address, Confirm, then mark <em>Shipped</em> and <em>Delivered</em>.
    Seller self-deliver packages are handled by the seller.
</div>
<?php endif; ?>

<div class="row g-4">
    <div class="col-lg-8">
        <?php if (($order['status'] ?? '') === 'placed' && ! empty($siblings)): ?>
        <div class="card-custom p-4 mb-4 border-warning">
            <h5 class="fw-bold mb-2">Other placed orders from this customer</h5>
            <p class="small text-muted mb-3">If the customer only needed one order, cancel the extras here.</p>
            <?php foreach ($siblings as $sib): ?>
                <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 border rounded-3 p-2 mb-2">
                    <div>
                        <a href="<?= site_url('admin/orders/' . (int) $sib['id']) ?>"><strong><?= esc($sib['order_number']) ?></strong></a>
                        <span class="small text-muted ms-2"><?= esc(pk_datetime($sib['created_at'] ?? '')) ?> · Rs. <?= number_format((float) ($sib['final_payable'] ?? 0), 0) ?></span>
                    </div>
                    <form method="POST" action="<?= site_url('admin/orders/' . (int) $sib['id'] . '/cancel-placed') ?>" onsubmit="return confirm('Cancel extra order <?= esc($sib['order_number']) ?>?');">
                        <?= csrf_field() ?>
                        <input type="hidden" name="back_order" value="<?= (int) $order['id'] ?>">
                        <button class="btn btn-sm btn-outline-danger rounded-pill">Cancel extra</button>
                    </form>
                </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>

        <?php if ($canEdit): ?>
        <form method="POST" action="<?= site_url('admin/orders/' . $order['id'] . '/edit') ?>" class="card-custom p-4 mb-4">
            <?= csrf_field() ?>
            <?php if ($fromMine): ?><input type="hidden" name="mine" value="1"><?php endif; ?>
            <h5 class="fw-bold mb-3">Edit before confirm</h5>
            <div class="table-responsive mb-3">
                <table class="table align-middle">
                    <thead class="table-light small">
                        <tr><th>Product</th><th style="width:110px">Qty</th><th>Line</th></tr>
                    </thead>
                    <tbody>
                        <?php foreach ($order['items'] as $item): ?>
                            <?php $deskLine = in_array((int) ($item['seller_id'] ?? 0), $deskSellerIds, true); ?>
                            <tr>
                                <td>
                                    <div class="fw-semibold"><?= esc($item['product_name']) ?></div>
                                    <div class="small text-muted">Rs. <?= number_format((float) $item['price'], 0) ?> each<?= $deskLine ? ' · 0 = remove' : ' · seller package (view only)' ?></div>
                                    <?= view('partials/order_item_gift', ['item' => $item]) ?>
                                </td>
                                <td>
                                    <?php if ($deskLine): ?>
                                        <input type="number" class="form-control form-control-sm" name="qty[<?= (int) $item['id'] ?>]" value="<?= (int) $item['quantity'] ?>" min="0" max="99">
                                    <?php else: ?>
                                        <span><?= (int) $item['quantity'] ?></span>
                                    <?php endif; ?>
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
            <span class="small text-muted ms-2">Totals, shipping and commission are recalculated. Confirm is a separate button.</span>
        </form>
        <div class="d-flex flex-wrap gap-2 mb-4">
            <form method="POST" action="<?= site_url('admin/orders/' . $order['id'] . '/confirm-call') ?>" onsubmit="return confirm('Did you speak to the customer? Commission becomes due after confirm.');">
                <?= csrf_field() ?>
                <?php if ($fromMine): ?><input type="hidden" name="mine" value="1"><?php endif; ?>
                <button type="submit" class="btn btn-success rounded-pill px-4">Confirm after call</button>
            </form>
            <form method="POST" action="<?= site_url('admin/orders/' . $order['id'] . '/cancel-placed') ?>" onsubmit="return confirm('Cancel this whole order?');">
                <?= csrf_field() ?>
                <?php if ($fromMine): ?><input type="hidden" name="mine" value="1"><?php endif; ?>
                <button type="submit" class="btn btn-outline-danger rounded-pill px-3">Cancel this order</button>
            </form>
        </div>
        <?php endif; ?>

        <?php if ($deskPackages !== [] || ($deskSellerIds !== [] && ! empty($hasAdminItems))): ?>
        <div class="card-custom p-4 mb-4">
            <h5 class="fw-bold mb-3 border-bottom pb-2">Solqam fulfillment — confirm / ship / deliver</h5>
            <?php
            $rows = $deskPackages;
            if ($rows === []) {
                foreach ($deskSellerIds as $sid) {
                    $rows[] = ['seller_id' => $sid, 'status' => 'placed', 'courier' => '', 'tracking_number' => ''];
                }
            }
            foreach ($rows as $pkg):
                $sid = (int) ($pkg['seller_id'] ?? 0);
                $st  = (string) ($pkg['status'] ?? 'placed');
            ?>
            <div class="border rounded-3 p-3 mb-3">
                <div class="d-flex flex-wrap justify-content-between gap-2 mb-2">
                    <strong><?= esc($storeBySeller[$sid] ?? ('Seller #' . $sid)) ?></strong>
                    <span class="badge fs-6 bg-info text-dark"><?= esc(ucfirst($st)) ?></span>
                </div>
                <?php if (! in_array($st, ['delivered', 'cancelled', 'undelivered'], true)): ?>
                <form action="<?= site_url('admin/orders/' . $order['id'] . '/status') ?>" method="POST" class="d-flex align-items-center gap-2 flex-wrap">
                    <?= csrf_field() ?>
                    <?php if ($fromMine): ?><input type="hidden" name="mine" value="1"><?php endif; ?>
                    <input type="hidden" name="seller_id" value="<?= $sid ?>">
                    <select name="status" class="form-select form-select-sm" style="max-width: 220px;">
                        <?php if ($st === 'placed'): ?>
                            <option value="confirmed">Confirmed (after call)</option>
                            <option value="shipped">Shipped</option>
                            <option value="delivered">Delivered</option>
                        <?php elseif ($st === 'confirmed'): ?>
                            <option value="shipped" selected>Shipped</option>
                            <option value="delivered">Delivered</option>
                        <?php elseif ($st === 'shipped'): ?>
                            <option value="delivered" selected>Delivered</option>
                            <option value="undelivered">Not received (charge courier fee)</option>
                        <?php endif; ?>
                    </select>
                    <input type="text" name="courier" class="form-control form-control-sm" style="max-width:140px" placeholder="TCS / Leopard" value="<?= esc($pkg['courier'] ?? '') ?>">
                    <input type="text" name="tracking_number" class="form-control form-control-sm" style="max-width:160px" placeholder="Tracking #" value="<?= esc($pkg['tracking_number'] ?? '') ?>">
                    <button type="submit" class="btn btn-primary btn-sm rounded-pill px-4">Update Solqam package</button>
                </form>
                <?php endif; ?>
            </div>
            <?php endforeach; ?>
        </div>
        <?php elseif ($deskSellerIds === []): ?>
        <div class="alert alert-secondary">Seller self-deliver — fulfillment seller hub se. Yahan view only.</div>
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
                                <td class="fw-semibold">
                                    <?= esc($item['product_name']) ?>
                                    <?php if (! empty($item['variant_label'])): ?>
                                        <div class="small text-solqam fw-normal"><?= esc($item['variant_label']) ?></div>
                                    <?php endif; ?>
                                    <?= view('partials/order_item_gift', ['item' => $item]) ?>
                                </td>
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
            <div class="d-flex justify-content-between mb-2"><span class="text-secondary">Gross (goods)</span><span>Rs. <?= number_format((float) $order['total_amount'], 2) ?></span></div>
            <?php if ((float) ($order['gift_wrap_amount'] ?? 0) > 0): ?>
            <div class="d-flex justify-content-between mb-2"><span class="text-secondary">Gift wrap</span><span>Rs. <?= number_format((float) $order['gift_wrap_amount'], 2) ?></span></div>
            <?php endif; ?>
            <div class="d-flex justify-content-between mb-2"><span class="text-secondary">Delivery</span><span>Rs. <?= number_format((float) ($order['shipping_amount'] ?? 0), 2) ?></span></div>
            <div class="d-flex justify-content-between mb-2"><span class="text-secondary">Wallet used</span><span>Rs. <?= number_format((float) ($order['wallet_amount_used'] ?? 0), 2) ?></span></div>
            <div class="d-flex justify-content-between mb-2"><span class="text-secondary">Buyer paid now</span><span>Rs. <?= number_format((float) $order['final_payable'], 2) ?></span></div>
            <div class="d-flex justify-content-between mb-2 text-success"><span>Buyer cashback</span><span>Rs. <?= number_format((float) ($order['cashback_amount'] ?? 0), 2) ?></span></div>
            <div class="d-flex justify-content-between mb-3 text-primary"><span>Commission (category<?= isset($order['commission_rate']) ? ', avg ' . esc($order['commission_rate']) . '%' : '' ?>)</span><span>Rs. <?= number_format((float) $order['commission_amount'], 2) ?></span></div>
            <?php if (!empty($order['shipments'])): ?>
                <hr>
                <div class="small fw-bold mb-2">Packages</div>
                <?php foreach ($order['shipments'] as $pkg): ?>
                    <div class="small border rounded-3 p-2 mb-2">
                        <div><strong><?= ((int) ($pkg['seller_id'] ?? 0) === (int) $adminId) ? 'Solqam' : 'Seller #' . (int) $pkg['seller_id'] ?></strong>
                            · <?= esc($pkg['fulfill_by'] ?? 'seller') ?> deliver
                            · <?= esc($pkg['status'] ?? '') ?></div>
                        <div>Delivery share Rs. <?= number_format((float) ($pkg['shipping_amount'] ?? 0), 0) ?></div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
            <?php foreach ($codDues ?? [] as $d): ?>
                <div class="small alert alert-warning py-2 mb-2">
                    COD self-ship <?= esc($d['store_name'] ?? 'seller') ?>:
                    collected Rs. <?= number_format((float) $d['collected_cash'], 0) ?>,
                    commission Rs. <?= number_format((float) $d['commission_amount'], 0) ?>,
                    cashback Rs. <?= number_format((float) $d['cashback_amount'], 0) ?>,
                    due <strong>Rs. <?= number_format((float) $d['due_amount'], 0) ?></strong>
                    · left Rs. <?= number_format(\App\Services\Commission\SellerDueService::remaining($d), 0) ?>
                    — <?= esc($d['status']) ?>
                    <a class="ms-1" href="<?= site_url('admin/dues/' . (int) $d['id'] . '/receipt') ?>">Receipt</a>
                    <?php if ($d['status'] === 'unpaid'): ?>
                        <form method="POST" action="<?= site_url('admin/dues/' . (int) $d['id'] . '/paid') ?>" class="mt-2 d-flex gap-2">
                            <?= csrf_field() ?>
                            <input type="number" name="amount" class="form-control form-control-sm" style="max-width:120px" value="<?= (int) \App\Services\Commission\SellerDueService::remaining($d) ?>">
                            <button class="btn btn-sm btn-success">Mark Solqam received</button>
                        </form>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
            <div class="small text-muted mt-2">Seller items: commission from the product’s category. Admin SKUs: 0%. Buyer cashback is the % saved on each product. Prepaid now; COD / Pay later after delivery.</div>
        </div>
        <?= view('partials/order_party_facts', [
            'order'            => $order,
            'showPayLaterDocs' => true,
            'buyer360'         => ! empty($order['user_id'])
                ? '<a class="btn btn-sm btn-outline-primary rounded-pill mt-3" href="' . site_url('admin/customers/' . $order['user_id']) . '">Customer 360</a>'
                : '',
            'payFootnote'      => '',
        ]) ?>
    </div>
</div>
<?= $this->endSection() ?>
