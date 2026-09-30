<?= $this->extend('layouts/main') ?>
<?= $this->section('content') ?>
<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-md-6 col-lg-5">
            <div class="sf-panel p-4 p-md-5 text-center">
                <h4 class="fw-bold mb-2">Enter email code</h4>
                <p class="text-secondary small">
                    A reset code was sent to
                    <?php if (! empty($email)): ?>
                    <strong class="d-block text-dark mt-1"><?= esc($email) ?></strong>
                    <?php endif; ?>
                </p>
                <p class="small text-muted">Check inbox and spam. Valid for 10 minutes.</p>
                <form action="<?= site_url('forgot-password/verify') ?>" method="POST">
                    <?= csrf_field() ?>
                    <input type="text" name="otp" class="form-control form-control-lg text-center font-monospace mb-3" maxlength="6" inputmode="numeric" required autofocus autocomplete="one-time-code">
                    <button class="btn btn-solqam w-100 rounded-pill" type="submit">Verify code</button>
                </form>
                <form action="<?= site_url('forgot-password/verify/resend') ?>" method="POST" class="small text-secondary mt-3">
                    <?= csrf_field() ?>
                    Didn’t get the email?
                    <button type="submit" class="btn btn-link p-0 fw-bold text-decoration-none align-baseline" style="color: var(--sol-primary);">Resend code</button>
                </form>
            </div>
        </div>
    </div>
</div>
<?= $this->endSection() ?>
