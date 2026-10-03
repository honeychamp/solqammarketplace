<?= $this->extend('layouts/main') ?>

<?= $this->section('content') ?>
<div class="container py-4">
    <div class="d-flex flex-wrap justify-content-between align-items-center mb-4 gap-2">
        <div>
            <h3 class="fw-bold mb-1" style="color: #0F172A;"><i class="bi bi-box-seam me-2 text-primary"></i> My Orders</h3>
            <p class="text-secondary small mb-0">Track shipments, verify delivery status, and request return refunds.</p>
        </div>
        <a href="<?= site_url('shop') ?>" class="btn btn-sol-outline btn-sm rounded-pill px-3">
            <i class="bi bi-bag-plus me-1"></i> Continue Shopping
        </a>
    </div>

    <?php if (empty($orders)): ?>
        <div class="card border-0 shadow-sm rounded-4 p-5 text-center bg-white">
            <div class="d-inline-flex p-4 rounded-circle mx-auto mb-3" style="background: rgba(11, 48, 230, 0.08); color: var(--sol-primary);">
                <i class="bi bi-bag-x fs-1"></i>
            </div>
            <h4 class="fw-bold text-dark mb-1">No orders placed yet</h4>
            <p class="text-secondary small mb-4">Prepaid orders earn the cashback listed on each product instantly; COD after delivery.</p>
            <div>
                <a href="<?= site_url('shop') ?>" class="btn btn-sol-primary px-4 py-2 rounded-pill fw-bold shadow-sm">
                    Explore Marketplace <i class="bi bi-arrow-right ms-1"></i>
                </a>
            </div>
        </div>
    <?php else: ?>
        <div class="row g-3">
            <?php foreach ($orders as $o): ?>
                <?php
                $statusBadgeClass = match($o['status']) {
                    'delivered' => 'bg-success text-white',
                    'shipped'   => 'bg-primary text-white',
                    'confirmed' => 'bg-info text-dark',
                    'placed'    => 'bg-warning text-dark',
                    'cancelled' => 'bg-danger text-white',
                    'returned'  => 'bg-dark text-white',
                    'undelivered' => 'bg-danger text-white',
                    default     => 'bg-secondary text-white',
                };
                ?>
                <div class="col-12">
                    <div class="card border-0 shadow-sm rounded-4 p-3 p-md-4 bg-white">
                        <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 border-bottom pb-3 mb-3">
                            <div class="d-flex align-items-center gap-2">
                                <span class="text-muted small">Order</span>
                                <strong class="text-primary fs-6 font-monospace">#<?= esc($o['order_number']) ?></strong>
                                <span class="badge <?= $statusBadgeClass ?> rounded-pill px-2.5 py-1 text-uppercase fw-semibold" style="font-size: 0.72rem; letter-spacing: 0.5px;">
                                    <?= esc($o['status']) ?>
                                </span>
                            </div>
                            <div class="text-muted small">
                                <i class="bi bi-clock me-1"></i> Placed on <?= date('d M Y, h:i A', strtotime($o['created_at'])) ?>
                            </div>
                        </div>

                        <div class="row align-items-center g-3">
                            <div class="col-md-4">
                                <div class="small text-muted text-uppercase" style="font-size: 0.72rem; letter-spacing: 0.5px;">Final Payable</div>
                                <div class="fs-5 fw-bold text-dark">Rs. <?= number_format($o['final_payable'], 2) ?></div>
                                <?php if ($o['wallet_amount_used'] > 0): ?>
                                    <small class="text-success fw-semibold"><i class="bi bi-wallet2 me-1"></i> Paid Rs. <?= number_format($o['wallet_amount_used'], 2) ?> from Wallet</small>
                                <?php endif; ?>
                            </div>
                            <div class="col-md-5">
                                <div class="small text-muted text-uppercase" style="font-size: 0.72rem; letter-spacing: 0.5px;">Delivery Tracker</div>
                                <div class="fw-semibold text-dark">
                                    <?php if ($o['status'] === 'delivered'): ?>
                                        <span class="text-success"><i class="bi bi-check-circle-fill me-1"></i> Delivered on <?= date('d M Y', strtotime($o['delivery_date'] ?? $o['updated_at'])) ?></span>
                                    <?php elseif ($o['status'] === 'shipped'): ?>
                                        <span class="text-primary"><i class="bi bi-truck me-1"></i> Dispatched &amp; In Transit</span>
                                    <?php elseif ($o['status'] === 'confirmed'): ?>
                                        <span class="text-info"><i class="bi bi-check2-circle me-1"></i> Confirmed by Seller</span>
                                    <?php elseif ($o['status'] === 'undelivered'): ?>
                                        <span class="text-danger"><i class="bi bi-x-octagon me-1"></i> Not received — courier fee charged to wallet</span>
                                    <?php elseif ($o['status'] === 'placed'): ?>
                                        <span class="text-warning"><i class="bi bi-hourglass-split me-1"></i> Order Placed &bull; Awaiting Seller Confirmation</span>
                                    <?php else: ?>
                                        <?= ucfirst($o['status']) ?>
                                    <?php endif; ?>
                                </div>
                            </div>
                            <div class="col-md-3 text-md-end">
                                <a href="<?= site_url('account/orders/' . $o['id']) ?>" class="btn btn-sm btn-outline-primary rounded-pill px-4 fw-semibold">
                                    View Details <i class="bi bi-chevron-right ms-1"></i>
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>
<?= $this->endSection() ?>
