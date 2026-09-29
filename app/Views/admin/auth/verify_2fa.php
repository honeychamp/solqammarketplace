<?= $this->extend('layouts/main') ?>
<?= $this->section('content') ?>
<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-md-5">
            <div class="card shadow-sm border-0 rounded-4 p-4 p-md-5 bg-white text-center">
                <h4 class="fw-bold mb-2">Admin email code</h4>
                <p class="text-secondary small">6-digit code aapki admin email par gaya.</p>
                <form method="POST" action="<?= site_url('admin/verify-2fa') ?>">
                    <?= csrf_field() ?>
                    <input type="text" name="otp" class="form-control form-control-lg text-center font-monospace mb-3" maxlength="6" required autofocus>
                    <button class="btn btn-sol-primary w-100 rounded-pill" type="submit">Verify</button>
                </form>
            </div>
        </div>
    </div>
</div>
<?= $this->endSection() ?>
