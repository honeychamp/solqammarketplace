<?= $this->extend('layouts/main') ?>
<?= $this->section('content') ?>
<div class="container py-4">
    <div class="row g-4">
        <div class="col-lg-8">
            <div class="card border-0 shadow-sm rounded-4 p-4 p-md-5">
                <h1 class="h3 fw-bold mb-2">Returns &amp; refunds</h1>
                <p class="text-muted">You can request a return on a delivered order. Admin reviews it; approved refunds credit your Solqam wallet.</p>
                <h5 class="fw-bold">Eligibility</h5>
                <ul class="text-secondary">
                    <li>Order status must be delivered.</li>
                    <li>One return request per order.</li>
                    <li>Reason and a short note are required.</li>
                </ul>
                <h5 class="fw-bold">How to request</h5>
                <ol class="text-secondary">
                    <li>Open <a href="<?= site_url('account/orders') ?>">My Orders</a> and the order detail.</li>
                    <li>Use Request Return / Refund.</li>
                    <li>Wait for admin approval. Wallet credit appears in the ledger.</li>
                </ol>
                <a href="<?= site_url('account/orders') ?>" class="btn btn-solqam rounded-pill px-4">Go to my orders</a>
            </div>
        </div>
        <div class="col-lg-4"><?= $this->include('customer/pages/_nav') ?></div>
    </div>
</div>
<?= $this->endSection() ?>
