<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= esc($title ?? 'Admin Console — Solqam Market Place') ?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="<?= base_url('assets/css/solqam-premium.css') ?>?v=20260917c">
</head>
<body class="hub-app admin-hub">
<div class="d-flex flex-column flex-lg-row">
    <aside class="solqam-sidebar solqam-admin-sidebar">
        <a href="<?= site_url('admin/dashboard') ?>" class="d-flex align-items-center gap-2 text-decoration-none px-3 pt-2 pb-3">
            <img src="<?= base_url('assets/images/solqam-logo-light.svg') ?>" alt="Solqam Market Place" style="height: 42px; width: auto;">
        </a>
        <div class="px-3 mb-2">
            <span class="badge rounded-pill px-3 py-2" style="background: rgba(240,20,47,0.28); color: #fff; border: 1px solid rgba(255,90,108,0.4);">Admin Console</span>
        </div>

        <div class="hub-status-card d-flex align-items-center justify-content-between">
            <span class="text-white-50 small">Platform</span>
            <span class="badge rounded-pill" style="background:#064E3B;color:#6EE7B7;"><i class="bi bi-circle-fill me-1" style="font-size:7px;"></i> Live</span>
        </div>

        <div class="hub-nav-label">Overview</div>
        <ul class="nav nav-pills flex-column mb-auto">
            <li class="nav-item">
                <a href="<?= site_url('admin/dashboard') ?>" class="nav-link <?= uri_string() === 'admin/dashboard' ? 'active' : '' ?>">
                    <span class="nav-ico"><i class="bi bi-speedometer2"></i></span><span class="label">Overview</span>
                </a>
            </li>
            <li class="nav-item">
                <a href="<?= site_url('admin/account') ?>" class="nav-link <?= strpos(uri_string(), 'admin/account') !== false ? 'active' : '' ?>">
                    <span class="nav-ico"><i class="bi bi-person-badge"></i></span><span class="label">Admin login</span>
                </a>
            </li>
            <li class="nav-item">
                <a href="<?= site_url('admin/settings') ?>" class="nav-link <?= strpos(uri_string(), 'admin/settings') !== false ? 'active' : '' ?>">
                    <span class="nav-ico"><i class="bi bi-sliders"></i></span><span class="label">SLA &amp; limits</span>
                </a>
            </li>
            <li class="nav-item">
                <a href="<?= site_url('admin/audit') ?>" class="nav-link <?= strpos(uri_string(), 'admin/audit') !== false ? 'active' : '' ?>">
                    <span class="nav-ico"><i class="bi bi-journal-text"></i></span><span class="label">Audit log</span>
                </a>
            </li>
            <li class="hub-nav-label">People</li>
            <li class="nav-item">
                <a href="<?= site_url('admin/sellers') ?>" class="nav-link <?= strpos(uri_string(), 'admin/sellers') !== false ? 'active' : '' ?>">
                    <span class="nav-ico"><i class="bi bi-shop"></i></span><span class="label">Sellers</span>
                </a>
            </li>
            <li class="nav-item">
                <a href="<?= site_url('admin/customers') ?>" class="nav-link <?= strpos(uri_string(), 'admin/customers') !== false ? 'active' : '' ?>">
                    <span class="nav-ico"><i class="bi bi-people"></i></span><span class="label">Customers</span>
                </a>
            </li>
            <li class="hub-nav-label">Catalog</li>
            <li class="nav-item">
                <a href="<?= site_url('admin/categories') ?>" class="nav-link <?= strpos(uri_string(), 'admin/categories') !== false ? 'active' : '' ?>">
                    <span class="nav-ico"><i class="bi bi-tags"></i></span><span class="label">Categories</span>
                </a>
            </li>
            <li class="nav-item">
                <a href="<?= site_url('admin/my-products') ?>" class="nav-link <?= strpos(uri_string(), 'admin/my-products') !== false ? 'active' : '' ?>">
                    <span class="nav-ico"><i class="bi bi-box2-heart"></i></span><span class="label">My products</span>
                </a>
            </li>
            <li class="nav-item">
                <a href="<?= site_url('admin/my-orders') ?>" class="nav-link <?= strpos(uri_string(), 'admin/my-orders') !== false ? 'active' : '' ?>">
                    <span class="nav-ico"><i class="bi bi-bag-check"></i></span><span class="label">My orders</span>
                </a>
            </li>
            <li class="nav-item">
                <a href="<?= site_url('admin/products') ?>" class="nav-link <?= uri_string() === 'admin/products' || strpos(uri_string(), 'admin/products/') === 0 ? 'active' : '' ?>">
                    <span class="nav-ico"><i class="bi bi-box-seam"></i></span><span class="label">Moderation</span>
                </a>
            </li>
            <li class="hub-nav-label">Commerce</li>
            <li class="nav-item">
                <a href="<?= site_url('admin/orders') ?>" class="nav-link <?= strpos(uri_string(), 'admin/orders') !== false ? 'active' : '' ?>">
                    <span class="nav-ico"><i class="bi bi-receipt"></i></span><span class="label">Orders</span>
                </a>
            </li>
            <li class="nav-item">
                <a href="<?= site_url('admin/returns') ?>" class="nav-link <?= strpos(uri_string(), 'admin/returns') !== false ? 'active' : '' ?>">
                    <span class="nav-ico"><i class="bi bi-arrow-counterclockwise"></i></span><span class="label">Returns</span>
                </a>
            </li>
            <li class="nav-item">
                <a href="<?= site_url('admin/commissions') ?>" class="nav-link <?= strpos(uri_string(), 'admin/commissions') !== false ? 'active' : '' ?>">
                    <span class="nav-ico"><i class="bi bi-percent"></i></span><span class="label">Commissions</span>
                </a>
            </li>
            <li class="nav-item">
                <a href="<?= site_url('admin/payouts') ?>" class="nav-link <?= strpos(uri_string(), 'admin/payouts') !== false ? 'active' : '' ?>">
                    <span class="nav-ico"><i class="bi bi-cash-coin"></i></span><span class="label">Payouts</span>
                </a>
            </li>
            <li class="nav-item">
                <a href="<?= site_url('admin/reports') ?>" class="nav-link <?= strpos(uri_string(), 'admin/reports') !== false ? 'active' : '' ?>">
                    <span class="nav-ico"><i class="bi bi-bar-chart-line"></i></span><span class="label">Reports</span>
                </a>
            </li>
            <li class="hub-nav-label">Growth</li>
            <li class="nav-item">
                <a href="<?= site_url('admin/coupons') ?>" class="nav-link <?= strpos(uri_string(), 'admin/coupons') !== false ? 'active' : '' ?>">
                    <span class="nav-ico"><i class="bi bi-ticket-perforated"></i></span><span class="label">Vouchers</span>
                </a>
            </li>
            <li class="nav-item">
                <a href="<?= site_url('admin/flash-sales') ?>" class="nav-link <?= strpos(uri_string(), 'admin/flash-sales') !== false ? 'active' : '' ?>">
                    <span class="nav-ico"><i class="bi bi-lightning-charge"></i></span><span class="label">Flash sales</span>
                </a>
            </li>
            <li class="nav-item">
                <a href="<?= site_url('admin/banners') ?>" class="nav-link <?= strpos(uri_string(), 'admin/banners') !== false ? 'active' : '' ?>">
                    <span class="nav-ico"><i class="bi bi-image"></i></span><span class="label">Banners</span>
                </a>
            </li>
            <li class="nav-item">
                <a href="<?= site_url('admin/shipping') ?>" class="nav-link <?= strpos(uri_string(), 'admin/shipping') !== false ? 'active' : '' ?>">
                    <span class="nav-ico"><i class="bi bi-truck"></i></span><span class="label">Shipping</span>
                </a>
            </li>
            <li class="nav-item">
                <a href="<?= site_url('admin/tickets') ?>" class="nav-link <?= strpos(uri_string(), 'admin/tickets') !== false ? 'active' : '' ?>">
                    <span class="nav-ico"><i class="bi bi-life-preserver"></i></span><span class="label">Help tickets</span>
                </a>
            </li>
            <li class="nav-item mt-3">
                <a href="<?= site_url('/') ?>" target="_blank" class="nav-link">
                    <span class="nav-ico"><i class="bi bi-box-arrow-up-right"></i></span><span class="label">Storefront</span>
                </a>
            </li>
        </ul>

        <div class="dropdown mt-auto px-2 pt-3">
            <a href="#" class="hub-user-chip dropdown-toggle" data-bs-toggle="dropdown">
                <div class="rounded-circle d-flex align-items-center justify-content-center" style="width:36px;height:36px;background:linear-gradient(135deg,#F0142F,#0B30E6);">
                    <i class="bi bi-shield-lock-fill"></i>
                </div>
                <div class="text-truncate" style="max-width: 150px;">
                    <strong class="d-block small"><?= esc(session()->get('user.name')) ?></strong>
                    <small class="text-white-50">Administrator</small>
                </div>
            </a>
            <ul class="dropdown-menu dropdown-menu-dark text-small shadow-lg rounded-3 border-0 mt-2">
                <li><a class="dropdown-item" href="<?= site_url('admin/account') ?>"><i class="bi bi-person-badge me-2"></i> Admin login</a></li>
                <li><a class="dropdown-item" href="<?= site_url('admin/settings') ?>"><i class="bi bi-sliders me-2"></i> SLA settings</a></li>
                <li><a class="dropdown-item" href="<?= site_url('/') ?>"><i class="bi bi-shop me-2"></i> Storefront</a></li>
                <li><hr class="dropdown-divider"></li>
                <li><a class="dropdown-item text-danger" href="<?= site_url('admin/logout') ?>"><i class="bi bi-box-arrow-right me-2"></i> Sign out</a></li>
            </ul>
        </div>
    </aside>

    <main class="hub-main content-area flex-grow-1 min-vh-100 d-flex flex-column">
        <header class="hub-topbar navbar navbar-expand px-4 py-3 sticky-top">
            <div class="container-fluid p-0">
                <div>
                    <div class="small text-muted fw-semibold text-uppercase" style="letter-spacing:.12em;">Solqam Market Place</div>
                    <h5 class="mb-0 fw-bold"><?= esc($title ?? 'Admin Console') ?></h5>
                </div>
                <div class="ms-auto d-flex align-items-center gap-2">
                    <span class="badge bg-solqam-accent text-white px-3 py-2 rounded-pill">Root access</span>
                    <a href="<?= site_url('admin/logout') ?>" class="btn btn-outline-danger btn-sm rounded-pill px-3">Logout</a>
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
            <?php if (session()->getFlashdata('info')): ?>
                <div class="alert alert-info alert-dismissible fade show rounded-4 border-0 shadow-sm" role="alert">
                    <?= session()->getFlashdata('info') ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>
            <?= $this->renderSection('content') ?>
        </div>
        <footer class="px-4 py-3 text-center text-muted small">
            &copy; <?= date('Y') ?> Solqam Market Place · Admin Console
        </footer>
    </main>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<?= $this->renderSection('scripts') ?>
</body>
</html>
