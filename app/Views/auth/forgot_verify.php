<?= $this->extend('layouts/main') ?>
<?= $this->section('content') ?>
<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-md-6 col-lg-5">
            <div class="card shadow-sm border-0 rounded-4 p-4 p-md-5 bg-white text-center">
                <h4 class="fw-bold mb-2">Enter email OTP</h4>
                <p class="text-secondary small">
                    Reset code bheja gaya hai
                    <?php if (! empty($email)): ?>
                    <strong class="d-block text-dark mt-1"><?= esc($email) ?></strong>
                    <?php endif; ?>
                </p>
                <p class="small text-muted">Inbox / spam check karein. 10 minutes valid.</p>
                <form action="<?= site_url('forgot-password/verify') ?>" method="POST">
                    <?= csrf_field() ?>
                    <input type="text" name="otp" class="form-control form-control-lg text-center font-monospace mb-3" maxlength="6" inputmode="numeric" required autofocus>
                    <button class="btn btn-solqam w-100 rounded-pill" type="submit">Verify code</button>
                </form>
                <div class="small text-secondary mt-3">
                    OTP nahi aya? <a href="<?= site_url('forgot-password/verify?resend=1') ?>" class="fw-bold text-decoration-none" style="color: var(--sol-primary);">Resend OTP</a>
                </div>
            </div>
        </div>
    </div>
</div>
<?= $this->endSection() ?>
