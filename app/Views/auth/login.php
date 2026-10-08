<?= $this->extend('layouts/main') ?>

<?= $this->section('content') ?>
<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-md-6 col-lg-5">
            <div class="card shadow-sm border-0 rounded-4 p-4 p-md-5 bg-white">
                <div class="text-center mb-4">
                    <img src="<?= base_url('assets/images/solqam-logo.svg') ?>?v=20261005c" alt="Solqam Marketplace" height="72" class="mb-3">
                    <h3 class="fw-bold text-dark mb-1">Welcome Back</h3>
                    <p class="text-secondary small">Sign in to your Customer or Seller account</p>
                </div>

                <form action="<?= site_url('login') ?>" method="POST">
                    <?= csrf_field() ?>

                    <div class="mb-3">
                        <label class="form-label fw-semibold small text-muted">Email or Mobile Number</label>
                        <div class="input-group">
                            <span class="input-group-text bg-light border-end-0 text-muted"><i class="bi bi-person"></i></span>
                            <?php $prefillLogin = old('login') ?: ($prefillLogin ?? ''); ?>
                            <input type="text" name="login" class="form-control border-start-0 ps-0" placeholder="Enter your email or phone number" value="<?= esc($prefillLogin) ?>" required <?= $prefillLogin === '' ? 'autofocus' : '' ?>>
                        </div>
                    </div>

                    <div class="mb-4">
                        <div class="d-flex justify-content-between align-items-center mb-1">
                            <label class="form-label fw-semibold small text-muted mb-0">Password</label>
                            <a href="<?= site_url('forgot-password') ?>" class="small text-decoration-none" style="color: var(--sol-primary); font-size: 0.78rem;">Forgot Password?</a>
                        </div>
                        <div class="input-group">
                            <span class="input-group-text bg-light border-end-0 text-muted"><i class="bi bi-lock"></i></span>
                            <input type="password" name="password" id="login_password" class="form-control border-start-0 border-end-0 ps-0" placeholder="••••••••" required <?= !empty($prefillLogin) ? 'autofocus' : '' ?>>
                            <button class="btn btn-outline-secondary border-start-0 bg-light text-muted" type="button" onclick="togglePasswordVisibility('login_password', 'loginPasswordEye')">
                                <i class="bi bi-eye" id="loginPasswordEye"></i>
                            </button>
                        </div>
                    </div>

                    <button type="submit" class="btn btn-sol-primary w-100 py-2.5 rounded-pill fw-bold mb-3 shadow-sm" style="font-size: 0.95rem;">
                        <i class="bi bi-box-arrow-in-right me-1"></i> Sign In
                    </button>

                    <div class="text-center small text-secondary">
                        Don't have an account? <a href="<?= site_url('register') ?>" class="fw-bold text-decoration-none" style="color: var(--sol-primary);">Sign Up for Free</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
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
</script>
<?= $this->endSection() ?>
