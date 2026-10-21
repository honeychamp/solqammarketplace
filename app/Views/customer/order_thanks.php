<?= $this->extend('layouts/main') ?>
<?= $this->section('content') ?>
<?php $order = $order ?? []; ?>
<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-lg-7">
            <div class="sf-panel p-4 p-md-5 text-center">
                <div class="text-success mb-3"><i class="bi bi-check-circle-fill" style="font-size:3rem;"></i></div>
                <h3 class="fw-bold mb-2">Order placed</h3>
                <p class="text-secondary mb-4">Save this order number. Use it with your phone number on Track Order. You can create an account later with the same phone to see wallet cashback and order history.</p>
                <div class="p-3 rounded-4 bg-light mb-4">
                    <div class="small text-muted">Order number</div>
                    <div class="fs-4 fw-bold font-monospace"><?= esc($order['order_number'] ?? '') ?></div>
                    <div class="small text-muted mt-2">Status: <?= esc(strtoupper($order['status'] ?? '')) ?> · Payment: <?= esc(strtoupper($order['payment_status'] ?? '')) ?></div>
                </div>
                <div class="d-flex flex-wrap gap-2 justify-content-center">
                    <a class="btn btn-solqam rounded-pill px-4" href="<?= site_url('track?order=' . urlencode((string) ($order['order_number'] ?? ''))) ?>">Track this order</a>
                    <a class="btn btn-outline-dark rounded-pill px-4" href="<?= site_url('shop') ?>">Keep shopping</a>
                    <a class="btn btn-solqam-outline rounded-pill px-4" href="<?= site_url('register') ?>">Create account</a>
                </div>
            </div>
        </div>
    </div>
</div>
<?= $this->endSection() ?>
