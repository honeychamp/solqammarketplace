<?= $this->extend('layouts/main') ?>

<?= $this->section('content') ?>
<?php
$fieldErrors = session()->getFlashdata('errors') ?? [];
$formError   = session()->getFlashdata('error');
$keepAddr    = form_keep_raw('checkout_draft', 'address_id');
$addrErrKeys = ['recipient_name', 'phone', 'street_address', 'city', 'province', 'postal_code'];
$hasAddrErr  = array_intersect_key($fieldErrors, array_flip($addrErrKeys)) !== [];
$showNewAddr = empty($addresses) || $keepAddr === 'new' || $hasAddrErr;
$keepPay     = form_keep_raw('checkout_draft', 'payment_method');
$invalid = static function (string $key) use ($fieldErrors): string {
    return isset($fieldErrors[$key]) ? ' is-invalid' : '';
};
?>
<div class="container py-4">
    <div class="ck-hero">
        <div>
            <div class="sf-eyebrow mb-1">Secure checkout</div>
            <h3 class="fw-bold mb-1 text-dark">Delivery &amp; payment</h3>
            <div class="small text-muted"><i class="bi bi-lock-fill text-success me-1"></i> 256-bit encrypted · 7-day returns</div>
        </div>
        <div class="ck-steps">
            <span class="ck-step is-done"><span class="ck-step-num">1</span> Cart</span>
            <span class="ck-step is-active"><span class="ck-step-num">2</span> Address &amp; pay</span>
            <span class="ck-step"><span class="ck-step-num">3</span> Place order</span>
        </div>
    </div>

    <form action="<?= site_url('checkout') ?>" method="POST" id="checkoutForm" enctype="multipart/form-data" onsubmit="return validateCheckoutForm(event)" novalidate>
        <?= csrf_field() ?>

        <div id="checkoutAlert" class="alert alert-danger border-0 shadow-sm rounded-3 mb-4 <?= ($fieldErrors === [] && ! $formError) ? 'd-none' : '' ?>">
            <div class="fw-bold mb-2 d-flex align-items-center gap-2">
                <i class="bi bi-exclamation-triangle-fill fs-5"></i>
                <span>Please fix the following issues to continue:</span>
            </div>
            <ul class="mb-0 ps-3 small" id="checkoutAlertList">
                <?php foreach ($fieldErrors as $error): ?>
                    <li><?= esc($error) ?></li>
                <?php endforeach; ?>
                <?php if ($formError): ?>
                    <li><?= esc($formError) ?></li>
                <?php endif; ?>
            </ul>
        </div>

        <div class="row g-4">
            <!-- Left Column: Shipping & Payment -->
            <div class="col-lg-7">
                <!-- 1. Shipping Address -->
                <div class="sf-panel p-4 mb-4">
                    <h5 class="fw-bold mb-3 d-flex align-items-center" style="color: #0F172A;">
                        <span class="badge rounded-circle me-2 text-white" style="width:28px;height:28px;display:inline-flex;align-items:center;justify-content:center;background: var(--sol-primary);">1</span>
                        Delivery Address
                    </h5>

                    <?php if (!empty($addresses)): ?>
                        <div class="mb-3">
                            <label class="form-label small fw-semibold text-secondary">Choose from saved destinations in Pakistan:</label>
                            <?php foreach ($addresses as $index => $addr): ?>
                                <div class="form-check addr-tile mb-2">
                                    <input class="form-check-input ms-0 me-2" type="radio" name="address_id" id="addr_<?= $addr['id'] ?>" value="<?= $addr['id'] ?>" data-city="<?= esc($addr['city']) ?>" data-province="<?= esc($addr['province'] ?? '') ?>" <?= (! $showNewAddr && ($keepAddr === (string) $addr['id'] || ($keepAddr === '' && $index === 0))) ? 'checked' : '' ?> onchange="toggleNewAddress(false); refreshShipping();">
                                    <label class="form-check-label w-100 ps-1" for="addr_<?= $addr['id'] ?>">
                                        <div class="d-flex justify-content-between align-items-center">
                                            <strong class="text-dark"><?= esc($addr['recipient_name']) ?></strong>
                                            <span class="badge rounded-pill bg-light text-dark border font-monospace"><?= esc($addr['city']) ?></span>
                                        </div>
                                        <div class="small text-secondary mt-1"><?= esc($addr['street_address']) ?>, <?= esc($addr['province']) ?></div>
                                        <div class="small text-muted mt-1"><i class="bi bi-telephone me-1 text-primary"></i> <?= esc($addr['phone']) ?></div>
                                    </label>
                                </div>
                            <?php endforeach; ?>

                            <div class="form-check mt-3">
                                <input class="form-check-input" type="radio" name="address_id" id="addr_new" value="new" <?= $showNewAddr ? 'checked' : '' ?> onchange="toggleNewAddress(true); refreshShipping();">
                                <label class="form-check-label fw-bold" style="color: var(--sol-primary);" for="addr_new">
                                    <i class="bi bi-plus-circle me-1"></i> Deliver to a new address
                                </label>
                            </div>
                        </div>
                    <?php else: ?>
                        <input type="hidden" name="address_id" value="new">
                    <?php endif; ?>

                    <!-- New Address Form (Visible if no existing address or 'new' selected) -->
                    <div id="newAddressFields" class="<?= $showNewAddr ? '' : 'd-none' ?> border-top pt-3 mt-3">
                        <h6 class="fw-bold mb-3 text-secondary">New Destination Coordinates</h6>
                        <div class="row g-2 mb-2">
                            <div class="col-md-6">
                                <label class="form-label small fw-semibold text-muted">Recipient Full Name <span class="text-danger">*</span></label>
                                <input type="text" name="recipient_name" id="ck_recipient_name" class="form-control<?= $invalid('recipient_name') ?>" placeholder="Full name" value="<?= checkout_keep('recipient_name') ?>" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label small fw-semibold text-muted">Mobile Number (Active) <span class="text-danger">*</span></label>
                                <input type="text" name="phone" id="ck_phone" class="form-control<?= $invalid('phone') ?>" placeholder="03XXXXXXXXX" value="<?= checkout_keep('phone') ?>" required>
                            </div>
                        </div>
                        <div class="mb-2">
                            <label class="form-label small fw-semibold text-muted">Street / House / Apartment / Sector <span class="text-danger">*</span></label>
                            <textarea name="street_address" id="ck_street" class="form-control<?= $invalid('street_address') ?>" rows="2" placeholder="Full residential or office address" required><?= checkout_keep('street_address') ?></textarea>
                        </div>
                        <div class="row g-2">
                            <div class="col-md-4">
                                <label class="form-label small fw-semibold text-muted">City <span class="text-danger">*</span></label>
                                <?php
                                $zones = $shippingZones ?? [];
                                $keepCity = form_keep_raw('checkout_draft', 'city');
                                $keepCityOther = form_keep_raw('checkout_draft', 'city_other');
                                ?>
                                <?php if ($zones !== []): ?>
                                    <select name="city" id="checkoutCity" class="form-select<?= $invalid('city') ?>" onchange="refreshShipping()" required>
                                        <option value="">Select city</option>
                                        <?php foreach ($zones as $z): ?>
                                            <option value="<?= esc($z['city']) ?>" <?= $keepCity === (string) $z['city'] ? 'selected' : '' ?>><?= esc($z['city']) ?> — Rs. <?= number_format((float) $z['rate'], 0) ?></option>
                                        <?php endforeach; ?>
                                        <option value="__other" <?= $keepCity === '__other' ? 'selected' : '' ?>>Other city</option>
                                    </select>
                                    <input type="text" name="city_other" id="checkoutCityOther" class="form-control mt-2<?= $keepCity === '__other' ? '' : ' d-none' ?>" placeholder="Type city name" value="<?= checkout_keep('city_other') ?>" oninput="refreshShipping()">
                                <?php else: ?>
                                    <input type="text" name="city" id="checkoutCity" class="form-control<?= $invalid('city') ?>" placeholder="e.g. Lahore" value="<?= checkout_keep('city') ?>" oninput="refreshShipping()" required>
                                <?php endif; ?>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label small fw-semibold text-muted">Province <span class="text-danger">*</span></label>
                                <?php $keepProv = form_keep_raw('checkout_draft', 'province') ?: 'Punjab'; ?>
                                <select name="province" id="checkoutProvince" class="form-select<?= $invalid('province') ?>" onchange="refreshShipping()" required>
                                    <?php foreach (['Punjab', 'Sindh', 'Khyber Pakhtunkhwa', 'Balochistan', 'Islamabad Capital Territory', 'Gilgit-Baltistan', 'Azad Kashmir'] as $prov): ?>
                                        <option value="<?= esc($prov) ?>" <?= $keepProv === $prov ? 'selected' : '' ?>><?= $prov === 'Islamabad Capital Territory' ? 'Islamabad' : esc($prov) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label small fw-semibold text-muted">Postal Code <span class="text-danger">*</span></label>
                                <input type="text" name="postal_code" id="ck_postal" class="form-control<?= $invalid('postal_code') ?>" placeholder="e.g. 54000" value="<?= checkout_keep('postal_code') ?>" required>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- 2. Payment Method -->
                <div class="sf-panel p-4 mb-4">
                    <h5 class="fw-bold mb-3 d-flex align-items-center" style="color: #0F172A;">
                        <span class="badge rounded-circle me-2 text-white" style="width:28px;height:28px;display:inline-flex;align-items:center;justify-content:center;background: var(--sol-primary);">2</span>
                        Payment Method
                    </h5>

                    <div class="small fw-bold text-muted text-uppercase mb-2" style="letter-spacing:.08em;">Accepted methods</div>
                    <div class="mb-3"><?= view('customer/_payment_methods') ?></div>

                    <div id="walletCoversBox" class="alert alert-success d-none mb-0">
                        <i class="bi bi-check-circle-fill me-1"></i>
                        Wallet covers this order. Extra balance stays in your Solqam wallet. No other payment needed.
                    </div>

                    <div id="remainderPayBox">
                        <p class="small text-muted mb-3" id="remainderHint">Select how you want to pay.</p>
                        <div class="row g-3" id="standardPayOptions">
                            <div class="col-12">
                                <div class="form-check pay-tile">
                                    <input class="form-check-input ms-0 me-2" type="radio" name="payment_method" id="pay_cod" value="cod" <?= ($keepPay === '' || $keepPay === 'cod') ? 'checked' : '' ?>>
                                    <label class="form-check-label w-100 ps-1" for="pay_cod">
                                        <div class="fw-bold text-dark"><i class="bi bi-cash-stack text-success fs-5"></i> Cash on Delivery</div>
                                        <small class="text-secondary d-block mt-1">Pay cash when the parcel arrives. Wallet cashback after delivery.</small>
                                    </label>
                                </div>
                            </div>
                            <?php if (!empty($payfastReady)): ?>
                            <div class="col-12">
                                <div class="form-check pay-tile">
                                    <input class="form-check-input ms-0 me-2" type="radio" name="payment_method" id="pay_payfast" value="payfast" <?= $keepPay === 'payfast' ? 'checked' : '' ?>>
                                    <label class="form-check-label w-100 ps-1" for="pay_payfast">
                                        <div class="fw-bold text-dark"><i class="bi bi-shield-lock text-primary fs-5"></i> Pay online</div>
                                        <small class="text-secondary d-block mt-1">JazzCash, EasyPaisa, Visa, Mastercard, UnionPay and PayPak via PayFast.</small>
                                    </label>
                                </div>
                            </div>
                            <?php endif; ?>
                            <div class="col-12">
                                <div class="form-check pay-tile">
                                    <input class="form-check-input ms-0 me-2" type="radio" name="payment_method" id="pay_later" value="pay_later" <?= $keepPay === 'pay_later' ? 'checked' : '' ?>>
                                    <label class="form-check-label w-100 ps-1" for="pay_later">
                                        <div class="fw-bold text-dark"><i class="bi bi-calendar2-week text-warning fs-5"></i> Pay later (wallet shortfall)</div>
                                        <small class="text-secondary d-block mt-1">Wallet shortfall wallet mein minus dikhega jab tak baad mein pay na ho.</small>
                                    </label>
                                </div>
                            </div>
                        </div>
                        <div id="payLaterFields" class="d-none border rounded-3 p-3 mt-3 bg-white">
                            <h6 class="fw-bold mb-2">Pay later documents</h6>
                            <div class="row g-2">
                                <div class="col-md-6">
                                    <label class="form-label small">Full name</label>
                                    <input type="text" name="pay_later_name" class="form-control" value="<?= checkout_keep('pay_later_name') ?>">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label small">CNIC</label>
                                    <input type="text" name="pay_later_cnic" class="form-control" placeholder="xxxxx-xxxxxxx-x" value="<?= checkout_keep('pay_later_cnic') ?>">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label small">Phone</label>
                                    <input type="text" name="pay_later_phone" class="form-control" value="<?= checkout_keep('pay_later_phone') ?>">
                                </div>
                                <div class="col-12">
                                    <label class="form-label small">Address</label>
                                    <textarea name="pay_later_address" class="form-control" rows="2"><?= checkout_keep('pay_later_address') ?></textarea>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label small">CNIC front</label>
                                    <input type="file" name="cnic_front" class="form-control" accept=".jpg,.jpeg,.png,.webp,.pdf">
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label small">CNIC back</label>
                                    <input type="file" name="cnic_back" class="form-control" accept=".jpg,.jpeg,.png,.webp,.pdf">
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label small">Utility bill</label>
                                    <input type="file" name="utility_bill" class="form-control" accept=".jpg,.jpeg,.png,.webp,.pdf">
                                </div>
                            </div>
                        </div>
                    </div>
                    <input type="hidden" name="payment_method" id="pay_wallet_hidden" value="wallet" disabled>
                </div>

                <!-- 3. Delivery Notes -->
                <div class="sf-panel p-4">
                    <h6 class="fw-bold mb-2 text-dark">Special Delivery Notes (Optional)</h6>
                                    <textarea name="notes" class="form-control" rows="2" placeholder="Nearby landmark, gate security code, preferred delivery time, etc."><?= checkout_keep('notes') ?></textarea>
                </div>
            </div>

            <!-- Right Column: Order Summary & Ledger Wallet -->
            <div class="col-lg-5">
                <div class="sf-panel ck-summary p-4 cart-sticky-summary">
                    <h5 class="fw-bold mb-3 border-bottom pb-2" style="color: #0F172A;">Order Summary</h5>

                    <!-- Items Preview -->
                    <div class="mb-3" style="max-height: 220px; overflow-y: auto;">
                        <?php foreach ($items as $item): ?>
                            <div class="d-flex justify-content-between align-items-center mb-2.5 small">
                                <div class="text-truncate me-2" style="max-width: 220px;">
                                    <strong><?= $item['quantity'] ?>x</strong> <span class="text-dark"><?= esc($item['product_name']) ?></span>
                                    <div class="text-muted" style="font-size: 0.72rem;"><i class="bi bi-shop me-1 text-primary"></i> <?= esc($item['store_name'] ?? 'Vendor') ?></div>
                                    <div class="text-success" style="font-size: 0.72rem;"><?= esc(cashback_percent_label($item)) ?> cashback</div>
                                </div>
                                <div class="fw-bold text-dark font-monospace">
                                    Rs. <?= number_format($item['unit_price'] * $item['quantity'], 0) ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>

                    <hr>

                    <!-- Wallet Ledger Box -->
                    <div class="p-3 rounded-3 mb-3" style="background: linear-gradient(135deg, #F0F4FF 0%, #E0E7FF 100%); border: 1px solid #C7D2FE;">
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <div>
                                <i class="bi bi-wallet2 text-warning fs-5 me-1"></i>
                                <strong style="color: var(--sol-primary);">Solqam Cash Wallet</strong>
                            </div>
                            <span class="badge rounded-pill bg-white fw-bold shadow-xs <?= ($walletBalance ?? 0) < 0 ? 'text-danger' : '' ?>" style="color: var(--sol-primary);">
                                <?php if (($walletBalance ?? 0) < 0): ?>
                                    Minus: Rs. <?= number_format(abs((float) $walletBalance), 2) ?>
                                <?php else: ?>
                                    Available: Rs. <?= number_format($walletBalance, 2) ?>
                                <?php endif; ?>
                            </span>
                        </div>

                        <?php if (($walletDebt ?? 0) > 0): ?>
                            <div class="alert alert-danger py-2 px-3 small mb-2 mt-2">
                                Your wallet is in minus by <strong>Rs. <?= number_format((float) $walletDebt, 2) ?></strong> (unpaid courier fee). This remaining amount is added to this order.
                            </div>
                        <?php elseif ($walletBalance > 0): ?>
                            <div class="form-check mt-2">
                                <input class="form-check-input" type="checkbox" name="use_wallet" id="use_wallet" value="1" <?= form_keep_raw('checkout_draft', 'use_wallet') === '0' ? '' : 'checked' ?> onchange="calculatePayable()">
                                <label class="form-check-label small fw-bold text-dark" for="use_wallet">
                                    Use wallet first (up to Rs. <?= number_format($walletBalance, 2) ?>). Extra stays in wallet.
                                </label>
                            </div>
                            <small class="text-muted d-block mt-1" id="walletSplitHint"></small>
                        <?php else: ?>
                            <small class="text-muted d-block">No wallet balance yet. Prepaid orders get listed product cashback instantly; COD after delivery.</small>
                        <?php endif; ?>
                    </div>

                    <!-- Totals Breakdown -->
                    <label class="form-label small fw-bold">Voucher / Promo Code</label>
                    <div class="input-group mb-3">
                        <input type="text" name="coupon_code" class="form-control" placeholder="WELCOME10" value="<?= checkout_keep('coupon_code') !== '' ? checkout_keep('coupon_code') : esc($couponCode ?? '') ?>">
                        <button class="btn btn-outline-primary" type="submit" formaction="<?= site_url('checkout/coupon') ?>">Apply</button>
                    </div>

                    <div class="d-flex justify-content-between mb-2">
                        <span class="text-secondary">Subtotal</span>
                        <span class="fw-bold text-dark font-monospace">Rs. <?= number_format($subtotal, 2) ?></span>
                    </div>
                    <?php if (!empty($couponDiscount)): ?>
                    <div class="d-flex justify-content-between mb-2 text-success">
                        <span>Voucher <?= esc($couponCode) ?></span>
                        <span class="fw-bold font-monospace">- Rs. <?= number_format($couponDiscount, 2) ?></span>
                    </div>
                    <?php endif; ?>
                    <?php $needsCity = ! empty($shippingQuote['needs_city']); ?>
                    <div class="d-flex justify-content-between mb-2">
                        <span class="text-secondary">Shipping <span id="shipEta"><?= $needsCity ? '' : esc(shipping_eta_label($shippingQuote['eta_days'] ?? '')) ?></span></span>
                        <span class="fw-bold font-monospace" id="shipAmount">
                            <?php if ($needsCity): ?>
                                <span class="text-muted">Select city</span>
                            <?php elseif (!empty($shippingQuote['is_free'])): ?>
                                <span class="text-success">FREE</span>
                            <?php else: ?>
                                Rs. <?= number_format($shippingQuote['amount'] ?? 0, 2) ?>
                            <?php endif; ?>
                        </span>
                    </div>

                    <div id="arrearsRow" class="d-flex justify-content-between mb-2 text-danger <?= empty($walletDebt) ? 'd-none' : '' ?>">
                        <span>Unpaid courier fee (wallet minus)</span>
                        <span class="fw-bold font-monospace">Rs. <span id="arrearsAmount"><?= number_format((float) ($walletDebt ?? 0), 2) ?></span></span>
                    </div>

                    <div id="walletDeductionRow" class="d-flex justify-content-between mb-2 text-success d-none">
                        <span>Wallet Ledger Deduction</span>
                        <span class="fw-bold font-monospace">- Rs. <span id="walletDiscountAmount">0.00</span></span>
                    </div>

                    <div class="d-flex justify-content-between mb-2">
                        <span class="text-secondary">Doorstep delivery</span>
                        <span class="text-muted small" id="shipNote"><?php
                            if (!empty($shippingQuote['needs_city'])) {
                                echo 'Select a city to see delivery charges and days';
                            } elseif (!empty($shippingQuote['is_free']) && (float) ($shippingQuote['free_above'] ?? 0) > 0) {
                                echo 'Free over Rs. ' . number_format($shippingQuote['free_above'], 0);
                            } elseif (!empty($shippingQuote['matched'])) {
                                echo 'Admin rate for ' . esc($shippingQuote['matched']);
                            } elseif (!empty($shippingQuote['unlisted'])) {
                                echo 'City not in admin list — highest listed delivery rate';
                            } else {
                                echo 'Select a city to see delivery charges and days';
                            }
                        ?></span>
                    </div>

                    <div class="d-flex justify-content-between mb-3">
                        <span class="text-secondary">Estimated cashback</span>
                        <span class="badge bg-success-subtle text-success border border-success-subtle rounded-pill font-monospace">+ Rs. <?= number_format($estimatedCashback ?? cart_cashback_total($items ?? []), 0) ?></span>
                    </div>

                    <hr>

                    <div class="d-flex justify-content-between mb-2">
                        <span class="fs-5 fw-bold text-dark">Final Payable</span>
                        <span class="fs-5 fw-bold text-primary font-monospace">Rs. <span id="finalPayableDisplay"><?= number_format($subtotal, 2) ?></span></span>
                    </div>
                    <p class="small text-muted mb-4" id="remainingPayNote"></p>

                    <button type="submit" class="btn btn-sol-primary btn-lg w-100 rounded-pill py-2.5 fw-bold shadow-sm">
                        Confirm &amp; Place Order <i class="bi bi-shield-check ms-1"></i>
                    </button>

                    <div class="text-center mt-3 text-muted small" style="font-size: 0.74rem;">
                        <i class="bi bi-shield-lock me-1"></i> 7-Day Free Returns &bull; 100% Genuine Products
                    </div>
                </div>
            </div>
        </div>
    </form>
