<?= $this->extend('layouts/main') ?>

<?= $this->section('content') ?>
<?php
$fieldErrors = session()->getFlashdata('errors') ?? [];
$formError   = session()->getFlashdata('error');
$invalid = static function (string $key) use ($fieldErrors): string {
    return isset($fieldErrors[$key]) ? ' is-invalid' : '';
};
?>
<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-md-8 col-lg-7">
            <div class="card shadow-sm border-0 rounded-4 p-4 p-md-5 bg-white">
                <div class="text-center mb-4">
                    <img src="<?= base_url('assets/images/solqam-logo.svg') ?>?v=20261005c" alt="Solqam Marketplace" height="72" class="mb-3">
                    <h3 class="fw-bold text-dark mb-1">Join SOLQAM Marketplace</h3>
                    <p class="text-secondary small">Pakistan's premier multi-vendor e-commerce platform</p>
                </div>

                <!-- Role Selection Tabs -->
                <ul class="nav nav-pills nav-fill mb-4 p-1.5 rounded-pill" style="background: #F1F5F9;" id="registerTab" role="tablist">
                    <li class="nav-item" role="presentation">
                        <button class="nav-link rounded-pill fw-semibold py-2.5 <?= $defaultRole === 'customer' ? 'active shadow-sm' : '' ?>" id="customer-tab" data-bs-toggle="tab" data-bs-target="#customer-pane" type="button" role="tab">
                            <i class="bi bi-person-fill me-1"></i> Customer Account
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link rounded-pill fw-semibold py-2.5 <?= $defaultRole === 'seller' ? 'active shadow-sm' : '' ?>" id="seller-tab" data-bs-toggle="tab" data-bs-target="#seller-pane" type="button" role="tab">
                            <i class="bi bi-shop-window me-1"></i> Sell on Solqam
                        </button>
                    </li>
                </ul>

                <!-- Prominent Validation Alert Box at the Top -->
                <div id="validationAlert" class="alert alert-danger border-0 shadow-sm rounded-3 mb-4 <?= ($fieldErrors === [] && ! $formError) ? 'd-none' : '' ?>">
                    <div class="fw-bold mb-2 d-flex align-items-center gap-2">
                        <i class="bi bi-exclamation-triangle-fill text-danger fs-5 flex-shrink-0"></i>
                        <span>Please fix the following issues to continue:</span>
                    </div>
                    <ul class="mb-0 ps-3 small" id="validationAlertList">
                        <?php foreach ($fieldErrors as $error): ?>
                            <li><?= esc($error) ?></li>
                        <?php endforeach; ?>
                        <?php if ($formError): ?>
                            <li><?= esc($formError) ?></li>
                        <?php endif; ?>
                    </ul>
                </div>

                <div class="tab-content" id="registerTabContent">
                    <!-- Customer Form -->
                    <div class="tab-pane fade <?= $defaultRole === 'customer' ? 'show active' : '' ?>" id="customer-pane" role="tabpanel">
                        <form id="customerForm" action="<?= site_url('register') ?>" method="POST" onsubmit="return validateCustomerForm(event)" novalidate>
                            <?= csrf_field() ?>
                            <input type="hidden" name="role" value="customer">

                            <div class="mb-3">
                                <label class="form-label fw-semibold small text-muted">Full Name <span class="text-danger">*</span></label>
                                <input type="text" name="name" id="customer_name" class="form-control<?= $invalid('name') ?>" placeholder="Full name" value="<?= register_keep('name') ?>" required>
                            </div>

                            <div class="row g-3 mb-3">
                                <div class="col-md-6">
                                    <label class="form-label fw-semibold small text-muted">Email Address <span class="text-danger">*</span></label>
                                    <input type="email" name="email" id="customer_email" class="form-control<?= $invalid('email') ?>" placeholder="name@domain.com" value="<?= register_keep('email') ?>" required>
                                    <div class="form-text text-muted" style="font-size: 0.75rem;">Must be unique &amp; not already registered.</div>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label fw-semibold small text-muted">Mobile Number (10 Digits) <span class="text-danger">*</span></label>
                                    <div class="input-group">
                                        <span class="input-group-text bg-light fw-bold text-dark border-end-0" style="font-size: 0.92rem;">+92</span>
                                        <input type="tel" name="phone" id="customer_phone" class="form-control border-start-0 ps-1 font-monospace<?= $invalid('phone') ?>" placeholder="3001234567" maxlength="10" inputmode="numeric" oninput="this.value = this.value.replace(/[^0-9]/g, '').slice(0, 10);" value="<?= register_keep('phone') ?>" required>
                                    </div>
                                    <div class="form-text text-muted" style="font-size: 0.75rem;">Enter 10 digits only without leading 0 (e.g. 3001234567).</div>
                                </div>
                            </div>

                            <div class="mb-4">
                                <label class="form-label fw-semibold small text-muted">Password (Min 6 characters) <span class="text-danger">*</span></label>
                                <div class="input-group">
                                    <input type="password" name="password" id="customer_password" class="form-control border-end-0<?= $invalid('password') ?>" placeholder="••••••••" minlength="6" value="<?= register_keep('password') ?>" required>
                                    <button class="btn btn-outline-secondary border-start-0 bg-transparent text-muted" type="button" onclick="togglePasswordVisibility('customer_password', 'customerPasswordEye')">
                                        <i class="bi bi-eye" id="customerPasswordEye"></i>
                                    </button>
                                </div>
                                <div class="form-text text-muted" style="font-size: 0.75rem;">Must be at least 6 characters. Numbers, letters, and symbols (@, #, $, %, etc.) are supported.</div>
                            </div>

                            <button type="submit" id="customerSubmitBtn" class="btn btn-sol-primary w-100 py-2.5 rounded-pill fw-bold shadow-sm">
                                Create Customer Account &amp; Verify Email OTP <i class="bi bi-arrow-right ms-1"></i>
                            </button>
                        </form>
                    </div>

                    <!-- Seller Form -->
                    <div class="tab-pane fade <?= $defaultRole === 'seller' ? 'show active' : '' ?>" id="seller-pane" role="tabpanel">
                        <form id="sellerForm" action="<?= site_url('register') ?>" method="POST" onsubmit="return validateSellerForm(event)" novalidate>
                            <?= csrf_field() ?>
                            <input type="hidden" name="role" value="seller">

                            <div class="row g-3 mb-3">
                                <div class="col-md-6">
                                    <label class="form-label fw-semibold small text-muted">Owner Full Name <span class="text-danger">*</span></label>
                                    <input type="text" name="name" id="seller_name" class="form-control<?= $invalid('name') ?>" placeholder="Owner full name" value="<?= register_keep('name') ?>" required>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label fw-semibold small text-muted">Store / Brand Name <span class="text-danger">*</span></label>
                                    <input type="text" name="store_name" id="seller_store_name" class="form-control<?= $invalid('store_name') ?>" placeholder="Store name" value="<?= register_keep('store_name') ?>" required>
                                </div>
                            </div>

                            <div class="row g-3 mb-3">
                                <div class="col-md-6">
                                    <label class="form-label fw-semibold small text-muted">Business Email Address <span class="text-danger">*</span></label>
                                    <input type="email" name="email" id="seller_email" class="form-control<?= $invalid('email') ?>" placeholder="store@domain.com" value="<?= register_keep('email') ?>" required>
                                    <div class="form-text text-muted" style="font-size: 0.75rem;">Must be unique &amp; not already registered.</div>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label fw-semibold small text-muted">Mobile / WhatsApp Number (10 Digits) <span class="text-danger">*</span></label>
                                    <div class="input-group">
                                        <span class="input-group-text bg-light fw-bold text-dark border-end-0" style="font-size: 0.92rem;">+92</span>
                                        <input type="tel" name="phone" id="seller_phone" class="form-control border-start-0 ps-1 font-monospace<?= $invalid('phone') ?>" placeholder="3001234567" maxlength="10" inputmode="numeric" oninput="this.value = this.value.replace(/[^0-9]/g, '').slice(0, 10);" value="<?= register_keep('phone') ?>" required>
                                    </div>
                                    <div class="form-text text-muted" style="font-size: 0.75rem;">Enter 10 digits only without leading 0 (e.g. 3001234567).</div>
                                </div>
                            </div>

                            <div class="row g-3 mb-3">
                                <div class="col-md-6">
                                    <label class="form-label fw-semibold small text-muted">City <span class="text-danger">*</span></label>
                                    <input type="text" name="city" id="seller_city" class="form-control<?= $invalid('city') ?>" placeholder="e.g. Lahore, Karachi, Islamabad" value="<?= register_keep('city') ?>" required>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label fw-semibold small text-muted">CNIC (National ID Card) <span class="text-danger">*</span></label>
                                    <input type="text" name="cnic_or_ntn" id="seller_cnic" class="form-control font-monospace<?= $invalid('cnic_or_ntn') ?>" placeholder="35201-1234567-1" maxlength="15" inputmode="numeric" oninput="formatCnic(this);" value="<?= register_keep('cnic_or_ntn') ?>" required>
                                    <div class="form-text text-muted" style="font-size: 0.75rem;">Format: 5 digits - 7 digits - 1 digit (auto-formatted).</div>
                                </div>
                            </div>

                            <div class="mb-3">
                                <label class="form-label fw-semibold small text-muted">Business / Warehouse Address <span class="text-danger">*</span></label>
                                <textarea name="business_address" id="seller_address" class="form-control<?= $invalid('business_address') ?>" rows="2" placeholder="Complete shop, plaza, or warehouse address" required><?= register_keep('business_address') ?></textarea>
                            </div>

                            <div class="row g-3 mb-4">
                                <div class="col-md-6">
                                    <label class="form-label fw-semibold small text-muted">Settlement Bank Name <span class="text-danger">*</span></label>
                                    <input type="text" name="bank_name" id="seller_bank" class="form-control<?= $invalid('bank_name') ?>" placeholder="e.g. Meezan Bank, HBL, Alfalah" value="<?= register_keep('bank_name') ?>" required>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label fw-semibold small text-muted">IBAN or Account Number <span class="text-danger">*</span></label>
                                    <input type="text" name="account_number_or_iban" id="seller_iban" class="form-control<?= $invalid('account_number_or_iban') ?>" placeholder="PKXXMEZN..." value="<?= register_keep('account_number_or_iban') ?>" required>
                                </div>
                            </div>

                            <div class="mb-4">
                                <label class="form-label fw-semibold small text-muted">Account Password (Min 6 characters) <span class="text-danger">*</span></label>
                                <div class="input-group">
                                    <input type="password" name="password" id="seller_password" class="form-control border-end-0<?= $invalid('password') ?>" placeholder="••••••••" minlength="6" value="<?= register_keep('password') ?>" required>
                                    <button class="btn btn-outline-secondary border-start-0 bg-transparent text-muted" type="button" onclick="togglePasswordVisibility('seller_password', 'sellerPasswordEye')">
                                        <i class="bi bi-eye" id="sellerPasswordEye"></i>
                                    </button>
                                </div>
                                <div class="form-text text-muted" style="font-size: 0.75rem;">Must be at least 6 characters. Numbers, letters, and symbols are supported.</div>
                            </div>

                            <button type="submit" id="sellerSubmitBtn" class="btn btn-sol-primary w-100 py-2.5 rounded-pill fw-bold shadow-sm">
                                Register Seller Account &amp; Verify Email OTP <i class="bi bi-arrow-right ms-1"></i>
                            </button>
                        </form>
                    </div>
                </div>

                <div class="text-center mt-4 small text-secondary">
                    Already registered? <a href="<?= site_url('login') ?>" class="fw-bold text-decoration-none" style="color: var(--sol-primary);">Sign In</a>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
// Hide / Show Password Toggle
function togglePasswordVisibility(inputId, iconId) {
    const input = document.getElementById(inputId);
    const icon = document.getElementById(iconId);
    if (!input || !icon) return;

    if (input.type === 'password') {
        input.type = 'text';
        icon.classList.remove('bi-eye');
        icon.classList.add('bi-eye-slash');
    } else {
        input.type = 'password';
        icon.classList.remove('bi-eye-slash');
        icon.classList.add('bi-eye');
    }
}

// Display error alert at the top and scroll to it
function displayValidationErrors(errors) {
    const alertBox = document.getElementById('validationAlert');
    const list = document.getElementById('validationAlertList');
    if (!alertBox || !list) return;

    list.innerHTML = '';
    errors.forEach(err => {
        const li = document.createElement('li');
        li.textContent = err;
        list.appendChild(li);
    });

    alertBox.classList.remove('d-none');
    alertBox.scrollIntoView({ behavior: 'smooth', block: 'start' });
}

// Customer Form Validation
function validateCustomerForm(e) {
    const nameEl = document.getElementById('customer_name');
    const emailEl = document.getElementById('customer_email');
    const phoneEl = document.getElementById('customer_phone');
    const passEl = document.getElementById('customer_password');

    let errors = [];

    // Clear previous highlights
    [nameEl, emailEl, phoneEl, passEl].forEach(el => el && el.classList.remove('is-invalid'));

    // Name check
    if (!nameEl.value.trim() || nameEl.value.trim().length < 2) {
        errors.push("Full Name is required and must be at least 2 characters.");
        nameEl.classList.add('is-invalid');
    }

    // Email check
    const emailVal = emailEl.value.trim();
    const emailPattern = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    if (!emailVal || !emailPattern.test(emailVal)) {
        errors.push("A valid Email Address is required (e.g. name@domain.com).");
        emailEl.classList.add('is-invalid');
    }

    // Phone check (exactly 10 digits, only numbers)
    const phoneVal = phoneEl.value.trim();
    if (!phoneVal || !/^[0-9]{10}$/.test(phoneVal)) {
        errors.push("Mobile number must be exactly 10 digits (numbers only, e.g. 3001234567).");
        phoneEl.classList.add('is-invalid');
    }

    // Password check (minimum 6 characters, supports symbols, numbers, letters)
    if (!passEl.value || passEl.value.length < 6) {
        errors.push("Password must be at least 6 characters long (letters, numbers, and symbols are supported).");
        passEl.classList.add('is-invalid');
    }

    if (errors.length > 0) {
        e.preventDefault();
        displayValidationErrors(errors);
        return false;
    }

    return true;
}

// Seller Form Validation
function validateSellerForm(e) {
    const nameEl = document.getElementById('seller_name');
    const storeEl = document.getElementById('seller_store_name');
    const emailEl = document.getElementById('seller_email');
    const phoneEl = document.getElementById('seller_phone');
    const cityEl = document.getElementById('seller_city');
    const passEl = document.getElementById('seller_password');
    const addressEl = document.getElementById('seller_address');
    const bankEl = document.getElementById('seller_bank');
    const ibanEl = document.getElementById('seller_iban');
    const cnicEl = document.getElementById('seller_cnic');

    let errors = [];

    [nameEl, storeEl, emailEl, phoneEl, cityEl, passEl, addressEl, bankEl, ibanEl, cnicEl].forEach(el => el && el.classList.remove('is-invalid'));

    if (!nameEl.value.trim() || nameEl.value.trim().length < 2) {
        errors.push("Owner Full Name is required and must be at least 2 characters.");
        nameEl.classList.add('is-invalid');
    }

    if (!storeEl.value.trim() || storeEl.value.trim().length < 3) {
        errors.push("Store / Brand Name is required and must be at least 3 characters.");
        storeEl.classList.add('is-invalid');
    }

    const emailVal = emailEl.value.trim();
    const emailPattern = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    if (!emailVal || !emailPattern.test(emailVal)) {
        errors.push("A valid Business Email Address is required (e.g. store@domain.com).");
        emailEl.classList.add('is-invalid');
    }

    const phoneVal = phoneEl.value.trim();
    if (!phoneVal || !/^[0-9]{10}$/.test(phoneVal)) {
        errors.push("Mobile number must be exactly 10 digits (numbers only, e.g. 3001234567).");
        phoneEl.classList.add('is-invalid');
    }

    if (!cityEl.value.trim()) {
        errors.push("City is required.");
        cityEl.classList.add('is-invalid');
    }

    const cnicVal = (cnicEl?.value || '').trim();
    if (!cnicVal || !/^[0-9]{5}-[0-9]{7}-[0-9]{1}$/.test(cnicVal)) {
        errors.push("CNIC is required (e.g. 35201-1234567-1).");
        if (cnicEl) cnicEl.classList.add('is-invalid');
    }

    if (!addressEl.value.trim() || addressEl.value.trim().length < 8) {
        errors.push("Business / warehouse address is required.");
        addressEl.classList.add('is-invalid');
    }

    if (!bankEl.value.trim()) {
        errors.push("Settlement bank name is required.");
        bankEl.classList.add('is-invalid');
    }

    if (!ibanEl.value.trim() || ibanEl.value.trim().length < 8) {
        errors.push("IBAN or account number is required.");
        ibanEl.classList.add('is-invalid');
    }

    if (!passEl.value || passEl.value.length < 6) {
        errors.push("Account Password must be at least 6 characters long (letters, numbers, and symbols are supported).");
        passEl.classList.add('is-invalid');
    }

    if (errors.length > 0) {
        e.preventDefault();
        displayValidationErrors(errors);
        return false;
    }

    return true;
}

// Auto-format CNIC with dashes: 5 digits - 7 digits - 1 digit
function formatCnic(input) {
    let val = input.value.replace(/[^0-9]/g, '').slice(0, 13);
    let formatted = '';
    if (val.length > 0) {
        formatted = val.slice(0, 5);
    }
    if (val.length > 5) {
        formatted += '-' + val.slice(5, 12);
    }
    if (val.length > 12) {
        formatted += '-' + val.slice(12, 13);
    }
    input.value = formatted;
}

// If page loaded with server-side errors, automatically scroll to alert box
document.addEventListener('DOMContentLoaded', function() {
    const alertBox = document.getElementById('validationAlert');
    if (alertBox && !alertBox.classList.contains('d-none')) {
        alertBox.scrollIntoView({ behavior: 'smooth', block: 'start' });
    }
});
</script>
<?= $this->endSection() ?>
