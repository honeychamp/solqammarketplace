<?= $this->extend('layouts/main') ?>

<?= $this->section('content') ?>
<?php
$u = session()->get('user') ?? [];
$name = (string) ($u['name'] ?? 'Customer');
$role = (string) ($u['role'] ?? 'customer');
$wallet = (float) ($wallet ?? 0);
?>
<div class="container py-4 acc-page">
    <div class="acc-card mb-3">
        <div class="fw-bold text-dark fs-5"><?= esc($name) ?></div>
        <div class="small text-muted text-capitalize mt-1">
            <i class="bi bi-person-badge me-1"></i> Role: <?= esc($role) ?>
        </div>
        <a href="<?= site_url('account/wallet') ?>" class="acc-wallet mt-3">
            <i class="bi bi-coin text-warning"></i>
            <span><?= $wallet < 0 ? 'Minus Rs. ' . number_format(abs($wallet), 0) : 'Rs. ' . number_format($wallet, 0) ?></span>
        </a>
    </div>

    <div class="acc-card p-0 overflow-hidden">
        <a class="acc-link" href="<?= site_url('account/orders') ?>"><i class="bi bi-box-seam text-solqam"></i> My Orders</a>
        <a class="acc-link" href="<?= site_url('messages') ?>"><i class="bi bi-chat-dots text-solqam"></i> Messages</a>
        <a class="acc-link" href="<?= site_url('help') ?>"><i class="bi bi-life-preserver text-solqam"></i> Help Center</a>
        <a class="acc-link" href="<?= site_url('account/wishlist') ?>"><i class="bi bi-heart text-danger"></i> Wishlist</a>
        <a class="acc-link" href="<?= site_url('track') ?>"><i class="bi bi-truck text-info"></i> Track Order</a>
        <a class="acc-link" href="<?= site_url('account/wallet') ?>"><i class="bi bi-wallet2 text-warning"></i> Wallet Cashback Ledger</a>
        <a class="acc-link" href="<?= site_url('account/addresses') ?>"><i class="bi bi-geo-alt text-danger"></i> Shipping Addresses</a>
        <a class="acc-link acc-link-out" href="<?= site_url('logout') ?>"><i class="bi bi-box-arrow-right"></i> Logout</a>
    </div>
</div>
<?= $this->endSection() ?>
