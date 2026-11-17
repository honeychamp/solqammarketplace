<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
    <title><?= esc($title ?? 'Merchant Hub — Solqam Marketplace') ?></title>
    <?= view('partials/favicon') ?>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="<?= base_url('assets/css/solqam-premium.css') ?>?v=20261113t">
    <link rel="stylesheet" href="<?= base_url('assets/css/solqam-card.css') ?>?v=20261113f">
    <link rel="stylesheet" href="<?= base_url('assets/css/solqam-responsive.css') ?>?v=20261113t">
</head>
<body class="hub-app seller-hub dukandar-hub">
<?php
$st = (string) (session()->get('user.dukandar_status') ?? 'pending');
$approved = $st === 'approved';
$key = merchant_hub_key();
?>
<div class="d-flex flex-column flex-lg-row">
    <aside class="solqam-sidebar">
        <div class="sidebar-head">
            <a href="<?= merchant_url('dashboard') ?>" class="d-flex align-items-center gap-2 text-decoration-none px-3 pt-2 pb-3">
                <img src="<?= base_url('assets/images/solqam-logo-light.svg') ?>?v=20261005c" alt="Solqam Marketplace" style="height: 56px; width: auto;">
            </a>
            <div class="px-3 mb-2">
                <span class="badge rounded-pill px-3 py-2" style="background: linear-gradient(135deg,#0B30E6,#10B981); color:#fff;">Merchant Hub</span>
            </div>
            <div class="hub-store-card">
                <div class="small text-white fw-semibold mb-1">Your shop</div>
                <div class="fw-bold text-white text-truncate"><i class="bi bi-shop-window me-1 text-warning"></i><?= esc(session()->get('user.shop_name') ?? 'Shop') ?></div>
                <?php if (session()->get('user.merchant_code')): ?>
                    <div class="small font-monospace text-white-50 mt-1"><?= esc(session()->get('user.merchant_code')) ?></div>
                <?php endif; ?>
                <div class="mt-2 small fw-semibold" style="color: <?= $approved ? '#6EE7B7' : '#FCD34D' ?>;">
                    <?= $approved ? 'Approved · accepts Solqam Card' : ($st === 'rejected' ? 'Not approved' : 'Pending approval') ?>
                </div>
            </div>
        </div>
        <div class="sidebar-scroll">
            <div class="hub-nav-label">Shop</div>
            <ul class="nav nav-pills flex-column mb-auto">
                <li class="nav-item"><a class="nav-link <?= $key === 'dashboard' ? 'active' : '' ?>" href="<?= merchant_url('dashboard') ?>"><span class="nav-ico"><i class="bi bi-grid-1x2-fill"></i></span><span class="label">Overview</span></a></li>
                <li class="nav-item"><a class="nav-link <?= str_starts_with($key, 'profile') ? 'active' : '' ?>" href="<?= merchant_url('profile') ?>"><span class="nav-ico"><i class="bi bi-person-badge"></i></span><span class="label">Shop profile</span></a></li>
                <?php if ($approved): ?>
                <li class="nav-item"><a class="nav-link <?= str_starts_with($key, 'scan') ? 'active' : '' ?>" href="<?= merchant_url('scan') ?>"><span class="nav-ico"><i class="bi bi-qr-code-scan"></i></span><span class="label">Scan card</span></a></li>
                <li class="nav-item"><a class="nav-link <?= str_starts_with($key, 'credits') ? 'active' : '' ?>" href="<?= merchant_url('credits') ?>"><span class="nav-ico"><i class="bi bi-wallet2"></i></span><span class="label">Pay Solqam</span></a></li>
                <li class="nav-item"><a class="nav-link <?= str_starts_with($key, 'customers') ? 'active' : '' ?>" href="<?= merchant_url('customers') ?>"><span class="nav-ico"><i class="bi bi-people"></i></span><span class="label">Customer history</span></a></li>
                <?php endif; ?>
                <li class="hub-nav-label">More</li>
                <li class="nav-item"><a class="nav-link <?= str_starts_with($key, 'become-seller') ? 'active' : '' ?>" href="<?= merchant_url('become-seller') ?>"><span class="nav-ico"><i class="bi bi-box-seam"></i></span><span class="label">Also sell online</span></a></li>
                <li class="nav-item"><a class="nav-link" href="<?= site_url('card') ?>"><span class="nav-ico"><i class="bi bi-credit-card-2-front"></i></span><span class="label">My Solqam Card</span></a></li>
                <li class="nav-item"><a class="nav-link" href="<?= site_url('/') ?>"><span class="nav-ico"><i class="bi bi-shop"></i></span><span class="label">Marketplace</span></a></li>
            </ul>
        </div>
        <div class="px-3 py-3">
            <a href="<?= site_url('logout') ?>" class="btn btn-outline-light btn-sm rounded-pill w-100">Sign out</a>
        </div>
    </aside>
    <?= view('partials/hub_sidebar_scroll') ?>
    <main class="hub-main content-area flex-grow-1 min-vh-100 d-flex flex-column">
        <header class="hub-topbar navbar navbar-expand px-4 py-3 sticky-top">
            <div class="container-fluid p-0">
                <div class="d-flex align-items-center min-w-0">
                    <?= view('partials/hub_nav_toggle') ?>
                    <div class="min-w-0">
                    <div class="small text-muted fw-semibold text-uppercase" style="letter-spacing:.12em;">Solqam Card network</div>
                    <h5 class="mb-0 fw-bold text-truncate"><?= esc($title ?? 'Merchant Hub') ?></h5>
                    </div>
                </div>
                <?= view('partials/hub_page_tools') ?>
            </div>
        </header>
        <div class="hub-content flex-grow-1">
            <?php if (session()->getFlashdata('success')): ?>
                <div class="alert alert-success rounded-4"><?= session()->getFlashdata('success') ?></div>
            <?php endif; ?>
            <?php if (session()->getFlashdata('error')): ?>
                <div class="alert alert-danger rounded-4"><?= session()->getFlashdata('error') ?></div>
            <?php endif; ?>
            <?= $this->renderSection('content') ?>
        </div>
    </main>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="<?= base_url('assets/js/solqam-hub-tools.js') ?>?v=20261113r"></script>
<?= $this->renderSection('scripts') ?>
</body>
</html>
