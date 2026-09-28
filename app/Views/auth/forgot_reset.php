<?= $this->extend('layouts/main') ?>
<?= $this->section('content') ?>
<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-md-6 col-lg-5">
            <div class="card shadow-sm border-0 rounded-4 p-4 p-md-5 bg-white">
                <h4 class="fw-bold mb-3">Set a new password</h4>
                <form action="<?= site_url('forgot-password/reset') ?>" method="POST">
                    <?= csrf_field() ?>
                    <div class="mb-3">
                        <label class="form-label small fw-bold">New password</label>
                        <input type="password" name="password" class="form-control" minlength="6" required>
                    </div>
                    <div class="mb-4">
                        <label class="form-label small fw-bold">Confirm password</label>
                        <input type="password" name="password_confirm" class="form-control" minlength="6" required>
                    </div>
                    <button class="btn btn-solqam w-100 rounded-pill" type="submit">Update password</button>
                </form>
            </div>
        </div>
    </div>
</div>
<?= $this->endSection() ?>