</div>
<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<script>
    function toggleNewAddress(show) {
        const fields = document.getElementById('newAddressFields');
        if (!fields) return;
        if (show) {
            fields.classList.remove('d-none');
        } else {
            fields.classList.add('d-none');
        }
    }

    const subtotal = <?= (float) $subtotal ?>;
    const walletBalance = <?= (float) $walletBalance ?>;
    const walletDebt = <?= (float) ($walletDebt ?? 0) ?>;
    let shipping = <?= !empty($shippingQuote['needs_city']) ? 0 : (float) ($shippingQuote['amount'] ?? 0) ?>;
    const couponDiscount = <?= (float) ($couponDiscount ?? 0) ?>;
    const quoteUrl = <?= json_encode(site_url('checkout/shipping-quote')) ?>;

    function currentCity() {
        const selected = document.querySelector('input[name="address_id"]:checked');
        if (selected && selected.value !== 'new') {
            return { city: selected.getAttribute('data-city') || '', province: selected.getAttribute('data-province') || '', addressId: selected.value };
        }
        const sel = document.getElementById('checkoutCity');
        let city = sel ? sel.value : '';
        const other = document.getElementById('checkoutCityOther');
        if (city === '__other' && other) {
            other.classList.remove('d-none');
            city = other.value;
        } else if (other) {
            other.classList.add('d-none');
        }
        const prov = document.getElementById('checkoutProvince');
        return { city, province: prov ? prov.value : '', addressId: '' };
    }

    function formatEta(eta) {
        const t = String(eta || '').trim();
        if (!t) return '';
        if (/day/i.test(t)) return '(' + t + ')';
        return '(' + t + ' days)';
    }

    function applyShippingQuote(q) {
        const needs = !!q.needs_city;
        shipping = needs ? 0 : parseFloat(q.amount || 0);
        const amt = document.getElementById('shipAmount');
        const eta = document.getElementById('shipEta');
        const note = document.getElementById('shipNote');
        if (amt) {
            amt.innerHTML = needs
                ? '<span class="text-muted">Select city</span>'
                : (q.is_free ? '<span class="text-success">FREE</span>' : ('Rs. ' + shipping.toFixed(2)));
        }
        if (eta) eta.textContent = needs ? '' : formatEta(q.eta_days);
        if (note) {
            if (needs) {
                note.textContent = 'Select a city to see delivery charges and days';
            } else if (q.is_free && Number(q.free_above || 0) > 0) {
                note.textContent = 'Free over Rs. ' + Number(q.free_above).toLocaleString();
            } else if (q.matched) {
                note.textContent = 'Admin rate for ' + q.matched;
            } else if (q.unlisted) {
                note.textContent = 'City not in admin list — highest listed delivery rate';
            } else {
                note.textContent = 'Select a city to see delivery charges and days';
            }
        }
        calculatePayable();
    }

    function refreshShipping() {
        const ctx = currentCity();
        const city = (ctx.city || '').trim();
        if (!ctx.addressId && !city) {
            applyShippingQuote({ needs_city: true, amount: 0, eta_days: '', is_free: false });
            return;
        }
        const params = new URLSearchParams();
        if (ctx.addressId) params.set('address_id', ctx.addressId);
        if (city) params.set('city', city);
        if (ctx.province) params.set('province', ctx.province);
        fetch(quoteUrl + '?' + params.toString(), { headers: { 'X-Requested-With': 'XMLHttpRequest' } })
            .then(r => r.json())
            .then(q => applyShippingQuote(q))
            .catch(() => {});
    }

    function selectedPayMethod() {
        const el = document.querySelector('input[name="payment_method"]:checked:not(:disabled)');
        return el ? el.value : 'cod';
    }

    function calculatePayable() {
        const useWalletCheckbox = document.getElementById('use_wallet');
        const deductionRow = document.getElementById('walletDeductionRow');
        const discountAmountSpan = document.getElementById('walletDiscountAmount');
        const payableDisplay = document.getElementById('finalPayableDisplay');
        const afterCoupon = Math.max(0, subtotal - couponDiscount) + shipping;
        const remainderBox = document.getElementById('remainderPayBox');
        const coversBox = document.getElementById('walletCoversBox');
        const hint = document.getElementById('walletSplitHint');
        const remainderHint = document.getElementById('remainderHint');
        const hiddenWallet = document.getElementById('pay_wallet_hidden');
        const radios = document.querySelectorAll('#remainderPayBox input[type="radio"][name="payment_method"]');
        const remainingNote = document.getElementById('remainingPayNote');

        let payable = afterCoupon + walletDebt;
        let usedWallet = 0;
        if (useWalletCheckbox && useWalletCheckbox.checked && walletBalance > 0) {
            usedWallet = Math.min(walletBalance, payable);
            payable = Math.max(0, payable - usedWallet);
            if (deductionRow) deductionRow.classList.remove('d-none');
            if (discountAmountSpan) discountAmountSpan.textContent = usedWallet.toFixed(2);
            if (hint) {
                const leftover = Math.max(0, walletBalance - usedWallet);
                hint.textContent = payable <= 0
                    ? ('Rs. ' + usedWallet.toFixed(0) + ' will be taken from wallet. Rs. ' + leftover.toFixed(0) + ' stays in wallet.')
                    : ('Rs. ' + usedWallet.toFixed(0) + ' from wallet. Choose a method below for the remaining Rs. ' + payable.toFixed(0) + '.');
            }
        } else {
            if (deductionRow) deductionRow.classList.add('d-none');
            if (hint) hint.textContent = '';
        }

        payableDisplay.textContent = payable.toFixed(2);
        if (remainingNote) {
            remainingNote.textContent = walletDebt > 0
                ? ('Remaining to pay: Rs. ' + payable.toFixed(2) + ' (this order + unpaid courier Rs. ' + walletDebt.toFixed(2) + ').')
                : '';
        }

        const walletCovers = useWalletCheckbox && useWalletCheckbox.checked && payable <= 0;
        const walletShort = useWalletCheckbox && useWalletCheckbox.checked && payable > 0;
        if (coversBox && remainderBox && hiddenWallet) {
            if (walletCovers) {
                coversBox.classList.remove('d-none');
                remainderBox.classList.add('d-none');
                hiddenWallet.disabled = false;
                radios.forEach(function (r) { r.disabled = true; });
            } else {
                coversBox.classList.add('d-none');
                remainderBox.classList.remove('d-none');
                hiddenWallet.disabled = true;
                radios.forEach(function (r) { r.disabled = false; });
                if (remainderHint) {
                    remainderHint.textContent = walletShort
                        ? ('Wallet covers Rs. ' + usedWallet.toFixed(0) + '. Remaining Rs. ' + payable.toFixed(0) + '.')
                        : 'Select how you want to pay.';
                }
            }
        }
    }

    function togglePayLater() {
        const box = document.getElementById('payLaterFields');
        if (!box) return;
        box.classList.toggle('d-none', selectedPayMethod() !== 'pay_later');
    }
    document.querySelectorAll('input[name="payment_method"]').forEach(function (el) {
        el.addEventListener('change', togglePayLater);
    });

    function showCheckoutErrors(errors) {
        const box = document.getElementById('checkoutAlert');
        const list = document.getElementById('checkoutAlertList');
        if (!box || !list) return;
        list.innerHTML = '';
        errors.forEach(function (err) {
            const li = document.createElement('li');
            li.textContent = err;
            list.appendChild(li);
        });
        box.classList.remove('d-none');
        box.scrollIntoView({ behavior: 'smooth', block: 'start' });
    }

    function validateCheckoutForm(e) {
        const submitter = e.submitter;
        if (submitter && submitter.getAttribute('formaction')) {
            return true;
        }
        const selected = document.querySelector('input[name="address_id"]:checked');
        const isNew = !selected || selected.value === 'new';
        if (!isNew) return true;

        const errors = [];
        const nameEl = document.getElementById('ck_recipient_name');
        const phoneEl = document.getElementById('ck_phone');
        const streetEl = document.getElementById('ck_street');
        const cityEl = document.getElementById('checkoutCity');
        const cityOther = document.getElementById('checkoutCityOther');
        const provEl = document.getElementById('checkoutProvince');
        const postalEl = document.getElementById('ck_postal');

        [nameEl, phoneEl, streetEl, cityEl, cityOther, provEl, postalEl].forEach(function (el) {
            if (el) el.classList.remove('is-invalid');
        });

        if (!nameEl || !nameEl.value.trim() || nameEl.value.trim().length < 2) {
            errors.push('Recipient full name is required.');
            if (nameEl) nameEl.classList.add('is-invalid');
        }
        const digits = (phoneEl ? phoneEl.value : '').replace(/\D/g, '');
        if (digits.length < 10 || digits.length > 12) {
            errors.push('Enter a valid mobile number (e.g. 03XXXXXXXXX).');
            if (phoneEl) phoneEl.classList.add('is-invalid');
        }
        if (!streetEl || !streetEl.value.trim() || streetEl.value.trim().length < 8) {
            errors.push('Street / house address is required.');
            if (streetEl) streetEl.classList.add('is-invalid');
        }
        let city = cityEl ? cityEl.value.trim() : '';
        if (city === '__other') {
            city = cityOther ? cityOther.value.trim() : '';
        }
        if (!city) {
            errors.push('City is required.');
            if (cityEl) cityEl.classList.add('is-invalid');
        }
        if (!provEl || !provEl.value.trim()) {
            errors.push('Province is required.');
            if (provEl) provEl.classList.add('is-invalid');
        }
        if (!postalEl || !/^[0-9]{4,6}$/.test(postalEl.value.trim())) {
            errors.push('Postal code is required (4–6 digits).');
            if (postalEl) postalEl.classList.add('is-invalid');
        }

        if (errors.length > 0) {
            e.preventDefault();
            showCheckoutErrors(errors);
            return false;
        }
        return true;
    }

    calculatePayable();
    togglePayLater();
    refreshShipping();
    const alertBox = document.getElementById('checkoutAlert');
    if (alertBox && !alertBox.classList.contains('d-none')) {
        alertBox.scrollIntoView({ behavior: 'smooth', block: 'start' });
    }
</script>
<?= $this->endSection() ?>
