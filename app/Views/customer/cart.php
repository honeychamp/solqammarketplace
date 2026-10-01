<?= $this->extend('layouts/main') ?>

<?= $this->section('content') ?>
<div class="container py-4">
    <div class="ck-hero">
        <div>
            <div class="sf-eyebrow mb-1">Your bag</div>
            <h2 class="fw-bold text-dark mb-0">Shopping cart</h2>
        </div>
        <div class="ck-steps">
            <span class="ck-step is-active"><span class="ck-step-num">1</span> Cart</span>
            <span class="ck-step"><span class="ck-step-num">2</span> Address &amp; pay</span>
            <span class="ck-step"><span class="ck-step-num">3</span> Place order</span>
        </div>
    </div>

    <?php if (empty($items)): ?>
        <div class="sf-panel p-5 text-center">
            <i class="bi bi-cart-x fs-1 text-muted mb-3 d-block"></i>
            <h4 class="fw-bold text-dark">Your shopping cart is empty</h4>
            <p class="text-muted small mb-4">Discover verified smartphones, fashion, and home goods. Cashback is listed on each product.</p>
            <div>
                <a href="<?= site_url('shop') ?>" class="btn btn-solqam px-4 rounded-pill">
                    <i class="bi bi-bag-plus-fill me-1"></i> Start Shopping
                </a>
            </div>
        </div>
    <?php else: ?>
        <div class="row g-4">
            <!-- Items List (Left) -->
            <div class="col-lg-8">
                <div class="sf-panel p-3 mb-3">
                    <div class="table-responsive">
                        <table class="table align-middle mb-0">
                            <thead class="table-light small text-uppercase">
                                <tr>
                                    <th>Item Details</th>
                                    <th>Unit Price</th>
                                    <th style="width: 140px;">Quantity</th>
                                    <th>Subtotal</th>
                                    <th></th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($items as $item): ?>
                                    <tr>
                                        <td>
                                            <div class="d-flex align-items-center">
                                                <img src="<?= esc($item['primary_image'] ?: base_url('assets/images/product-placeholder.svg')) ?>" class="rounded-3 me-3" style="width: 65px; height: 65px; object-fit: cover; border: 1px solid #E2E8F0;">
                                                <div>
                                                    <h6 class="mb-1 fw-bold text-dark">
                                                        <a href="<?= site_url('product/' . $item['product_id']) ?>" class="text-dark text-decoration-none">
                                                            <?= esc($item['product_name']) ?>
                                                        </a>
                                                    </h6>
                                                    <small class="text-muted"><i class="bi bi-shop text-solqam me-1"></i> <?= esc($item['store_name'] ?? 'Vendor') ?></small>
                                                    <div class="mt-1"><span class="cashback-inline"><?= esc(cashback_percent_label($item)) ?> Cashback</span></div>
                                                    <?php if (!empty($item['variant_label'])): ?>
                                                        <div class="small text-solqam"><?= esc($item['variant_label']) ?></div>
                                                    <?php endif; ?>
                                                </div>
                                            </div>
                                        </td>
                                        <td class="fw-bold text-dark">
                                            Rs. <?= number_format($item['unit_price'], 0) ?>
                                        </td>
                                        <td>
                                            <form action="<?= site_url('cart/update') ?>" method="POST" class="d-flex align-items-center gap-1">
                                                <?= csrf_field() ?>
                                                <input type="hidden" name="item_id" value="<?= $item['id'] ?>">
                                                <input type="number" name="quantity" class="form-control form-control-sm text-center fw-bold rounded-2" value="<?= $item['quantity'] ?>" min="1" max="<?= $item['stock_available'] ?>" style="width: 65px;">
                                                <button type="submit" class="btn btn-outline-secondary btn-sm" title="Update"><i class="bi bi-arrow-repeat"></i></button>
                                            </form>
                                        </td>
                                        <td class="fw-bold text-solqam fs-6">
                                            Rs. <?= number_format($item['unit_price'] * $item['quantity'], 0) ?>
                                        </td>
                                        <td>
                                            <a href="<?= site_url('cart/remove/' . $item['id']) ?>" class="btn btn-sm text-danger" title="Remove Item" onclick="return confirm('Remove this item from your cart?');">
                                                <i class="bi bi-trash fs-5"></i>
                                            </a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

                <div class="d-flex justify-content-between align-items-center">
                    <a href="<?= site_url('shop') ?>" class="text-solqam text-decoration-none fw-bold small">
                        <i class="bi bi-arrow-left me-1"></i> Continue Shopping
                    </a>
                </div>
            </div>

            <!-- Order Summary (Right) -->
            <div class="col-lg-4">
                <div class="sf-panel ck-summary p-4 cart-sticky-summary">
                    <h5 class="fw-bold text-dark mb-3 border-bottom pb-2">Order Summary</h5>

                    <div class="d-flex justify-content-between mb-2 small">
                        <span class="text-secondary">Subtotal (<?= count($items) ?> items)</span>
                        <span class="fw-bold text-dark">Rs. <?= number_format($subtotal, 2) ?></span>
                    </div>

                    <div class="d-flex justify-content-between mb-2 small">
                        <span class="text-secondary">Delivery (city-wise)</span>
                        <span class="fw-bold text-dark"><?= esc(delivery_hint()['label']) ?></span>
                    </div>
                    <p class="small text-muted mb-2">Exact charge applies at checkout from Admin city rates (Lahore, Rawalpindi, …). Free only if that city has a “free above” amount and your cart reaches it.</p>

                    <div class="d-flex justify-content-between mb-3 small">
                        <span class="text-secondary">Wallet cashback (as listed)</span>
                        <span class="badge badge-wallet">Rs. <?= number_format($estimatedCashback ?? cart_cashback_total($items), 0) ?></span>
                    </div>

                    <hr>

                    <div class="d-flex justify-content-between mb-4">
                        <span class="fs-5 fw-bold text-dark">Estimated Total</span>
                        <span class="fs-5 fw-bold text-solqam">Rs. <?= number_format($subtotal, 2) ?></span>
                    </div>

                    <a href="<?= site_url('checkout') ?>" class="btn btn-solqam-accent btn-lg w-100 rounded-pill py-2 fw-bold shadow-sm">
                        Proceed to Checkout <i class="bi bi-arrow-right ms-1"></i>
                    </a>

                    <div class="text-center mt-3 small text-muted">
                        <i class="bi bi-shield-check text-success me-1"></i> Buyer protection · COD &amp; wallet
                    </div>
                </div>
            </div>
        </div>
    <?php endif; ?>
</div>
<?= $this->endSection() ?>
