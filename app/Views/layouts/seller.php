<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= esc($title ?? 'Seller Hub — Solqam Market Place') ?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="<?= base_url('assets/css/solqam-premium.css') ?>?v=20260917c">
</head>
<body class="hub-app seller-hub">
<div class="d-flex flex-column flex-lg-row">
    <aside class="solqam-sidebar">
        <a href="<?= site_url('seller/dashboard') ?>" class="d-flex align-items-center gap-2 text-decoration-none px-3 pt-2 pb-3">
            <img src="<?= base_url('assets/images/solqam-logo-light.svg') ?>" alt="Solqam Market Place" style="height: 42px; width: auto;">
        </a>
        <div class="px-3 mb-2">
            <span class="badge rounded-pill px-3 py-2" style="background: rgba(11,48,230,0.35); color: #fff; border: 1px solid rgba(147,197,253,0.35);">Seller Hub</span>
        </div>

        <div class="hub-store-card">
            <div class="text-white-50 small mb-1">Your store</div>
            <div class="fw-bold text-white text-truncate"><i class="bi bi-shop me-1 text-warning"></i><?= esc(session()->get('user.store_name') ?? 'Vendor Store') ?></div>
            <div class="mt-2 small fw-semibold" style="color: #6EE7B7;"><i class="bi bi-patch-check-fill me-1"></i>Verified partner</div>
        </div>

            <div class="hub-nav-label">Workspace</div>
        <ul class="nav nav-pills flex-column mb-auto">
            <li class="nav-item">
                <a href="<?= site_url('seller/dashboard') ?>" class="nav-link <?= uri_string() === 'seller/dashboard' ? 'active' : '' ?>">
                    <span class="nav-ico"><i class="bi bi-grid-1x2-fill"></i></span><span class="label">Dashboard</span>
                </a>
            </li>
            <li class="nav-item">
                <a href="<?= site_url('seller/products') ?>" class="nav-link <?= strpos(uri_string(), 'seller/products') !== false ? 'active' : '' ?>">
                    <span class="nav-ico"><i class="bi bi-box-seam"></i></span><span class="label">Products</span>
                </a>
            </li>
            <li class="nav-item">
                <a href="<?= site_url('seller/orders') ?>" class="nav-link <?= strpos(uri_string(), 'seller/orders') !== false ? 'active' : '' ?>">
                    <span class="nav-ico"><i class="bi bi-bag-check"></i></span><span class="label">Orders</span>
                </a>
            </li>
            <li class="nav-item">
                <a href="<?= site_url('seller/customers') ?>" class="nav-link <?= strpos(uri_string(), 'seller/customers') !== false ? 'active' : '' ?>">
                    <span class="nav-ico"><i class="bi bi-people"></i></span><span class="label">Buyers 360</span>
                </a>
            </li>
            <li class="nav-item">
                <a href="<?= site_url('seller/performance') ?>" class="nav-link <?= uri_string() === 'seller/performance' ? 'active' : '' ?>">
                    <span class="nav-ico"><i class="bi bi-graph-up-arrow"></i></span><span class="label">Performance</span>
                </a>
            </li>
        </ul>
        <div class="hub-nav-label">Buyers</div>
        <ul class="nav nav-pills flex-column mb-auto">
            <li class="nav-item">
                <a href="<?= site_url('seller/questions') ?>" class="nav-link <?= strpos(uri_string(), 'seller/questions') !== false ? 'active' : '' ?>">
                    <span class="nav-ico"><i class="bi bi-chat-left-text"></i></span><span class="label">Q&amp;A</span>
                </a>
            </li>
            <li class="nav-item">
                <a href="<?= site_url('seller/messages') ?>" class="nav-link <?= strpos(uri_string(), 'seller/messages') !== false ? 'active' : '' ?>">
                    <span class="nav-ico"><i class="bi bi-chat-dots"></i></span><span class="label">Chat</span>
                </a>
            </li>
        </ul>
        <div class="hub-nav-label">Money</div>
        <ul class="nav nav-pills flex-column mb-auto">
            <li class="nav-item">
                <a href="<?= site_url('seller/payouts') ?>" class="nav-link <?= strpos(uri_string(), 'seller/payouts') !== false ? 'active' : '' ?>">
                    <span class="nav-ico"><i class="bi bi-wallet2"></i></span><span class="label">Payouts</span>
                </a>
            </li>
            <li class="nav-item mt-3">
                <a href="<?= site_url('/') ?>" target="_blank" class="nav-link">
                    <span class="nav-ico"><i class="bi bi-shop-window"></i></span><span class="label">View storefront</span>
                </a>
            </li>
        </ul>

        <div class="dropdown mt-auto px-2 pt-3">
            <a href="#" class="hub-user-chip dropdown-toggle" data-bs-toggle="dropdown">
                <div class="rounded-circle d-flex align-items-center justify-content-center" style="width:36px;height:36px;background:linear-gradient(135deg,#0B30E6,#3d5df0);">
                    <i class="bi bi-person-fill"></i>
                </div>
                <div class="text-truncate" style="max-width: 150px;">
                    <strong class="d-block small"><?= esc(session()->get('user.name')) ?></strong>
                    <small class="text-white-50">Merchant</small>
                </div>
            </a>
            <ul class="dropdown-menu dropdown-menu-dark text-small shadow-lg rounded-3 border-0 mt-2">
                <li><a class="dropdown-item" href="<?= site_url('/') ?>"><i class="bi bi-shop me-2"></i> Storefront</a></li>
                <li><hr class="dropdown-divider"></li>
                <li><a class="dropdown-item text-danger" href="<?= site_url('logout') ?>"><i class="bi bi-box-arrow-right me-2"></i> Sign out</a></li>
            </ul>
        </div>
    </aside>

    <main class="hub-main content-area flex-grow-1 min-vh-100 d-flex flex-column">
        <header class="hub-topbar navbar navbar-expand px-4 py-3 sticky-top">
            <div class="container-fluid p-0">
                <div>
                    <div class="small text-muted fw-semibold text-uppercase" style="letter-spacing:.12em;">Solqam Market Place</div>
                    <h5 class="mb-0 fw-bold"><?= esc($title ?? 'Seller Hub') ?></h5>
                </div>
                <div class="ms-auto d-flex align-items-center gap-2">
                    <a href="<?= site_url('seller/products/create') ?>" class="btn btn-solqam btn-sm px-3">
                        <i class="bi bi-plus-lg me-1"></i> Add product
                    </a>
                    <a href="<?= site_url('seller/orders') ?>" class="btn btn-light border btn-sm rounded-pill px-3" title="Orders">
                        <i class="bi bi-bell"></i>
                    </a>
                </div>
            </div>
        </header>

        <div class="hub-content flex-grow-1">
            <?php if (session()->getFlashdata('success')): ?>
                <div class="alert alert-success alert-dismissible fade show rounded-4 border-0 shadow-sm" role="alert">
                    <i class="bi bi-check-circle-fill me-2"></i> <?= session()->getFlashdata('success') ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>
            <?php if (session()->getFlashdata('error')): ?>
                <div class="alert alert-danger alert-dismissible fade show rounded-4 border-0 shadow-sm" role="alert">
                    <i class="bi bi-exclamation-triangle-fill me-2"></i> <?= session()->getFlashdata('error') ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>
            <?= $this->renderSection('content') ?>
        </div>

        <footer class="px-4 py-3 text-center text-muted small">
            &copy; <?= date('Y') ?> Solqam Market Place · Seller Hub
        </footer>
    </main>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<?= $this->renderSection('scripts') ?>
</body>
</html>
