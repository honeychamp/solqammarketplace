<?= $this->extend('layouts/main') ?>
<?= $this->section('content') ?>
<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-md-6 col-lg-5">
            <div class="card shadow-sm border-0 rounded-4 p-4 p-md-5 bg-white">
                <h3 class="fw-bold mb-1">Forgot password</h3>
                    <p class="text-secondary small mb-4">Enter the email or mobile number on your customer or seller account. We will send a code to the registered email.</p>
                <form action="<?= site_url('forgot-password') ?>" method="POST">
                    <?= csrf_field() ?>
                    <div class="mb-3">
                        <label class="form-label small fw-bold">Email or mobile</label>
                        <input type="text" name="login" class="form-control" value="<?= old('login') ?>" required autofocus>
                    </div>
                    <button class="btn btn-solqam w-100 rounded-pill py-2" type="submit">Send reset code</button>
                </form>
                <div class="text-center small mt-3"><a href="<?= site_url('login') ?>">Back to sign in</a></div>
            </div>
        </div>
    </div>
</div>
<?= $this->endSection() ?>
