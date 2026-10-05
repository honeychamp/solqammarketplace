<?= $this->extend('layouts/main') ?>

<?= $this->section('content') ?>
<div class="container py-4">
    <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
        <div>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb small mb-1">
                    <li class="breadcrumb-item"><a href="<?= site_url('account/orders') ?>" class="text-secondary text-decoration-none">My Orders</a></li>
                    <li class="breadcrumb-item active font-monospace">#<?= esc($order['order_number']) ?></li>
                </ol>
            </nav>
            <h3 class="fw-bold mb-0" style="color: #0F172A;">Order #<?= esc($order['order_number']) ?></h3>
        </div>
        <div>
            <a href="<?= site_url('account/orders/' . $order['id'] . '/invoice') ?>" class="btn btn-outline-primary btn-sm rounded-pill px-3 me-2">Invoice</a>
            <?php if ($order['status'] === 'placed'): ?>
            <form action="<?= site_url('account/orders/' . $order['id'] . '/cancel') ?>" method="POST" class="d-inline">
                <?= csrf_field() ?>
                <button class="btn btn-outline-secondary btn-sm rounded-pill px-3 me-2" type="submit">Cancel order</button>
            </form>
            <?php endif; ?>
            <?php if ($order['status'] === 'delivered'): ?>
                <?php if ($existingReturn): ?>
                    <span class="badge bg-danger-subtle text-danger border border-danger-subtle px-3 py-2 rounded-pill me-2 fw-semibold">
                        Return: <?= ucfirst($existingReturn['status']) ?>
                    </span>
                <?php endif; ?>
            <?php endif; ?>
            <a href="<?= site_url('account/orders') ?>" class="btn btn-light btn-sm rounded-pill border px-3">
                <i class="bi bi-arrow-left me-1"></i> Back to Orders
            </a>
        </div>
    </div>

    <!-- Fulfillment Pipeline Tracker -->
    <div class="card border-0 shadow-sm rounded-4 p-4 bg-white mb-4">
        <h6 class="fw-bold mb-3 text-muted text-uppercase" style="font-size: 0.75rem; letter-spacing: 0.5px;">Fulfillment Progress Tracker</h6>
        <?php
        $statuses = ['placed' => 1, 'confirmed' => 2, 'shipped' => 3, 'delivered' => 4];
        $currentStep = $statuses[$order['status']] ?? 1;
        $isCancelled = $order['status'] === 'cancelled';
        $isReturned  = $order['status'] === 'returned';
        $isUndelivered = $order['status'] === 'undelivered';
        ?>

        <?php if ($isCancelled || $isReturned || $isUndelivered): ?>
            <div class="alert <?= $isUndelivered ? 'alert-warning' : 'alert-danger' ?> py-2 mb-0 border-0 shadow-sm rounded-3">
                <i class="bi bi-exclamation-octagon-fill me-1"></i>
                <?php if ($isUndelivered): ?>
                    This order was <strong>not received</strong>. Courier fee was charged to your wallet (wallet may show minus). That remaining amount is added to your next purchase.
                <?php else: ?>
                    This order is marked as <strong><?= ucfirst($order['status']) ?></strong>.
                <?php endif; ?>
            </div>
        <?php else: ?>
            <div class="row text-center g-2 position-relative">
                <div class="col-3">
                    <div class="p-2 rounded-circle mx-auto mb-2 <?= $currentStep >= 1 ? 'text-white' : 'bg-light text-muted' ?>" style="width: 44px; height: 44px; display: flex; align-items: center; justify-content: center; background-color: <?= $currentStep >= 1 ? 'var(--sol-primary)' : '#F1F5F9' ?>;">
                        <i class="bi bi-cart-check fs-5"></i>
                    </div>
                    <div class="small fw-bold <?= $currentStep >= 1 ? 'text-primary' : 'text-muted' ?>">Placed</div>
                    <div class="text-muted" style="font-size: 0.7rem;"><?= date('d M, h:i A', strtotime($order['created_at'])) ?></div>
                </div>
                <div class="col-3">
                    <div class="p-2 rounded-circle mx-auto mb-2 <?= $currentStep >= 2 ? 'text-white' : 'bg-light text-muted' ?>" style="width: 44px; height: 44px; display: flex; align-items: center; justify-content: center; background-color: <?= $currentStep >= 2 ? 'var(--sol-primary)' : '#F1F5F9' ?>;">
                        <i class="bi bi-patch-check fs-5"></i>
                    </div>
                    <div class="small fw-bold <?= $currentStep >= 2 ? 'text-primary' : 'text-muted' ?>">Confirmed</div>
                    <div class="text-muted" style="font-size: 0.7rem;">Vendor Approved</div>
                </div>
                <div class="col-3">
                    <div class="p-2 rounded-circle mx-auto mb-2 <?= $currentStep >= 3 ? 'text-white' : 'bg-light text-muted' ?>" style="width: 44px; height: 44px; display: flex; align-items: center; justify-content: center; background-color: <?= $currentStep >= 3 ? 'var(--sol-primary)' : '#F1F5F9' ?>;">
                        <i class="bi bi-truck fs-5"></i>
                    </div>
                    <div class="small fw-bold <?= $currentStep >= 3 ? 'text-primary' : 'text-muted' ?>">Shipped</div>
                    <div class="text-muted" style="font-size: 0.7rem;">
                        <?php if (!empty($order['tracking_number'])): ?>
                            <?= esc($order['courier'] ?: 'Courier') ?> · <?= esc($order['tracking_number']) ?>
                        <?php else: ?>
                            In Transit
                        <?php endif; ?>
                    </div>
                </div>
                <div class="col-3">
                    <div class="p-2 rounded-circle mx-auto mb-2 <?= $currentStep >= 4 ? 'bg-success text-white' : 'bg-light text-muted' ?>" style="width: 44px; height: 44px; display: flex; align-items: center; justify-content: center;">
                        <i class="bi bi-house-door fs-5"></i>
                    </div>
                    <div class="small fw-bold <?= $currentStep >= 4 ? 'text-success' : 'text-muted' ?>">Delivered</div>
                    <div class="text-muted" style="font-size: 0.7rem;">
                        <?= $order['delivery_date'] ? date('d M, h:i A', strtotime($order['delivery_date'])) : 'Pending Arrival' ?>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    </div>

    <?php if (!empty($order['shipments'])): ?>
        <div class="card border-0 shadow-sm rounded-4 p-4 bg-white mb-4">
            <h6 class="fw-bold mb-3">Packages by seller</h6>
            <?php foreach ($order['shipments'] as $pkg): ?>
                <div class="d-flex justify-content-between align-items-center border-bottom py-2">
                    <div>
                        <div class="fw-semibold">Seller #<?= (int) $pkg['seller_id'] ?></div>
                        <div class="small text-muted">
                            <?= esc($pkg['courier'] ?: 'Courier') ?>
                            <?= $pkg['tracking_number'] ? ' · ' . esc($pkg['tracking_number']) : '' ?>
                        </div>
                    </div>
                    <span class="badge bg-light text-dark"><?= esc($pkg['status']) ?></span>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <div class="row g-4">
        <!-- Order Items -->
        <div class="col-lg-8">
            <div class="card border-0 shadow-sm rounded-4 p-4 bg-white mb-4">
                <h5 class="fw-bold mb-3 border-bottom pb-2" style="color: #0F172A;">Order Items</h5>
                <div class="table-responsive">
                    <table class="table align-middle table-hover">
                        <thead class="table-light small text-uppercase" style="font-size: 0.75rem; letter-spacing: 0.5px;">
                            <tr>
                                <th>Product</th>
                                <th>Price</th>
                                <th>Qty</th>
                                <th>Subtotal</th>
                                <?php if ($order['status'] === 'delivered'): ?>
                                    <th class="text-end">Review</th>
                                <?php endif; ?>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($order['items'] as $item): ?>
                                <tr>
                                    <td>
                                        <div class="d-flex align-items-center">
                                            <img src="<?= esc($item['product_image'] ?: base_url('assets/images/product-placeholder.svg')) ?>" class="rounded-3 me-3" style="width: 52px; height: 52px; object-fit: cover;">
                                            <div>
                                                <h6 class="mb-1 fw-semibold text-dark">
                                                    <a href="<?= site_url('product/' . $item['product_id']) ?>" class="text-dark text-decoration-none">
                                                        <?= esc($item['product_name']) ?>
                                                    </a>
                                                </h6>
                                                <small class="text-muted"><i class="bi bi-shop me-1 text-primary"></i> <?= esc($item['store_name'] ?? 'Verified Vendor') ?></small>
                                            </div>
                                        </div>
                                    </td>
                                    <td>Rs. <?= number_format($item['price'], 0) ?></td>
                                    <td class="fw-bold"><?= $item['quantity'] ?></td>
                                    <td class="fw-bold text-dark">Rs. <?= number_format($item['subtotal'], 0) ?></td>
                                    <?php if ($order['status'] === 'delivered'): ?>
                                        <td class="text-end">
                                            <button type="button" class="btn btn-outline-warning btn-sm rounded-pill px-3 fw-semibold" data-bs-toggle="modal" data-bs-target="#reviewModal_<?= $item['id'] ?>">
                                                <i class="bi bi-star-fill text-warning me-1"></i> Review
                                            </button>
                                        </td>
                                    <?php endif; ?>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Order Summary & Shipping Address -->
        <div class="col-lg-4">
            <!-- Payment & Totals -->
            <div class="card border-0 shadow-sm rounded-4 p-4 bg-white mb-4">
                <h5 class="fw-bold mb-3 border-bottom pb-2" style="color: #0F172A;">Payment Summary</h5>

                <div class="d-flex justify-content-between mb-2">
                    <span class="text-secondary">Subtotal</span>
                    <span class="fw-bold text-dark">Rs. <?= number_format($order['total_amount'], 2) ?></span>
                </div>

                <?php if ($order['wallet_amount_used'] > 0): ?>
                    <div class="d-flex justify-content-between mb-2 text-success">
                        <span>Wallet Ledger Applied</span>
                        <span class="fw-bold">- Rs. <?= number_format($order['wallet_amount_used'], 2) ?></span>
                    </div>
                <?php endif; ?>

                <?php $arrearsPaid = (float) ($order['delivery_arrears'] ?? 0); ?>
                <?php if ($arrearsPaid > 0): ?>
                    <div class="d-flex justify-content-between mb-2 text-danger">
                        <span>Previous unpaid courier fee</span>
                        <span class="fw-bold">Rs. <?= number_format($arrearsPaid, 2) ?></span>
                    </div>
                <?php endif; ?>

                <div class="d-flex justify-content-between mb-2">
                    <span class="text-secondary">Delivery Charge</span>
                    <?php $shipAmt = (float) ($order['shipping_amount'] ?? 0); ?>
                    <span class="fw-bold <?= $shipAmt <= 0 ? 'text-success' : 'text-dark' ?>">
                        <?= $shipAmt <= 0 ? 'FREE' : 'Rs. ' . number_format($shipAmt, 2) ?>
                    </span>
                </div>

                <hr>

                <div class="d-flex justify-content-between mb-3">
                    <span class="fw-bold fs-5 text-dark">Total Paid / Payable</span>
                    <span class="fw-bold fs-5 text-primary font-monospace">Rs. <?= number_format($order['final_payable'], 2) ?></span>
                </div>

                <div class="p-3 bg-light rounded-3 small">
                    <div class="mb-1"><strong>Payment Method:</strong> <?= strtoupper($order['payment_method'] ?? 'COD') ?></div>
                    <div class="mb-1"><strong>Payment Status:</strong> <span class="badge rounded-pill bg-<?= ($order['payment_status'] === 'paid') ? 'success' : (($order['payment_status'] ?? '') === 'failed' ? 'danger' : 'warning text-dark') ?>"><?= strtoupper($order['payment_status'] ?? 'PENDING') ?></span></div>
                    <?php if (!empty($order['transaction_ref'])): ?>
                        <div><strong>Txn Reference:</strong> <code><?= esc($order['transaction_ref']) ?></code></div>
                    <?php endif; ?>
                </div>
                <?php if (is_online_gateway($order['payment_method'] ?? '') && ($order['payment_status'] ?? '') !== 'paid'): ?>
                    <a href="<?= site_url('checkout/pay/' . $order['id']) ?>" class="btn btn-solqam w-100 rounded-pill mt-3">Complete PayFast payment</a>
                    <p class="small text-muted mt-2 mb-0">Paid tabhi hoga jab PayFast confirm kare.</p>
                <?php endif; ?>
                <?php if (!empty($order['pay_later'])): ?>
                    <div class="mt-3 p-3 border rounded-3 small">
                        <strong>Pay later KYC</strong>
                        <div><?= esc($order['pay_later']['full_name']) ?> · <?= esc($order['pay_later']['cnic_number']) ?></div>
                        <?php if ((float) ($order['pay_later_wallet'] ?? 0) > 0 && empty($order['pay_later_cleared'])): ?>
                            <div class="text-danger mt-1">Wallet minus Rs. <?= number_format((float) $order['pay_later_wallet'], 2) ?> until this remaining is paid.</div>
                        <?php endif; ?>
                        <div class="text-muted"><?= esc($order['pay_later']['address_text']) ?></div>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Shipping Address -->
            <div class="card border-0 shadow-sm rounded-4 p-4 bg-white mb-4">
                <h6 class="fw-bold mb-3 border-bottom pb-2" style="color: #0F172A;"><i class="bi bi-geo-alt text-primary me-1"></i> Delivery Destination</h6>
                <div class="small">
                    <strong class="text-dark d-block fs-6 mb-1"><?= esc($order['recipient_name']) ?></strong>
                    <div class="text-secondary"><?= esc($order['street_address']) ?></div>
                    <div class="text-secondary"><?= esc($order['city']) ?>, <?= esc($order['province']) ?> <?= esc($order['postal_code']) ?></div>
                    <div class="text-secondary mt-2"><i class="bi bi-telephone me-1 text-primary"></i> <?= esc($order['recipient_phone']) ?></div>
                </div>
            </div>

            <?php if ($order['status'] === 'delivered' && ! $existingReturn): ?>
            <div class="card border-0 shadow-sm rounded-4 p-4 bg-white">
                <h6 class="fw-bold mb-2" style="color: #0F172A;"><i class="bi bi-arrow-counterclockwise text-danger me-1"></i> Request return / refund</h6>
                <p class="small text-secondary mb-3">Submit here. After admin approval, your wallet is credited and the seller/admin wallet is cut for the same goods.</p>
                <form action="<?= site_url('account/orders/' . $order['id'] . '/return') ?>" method="POST" enctype="multipart/form-data">
                    <?= csrf_field() ?>
                    <div class="mb-3">
                        <label class="form-label small fw-bold">Reason</label>
                        <select name="reason" class="form-select" required>
                            <option value="Defective or Damaged Item">Defective or Damaged Item</option>
                            <option value="Wrong Item Delivered">Wrong Item Delivered</option>
                            <option value="Item Does Not Match Description">Item Does Not Match Description</option>
                            <option value="Quality Below Expectations">Quality Below Expectations</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-bold">Details</label>
                        <textarea name="customer_note" class="form-control" rows="3" placeholder="What went wrong with this parcel?" required></textarea>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-bold">Photo (optional)</label>
                        <input type="file" name="return_photo" class="form-control" accept="image/*">
                    </div>
                    <div class="alert alert-light border small mb-3">
                        Estimated refund: Rs. <?= number_format($order['final_payable'] > 0 ? $order['final_payable'] : $order['total_amount'], 2) ?>
                    </div>
                    <button type="submit" class="btn btn-danger rounded-pill w-100">Submit return request</button>
                </form>
            </div>
            <?php elseif ($existingReturn): ?>
            <div class="card border-0 shadow-sm rounded-4 p-4 bg-white">
                <h6 class="fw-bold mb-2">Return request</h6>
                <p class="small mb-1">Status: <strong><?= esc(ucfirst($existingReturn['status'])) ?></strong></p>
                <p class="small text-secondary mb-0"><?= esc($existingReturn['reason']) ?></p>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>
