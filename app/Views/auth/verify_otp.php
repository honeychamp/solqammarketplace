<?= $this->extend('layouts/main') ?>

<?= $this->section('content') ?>
<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-md-5">
            <div class="card shadow-sm border-0 rounded-4 p-4 p-md-5 bg-white text-center">
                <div class="mb-4">
                    <img src="<?= base_url('assets/images/solqam-logo.svg') ?>" alt="Solqam Market Place" height="52" class="mb-3">
                    <div class="d-inline-flex p-3 rounded-circle mb-3" style="background: rgba(11, 48, 230, 0.1); color: var(--sol-primary);">
                        <i class="bi bi-envelope-check-fill fs-2"></i>
                    </div>
                    <h4 class="fw-bold text-dark mb-1">Verify your email</h4>
                    <p class="text-secondary small mb-0">
                        6-digit OTP bheja gaya hai
                    </p>
                    <?php if (! empty($email)): ?>
                    <p class="fw-semibold text-dark mt-2 mb-0"><?= esc($email) ?></p>
                    <?php endif; ?>
                </div>

                <div class="p-3 rounded-3 mb-4 text-start d-flex align-items-center gap-2" style="background: #ECFDF5; border: 1px solid #A7F3D0;">
                    <i class="bi bi-envelope text-success fs-5"></i>
                    <div class="small text-dark">Inbox (aur spam) check karein. Code 10 minutes valid hai. Page par OTP nahi dikhega.</div>
                </div>

                <form action="<?= site_url('verify-otp') ?>" method="POST">
                    <?= csrf_field() ?>
                    <input type="hidden" name="phone" value="<?= esc($phone) ?>">

                    <div class="mb-4">
                        <label class="form-label fw-semibold small text-muted">Email se OTP darj karein</label>
                        <input type="text" name="otp" class="form-control form-control-lg text-center fw-bold fs-2 font-monospace" style="letter-spacing: 0.5rem; max-width: 280px; margin: 0 auto;" maxlength="6" inputmode="numeric" placeholder="••••••" required autofocus>
                    </div>

                    <button type="submit" class="btn btn-sol-primary w-100 py-2.5 rounded-pill fw-bold mb-3 shadow-sm">
                        Confirm &amp; Activate Account <i class="bi bi-check2-circle ms-1"></i>
                    </button>
                </form>

                <div class="small text-secondary">
                    OTP nahi aya? <a href="<?= site_url('verify-otp?phone=' . urlencode((string) $phone) . '&resend=1') ?>" class="fw-bold text-decoration-none" style="color: var(--sol-primary);">Resend OTP</a>
                </div>
            </div>
        </div>
    </div>
</div>
<?= $this->endSection() ?>
