<?= $this->extend('layouts/main') ?>
<?= $this->section('content') ?>
<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-md-6">
            <div class="card border-0 shadow-sm rounded-4 p-4 text-center">
                <h4 class="fw-bold"><?= $success ? 'Payment update' : 'Payment not confirmed' ?></h4>
                <p class="text-secondary"><?= esc($message) ?></p>
                <?php if (!empty($orderId)): ?>
                    <a class="btn btn-solqam rounded-pill" href="<?= site_url('account/orders/' . $orderId) ?>">Open order</a>
                <?php else: ?>
                    <a class="btn btn-solqam rounded-pill" href="<?= site_url('login') ?>">Sign in to view order</a>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>
<?= $this->endSection() ?>