<?= $this->endSection() ?>

<?= $this->section('modals') ?>
<?php if ($order['status'] === 'delivered'): ?>
    <?php foreach ($order['items'] as $item): ?>
        <div class="modal fade" id="reviewModal_<?= $item['id'] ?>" tabindex="-1">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content rounded-4 border-0">
                    <div class="modal-header">
                        <h5 class="modal-title fw-bold">Review: <?= esc($item['product_name']) ?></h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <form action="<?= site_url('account/orders/' . $order['id'] . '/review') ?>" method="POST" enctype="multipart/form-data">
                        <?= csrf_field() ?>
                        <input type="hidden" name="product_id" value="<?= $item['product_id'] ?>">
                        <div class="modal-body">
                            <div class="mb-3">
                                <label class="form-label small fw-bold">Your Rating</label>
                                <select name="rating" class="form-select">
                                    <option value="5">5 Stars - Outstanding Quality</option>
                                    <option value="4">4 Stars - Very Satisfied</option>
                                    <option value="3">3 Stars - Average Quality</option>
                                    <option value="2">2 Stars - Needs Improvement</option>
                                    <option value="1">1 Star - Unsatisfactory</option>
                                </select>
                            </div>
                            <div class="mb-3">
                                <label class="form-label small fw-bold">Your Feedback</label>
                                <textarea name="comment" class="form-control" rows="3" required></textarea>
                            </div>
                            <div class="mb-3">
                                <label class="form-label small fw-bold">Photo (optional)</label>
                                <input type="file" name="review_image" class="form-control" accept="image/*">
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-light rounded-pill" data-bs-dismiss="modal">Cancel</button>
                            <button type="submit" class="btn btn-sol-primary rounded-pill px-4 fw-bold">Submit Review</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    <?php endforeach; ?>
<?php endif; ?>
<?= $this->endSection() ?>
