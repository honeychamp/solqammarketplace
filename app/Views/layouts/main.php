<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= esc($title ?? 'Solqam Market Place') ?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="<?= base_url('assets/css/solqam-premium.css') ?>?v=20260918b">
</head>
<body class="d-flex flex-column min-vh-100">

    <!-- Top Announcement Bar (Daraz Style) -->
    <div class="solqam-topbar py-1 px-3">
        <div class="container d-flex justify-content-between align-items-center flex-wrap">
            <div class="d-flex align-items-center gap-3">
                <span><i class="bi bi-truck text-warning me-1"></i> Express Delivery to 150+ Cities in Pakistan</span>
                <span class="d-none d-md-inline text-white-50">|</span>
                <span class="d-none d-md-inline"><i class="bi bi-shield-check text-success me-1"></i> 100% Genuine Products &amp; Doorstep COD</span>
            </div>
            <div class="d-flex align-items-center gap-3 mt-1 mt-sm-0">
                <a href="<?= site_url('register?role=seller') ?>" class="text-warning fw-semibold">
                    <i class="bi bi-shop me-1"></i> Become a Seller
                </a>
                <span class="text-white-50">|</span>
                <a href="<?= site_url('track') ?>">Track My Order</a>
                <span class="text-white-50">|</span>
                <a href="<?= site_url('help') ?>">Help &amp; Support</a>
            </div>
        </div>
    </div>

    <!-- Main Navigation Header (Daraz Class) -->
    <header class="solqam-main-nav sticky-top py-2">
        <div class="container">
            <div class="row align-items-center g-2">
                <!-- Brand Logo: SOLQAM -->
                <div class="col-auto">
                    <a class="brand-badge-logo" href="<?= site_url('/') ?>" title="Solqam Market Place">
                        <img src="<?= base_url('assets/images/solqam-logo.svg') ?>" alt="Solqam Market Place" class="d-none d-sm-block" style="height: 42px; width: auto;">
                        <img src="<?= base_url('assets/images/solqam-logo.svg') ?>" alt="Solqam Market Place" class="d-sm-none" style="height: 34px; width: auto;">
                    </a>
                </div>

                <!-- Grand Marketplace Search Bar -->
                <div class="col">
                    <div class="solqam-search-container mx-auto">
                        <form action="<?= site_url('shop') ?>" method="GET" class="m-0">
                            <div class="solqam-search-input-group">
                                <i class="bi bi-search ms-3 text-muted"></i>
                                <input class="solqam-search-input" type="search" name="q" placeholder="Search genuine electronics, fashion, groceries, beauty in Pakistan..." value="<?= esc($_GET['q'] ?? '') ?>" autocomplete="off">
                                <button class="solqam-search-btn" type="submit">
                                    <span>Search</span>
                                </button>
                            </div>
                        </form>
                        <div class="quick-tags d-none d-lg-flex">
                            <span class="fw-bold text-dark">Trending:</span>
                            <a href="<?= site_url('shop?q=Smartphones') ?>">Smartphones</a>
                            <a href="<?= site_url('shop?q=Wireless+Earbuds') ?>">Earbuds</a>
                            <a href="<?= site_url('shop?q=Watches') ?>">Watches</a>
                            <a href="<?= site_url('shop?q=Laptops') ?>">Laptops</a>
                            <a href="<?= site_url('shop?q=Fashion') ?>">Fashion Deals</a>
                        </div>
                    </div>
                </div>

                <!-- User Actions & Cart -->
                <div class="col-auto">
                    <?php
                    $session = session();
                    $user = $session->get('user');
                    $cartCount = 0;
                    if ($user) {
                        $userModel = new \App\Models\UserModel();
                        $dbUser = !empty($user['id']) ? $userModel->find($user['id']) : null;
                        if (!$dbUser) {
                            $session->remove('user');
                            $user = null;
                        } else {
                            $cartModel = new \App\Models\CartModel();
                            $cartItemModel = new \App\Models\CartItemModel();
                            $c = $cartModel->where('user_id', $user['id'])->first();
                            if ($c) {
                                $cartCount = $cartItemModel->where('cart_id', $c['id'])->countAllResults();
                            }
                        }
                    }
                    ?>

                    <div class="d-flex align-items-center gap-2">
                        <!-- Wallet Pill for Logged-in Users -->
                        <?php if ($user): ?>
                            <?php
                            $walletService = new \App\Services\Wallet\WalletService();
                            $walletBalance = $walletService->getBalance((int) $user['id']);
                            ?>
                            <a href="<?= site_url('account/wallet') ?>" class="wallet-badge-pill d-none d-md-inline-flex" title="Solqam Instant Cashback Wallet">
                                <i class="bi bi-coin text-warning fs-6"></i>
                                <span>Rs. <?= number_format($walletBalance, 0) ?></span>
                            </a>
                        <?php endif; ?>

                        <a href="<?= site_url('compare') ?>" class="header-action-btn d-none d-md-inline-flex" title="Compare">
                            <i class="bi bi-sliders2-vertical fs-5 text-solqam"></i>
                            <span class="d-none d-xl-inline fw-bold">Compare</span>
                        </a>
                        <a href="<?= site_url('account/wishlist') ?>" class="header-action-btn position-relative" title="Wishlist">
                            <i class="bi bi-heart fs-5 text-solqam"></i>
                            <?php $wl = wishlist_count(); if ($wl > 0): ?>
                                <span class="cart-badge-counter"><?= $wl ?></span>
                            <?php endif; ?>
                            <span class="d-none d-xl-inline fw-bold">Wishlist</span>
                        </a>

                        <!-- Cart Button with Counter -->
                        <a href="<?= site_url('cart') ?>" class="header-action-btn position-relative" title="Shopping Cart">
                            <div class="cart-icon-wrapper text-solqam">
                                <i class="bi bi-cart3"></i>
                                <?php if ($cartCount > 0): ?>
                                    <span class="cart-badge-counter"><?= $cartCount ?></span>
                                <?php endif; ?>
                            </div>
                            <span class="d-none d-xl-inline fw-bold">Cart</span>
                        </a>

                        <!-- Account Dropdown / Auth Buttons -->
                        <?php if ($user): ?>
                            <div class="dropdown">
                                <a class="header-action-btn dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown">
                                    <i class="bi bi-person-circle fs-5 text-solqam"></i>
                                    <span class="d-none d-lg-inline"><?= esc(explode(' ', $user['name'])[0]) ?></span>
                                </a>
                                <ul class="dropdown-menu dropdown-menu-end shadow-lg border-0 rounded-4 mt-2 p-2" style="min-width: 240px;">
                                    <li class="px-3 py-2 border-bottom mb-2 bg-light rounded-3">
                                        <div class="fw-bold text-dark"><?= esc($user['name']) ?></div>
                                        <div class="small text-muted text-capitalize"><i class="bi bi-person-badge me-1"></i> Role: <?= esc($user['role']) ?></div>
                                    </li>
                                    <?php if ($user['role'] === 'customer'): ?>
                                        <li><a class="dropdown-item rounded-3 py-2" href="<?= site_url('account/orders') ?>"><i class="bi bi-box-seam text-solqam me-2"></i> My Orders</a></li>
                                        <li><a class="dropdown-item rounded-3 py-2" href="<?= site_url('messages') ?>"><i class="bi bi-chat-dots text-solqam me-2"></i> Messages</a></li>
                                        <li><a class="dropdown-item rounded-3 py-2" href="<?= site_url('help') ?>"><i class="bi bi-life-preserver text-solqam me-2"></i> Help Center</a></li>
                                        <li><a class="dropdown-item rounded-3 py-2" href="<?= site_url('account/wishlist') ?>"><i class="bi bi-heart text-danger me-2"></i> Wishlist</a></li>
                                        <li><a class="dropdown-item rounded-3 py-2" href="<?= site_url('track') ?>"><i class="bi bi-truck text-info me-2"></i> Track Order</a></li>
                                        <li><a class="dropdown-item rounded-3 py-2" href="<?= site_url('account/wallet') ?>"><i class="bi bi-wallet2 text-warning me-2"></i> Wallet Cashback Ledger</a></li>
                                        <li><a class="dropdown-item rounded-3 py-2" href="<?= site_url('account/addresses') ?>"><i class="bi bi-geo-alt text-danger me-2"></i> Shipping Addresses</a></li>
                                    <?php elseif ($user['role'] === 'seller'): ?>
                                        <li><a class="dropdown-item rounded-3 py-2 fw-bold text-solqam" href="<?= site_url('seller/dashboard') ?>"><i class="bi bi-speedometer2 me-2"></i> Seller Hub Dashboard</a></li>
                                        <li><a class="dropdown-item rounded-3 py-2" href="<?= site_url('seller/products') ?>"><i class="bi bi-box me-2"></i> Manage Inventory</a></li>
                                        <li><a class="dropdown-item rounded-3 py-2" href="<?= site_url('seller/orders') ?>"><i class="bi bi-receipt me-2"></i> Incoming Orders</a></li>
                                    <?php elseif ($user['role'] === 'admin'): ?>
                                        <li><a class="dropdown-item rounded-3 py-2 fw-bold text-danger" href="<?= site_url('admin/dashboard') ?>"><i class="bi bi-shield-lock me-2"></i> Admin Console</a></li>
                                        <li><a class="dropdown-item rounded-3 py-2" href="<?= site_url('admin/sellers') ?>"><i class="bi bi-shop me-2"></i> Seller Approvals</a></li>
                                        <li><a class="dropdown-item rounded-3 py-2" href="<?= site_url('admin/reports') ?>"><i class="bi bi-graph-up me-2"></i> Analytics &amp; GMV</a></li>
                                    <?php endif; ?>
                                    <li><hr class="dropdown-divider my-2"></li>
                                    <li><a class="dropdown-item rounded-3 py-2 text-danger fw-semibold" href="<?= site_url('logout') ?>"><i class="bi bi-box-arrow-right me-2"></i> Logout</a></li>
                                </ul>
                            </div>
                        <?php else: ?>
                            <a href="<?= site_url('login') ?>" class="btn btn-solqam-outline btn-sm px-3 d-none d-sm-inline-flex">Sign In</a>
                            <a href="<?= site_url('register') ?>" class="btn btn-solqam btn-sm px-3">Join Solqam</a>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </header>

    <nav class="solqam-category-bar d-none d-md-block">
        <div class="container d-flex align-items-center justify-content-between">
            <div class="d-flex align-items-center gap-1 overflow-x-auto">
                <a href="<?= site_url('shop?sort=best_selling') ?>" class="category-nav-link text-solqam-accent fw-bold">
                    <i class="bi bi-lightning-charge-fill"></i> Flash Deals
                </a>
                <?php foreach (category_tree() as $navCat): ?>
                    <div class="dropdown">
                        <a href="<?= site_url('shop?category=' . esc($navCat['slug'])) ?>" class="category-nav-link" <?= !empty($navCat['children']) ? 'data-bs-toggle="dropdown"' : '' ?>>
                            <i class="bi <?= esc($navCat['icon'] ?: 'bi-grid') ?>"></i> <?= esc($navCat['name']) ?>
                        </a>
                        <?php if (!empty($navCat['children'])): ?>
                            <ul class="dropdown-menu border-0 shadow-lg rounded-3 mt-0">
                                <?php foreach ($navCat['children'] as $child): ?>
                                    <li><a class="dropdown-item py-2" href="<?= site_url('shop?category=' . esc($child['slug'])) ?>"><?= esc($child['name']) ?></a></li>
                                <?php endforeach; ?>
                            </ul>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            </div>
            <a href="<?= site_url('shop') ?>" class="text-solqam text-decoration-none small fw-bold d-none d-xl-inline">
                All Categories <i class="bi bi-chevron-right"></i>
            </a>
        </div>
    </nav>

    <!-- Flash Alerts -->
    <div class="container mt-3">
        <?php if (session()->getFlashdata('success')): ?>
            <div class="alert alert-success alert-dismissible fade show d-flex align-items-center shadow-sm rounded-3 border-0 bg-success text-white" role="alert">
                <i class="bi bi-check-circle-fill me-2 fs-5"></i>
                <div class="fw-semibold"><?= session()->getFlashdata('success') ?></div>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <?php if (session()->getFlashdata('error')): ?>
            <div class="alert alert-danger alert-dismissible fade show d-flex align-items-center shadow-sm rounded-3 border-0 bg-danger text-white" role="alert">
                <i class="bi bi-exclamation-triangle-fill me-2 fs-5"></i>
                <div class="fw-semibold"><?= session()->getFlashdata('error') ?></div>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <?php if (session()->getFlashdata('info')): ?>
            <div class="alert alert-primary alert-dismissible fade show d-flex align-items-center shadow-sm rounded-3 border-0 bg-solqam text-white" role="alert">
                <i class="bi bi-info-circle-fill me-2 fs-5"></i>
                <div class="fw-semibold"><?= session()->getFlashdata('info') ?></div>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>
    </div>

    <!-- Main View Content -->
    <main class="flex-grow-1">
        <?= $this->renderSection('content') ?>
    </main>

    <!-- Daraz Class Footer -->
    <footer class="solqam-footer">
        <div class="container">
            <div class="row g-4 mb-5">
                <div class="col-lg-4 col-md-6">
                    <div class="mb-3">
                        <a href="<?= site_url('/') ?>" class="text-decoration-none d-inline-block">
                            <img src="<?= base_url('assets/images/solqam-logo-light.svg') ?>" alt="Solqam Market Place" style="height: 48px; width: auto;">
                        </a>
                    </div>
                    <p class="small text-secondary mb-3 pe-lg-4">
                        Pakistan's premier multi-vendor eCommerce marketplace. Empowering thousands of genuine local brands and sellers across Karachi, Lahore, Islamabad, and nationwide with automated ledger cashback, COD, and certified JazzCash payments.
                    </p>
                    <div class="d-flex align-items-center gap-3 text-white small">
                        <span><i class="bi bi-patch-check-fill text-primary me-1"></i> SECP Registered</span>
                        <span><i class="bi bi-shield-lock-fill text-success me-1"></i> 256-Bit SSL Secured</span>
                    </div>
                </div>

                <div class="col-6 col-lg-2 col-md-3">
                    <h6>Customer Care</h6>
                    <ul class="list-unstyled mb-0">
                        <li><a href="<?= site_url('help') ?>">Help Center</a></li>
                        <li><a href="<?= site_url('track') ?>">Track Your Order</a></li>
                        <li><a href="<?= site_url('account/wallet') ?>">Solqam Wallet</a></li>
                        <li><a href="<?= site_url('returns-policy') ?>">Returns &amp; Refunds</a></li>
                        <li><a href="<?= site_url('shipping-info') ?>">Shipping &amp; Delivery</a></li>
                    </ul>
                </div>

                <div class="col-6 col-lg-2 col-md-3">
                    <h6>Sell on Solqam</h6>
                    <ul class="list-unstyled mb-0">
                        <li><a href="<?= site_url('register?role=seller') ?>" class="text-warning fw-semibold">Seller Registration</a></li>
                        <li><a href="<?= site_url('login') ?>">Seller Hub Login</a></li>
                        <li><a href="<?= site_url('commission') ?>">Commission Structure</a></li>
                        <li><a href="<?= site_url('seller-policies') ?>">Seller Policies</a></li>
                        <li><a href="<?= site_url('fulfillment') ?>">Fulfillment by Solqam</a></li>
                    </ul>
                </div>

                <div class="col-lg-4 col-md-6">
                    <h6>Safe Payments &amp; Logistics</h6>
                    <div class="payment-badge-grid mb-3">
                        <span class="payment-chip"><i class="bi bi-cash text-success"></i> Cash on Delivery</span>
                        <span class="payment-chip"><i class="bi bi-phone text-success"></i> EasyPaisa</span>
                        <span class="payment-chip"><i class="bi bi-phone text-danger"></i> JazzCash</span>
                        <span class="payment-chip"><i class="bi bi-wallet2 text-warning"></i> Solqam Ledger</span>
                        <span class="payment-chip"><i class="bi bi-credit-card text-primary"></i> VISA / MasterCard</span>
                    </div>
                    <div class="small text-secondary mb-2">Nationwide Logistics Network:</div>
                    <div class="d-flex gap-2 flex-wrap text-white-50 small">
                        <span class="badge bg-secondary">TCS Express</span>
                        <span class="badge bg-secondary">Leopard Courier</span>
                        <span class="badge bg-secondary">Trax Logistics</span>
                        <span class="badge bg-secondary">Pakistan Post</span>
                    </div>
                </div>
            </div>

            <hr class="border-secondary opacity-25">
            <div class="d-flex justify-content-between align-items-center flex-column flex-sm-row small text-secondary pt-2">
                <div>&copy; <?= date('Y') ?> Solqam Market Place. All rights reserved.</div>
                <div class="mt-2 mt-sm-0">Enterprise Multi-Vendor Platform with Instant Ledger Cashback.</div>
            </div>
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <?= $this->renderSection('scripts') ?>
</body>
</html>

