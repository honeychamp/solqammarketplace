<?= $this->extend('layouts/main') ?>

<?= $this->section('content') ?>
<!-- Daraz-Style Grand Hero Banner Section -->
<section class="py-4">
    <div class="container">
        <div class="row g-3 align-items-stretch">
            <!-- Main Hero Promotional Carousel Card -->
            <div class="col-lg-8">
                <?php if (!empty($heroBanners)): ?>
                    <div id="heroCarousel" class="carousel slide h-100 rounded-4 overflow-hidden shadow-sm" data-bs-ride="carousel">
                        <div class="carousel-inner h-100">
                            <?php foreach ($heroBanners as $i => $banner): ?>
                                <div class="carousel-item <?= $i === 0 ? 'active' : '' ?>">
                                    <a href="<?= esc($banner['link_url'] ?: site_url('shop')) ?>" class="d-block text-decoration-none">
                                        <?php if (!empty($banner['image_path'])): ?>
                                            <img src="<?= esc($banner['image_path']) ?>" class="d-block w-100" alt="<?= esc($banner['title']) ?>" style="min-height: 280px; object-fit: cover;">
                                        <?php else: ?>
                                            <div class="hero-slider-card p-4 p-md-5">
                                                <h2 class="text-white fw-bold"><?= esc($banner['title']) ?></h2>
                                                <p class="text-white-50"><?= esc($banner['subtitle']) ?></p>
                                            </div>
                                        <?php endif; ?>
                                    </a>
                                </div>
                            <?php endforeach; ?>
                        </div>
                        <?php if (count($heroBanners) > 1): ?>
                            <button class="carousel-control-prev" type="button" data-bs-target="#heroCarousel" data-bs-slide="prev"><span class="carousel-control-prev-icon"></span></button>
                            <button class="carousel-control-next" type="button" data-bs-target="#heroCarousel" data-bs-slide="next"><span class="carousel-control-next-icon"></span></button>
                        <?php endif; ?>
                    </div>
                <?php else: ?>
                <div class="hero-slider-card p-4 p-md-5 d-flex flex-column justify-content-between h-100">
                    <div class="position-relative" style="z-index: 2;">
                        <span class="badge bg-solqam-accent text-white px-3 py-2 rounded-pill fw-bold text-uppercase mb-3 shadow-sm">
                            <i class="bi bi-fire me-1"></i> Mega Shopping Festival &bull; Pakistan
                        </span>
                        <h1 class="display-6 fw-black text-white fw-bold mb-3" style="max-width: 520px; line-height: 1.2;">
                            Shop Genuine Brands with <span class="text-warning">Product Cashback</span>
                        </h1>
                        <p class="lead text-white-50 mb-4" style="max-width: 480px; font-size: 1.05rem;">
                            Discover top electronics, fashion, and groceries from verified merchants across Pakistan. Enjoy doorstep COD and certified JazzCash payments.
                        </p>
                        <div class="d-flex flex-wrap gap-3">
                            <a href="<?= site_url('shop') ?>" class="btn btn-solqam-accent px-4 py-2 rounded-pill">
                                <i class="bi bi-bag-check-fill me-2"></i> Explore Mega Deals
                            </a>
                            <a href="<?= site_url('register?role=seller') ?>" class="btn btn-outline-light px-4 py-2 rounded-pill fw-semibold">
                                <i class="bi bi-shop me-2"></i> Open Your Store
                            </a>
                        </div>
                    </div>

                    <!-- Trust indicators inside Hero -->
                    <div class="pt-4 border-top border-white border-opacity-25 mt-4 d-flex justify-content-between flex-wrap gap-3 text-white small" style="z-index: 2;">
                        <div class="d-flex align-items-center gap-2">
                            <i class="bi bi-patch-check-fill text-warning fs-5"></i>
                            <span>100% Genuine Guaranteed</span>
                        </div>
                        <div class="d-flex align-items-center gap-2">
                            <i class="bi bi-truck text-info fs-5"></i>
                            <span>Express Nationwide Dispatch</span>
                        </div>
                        <div class="d-flex align-items-center gap-2">
                            <i class="bi bi-wallet2 text-success fs-5"></i>
                            <span>Wallet cashback as listed on each product</span>
                        </div>
                    </div>
                </div>
                <?php endif; ?>
            </div>

            <!-- Side Promo Banners (Daraz Style) -->
            <div class="col-lg-4">
                <div class="d-flex flex-column gap-3 h-100">
                    <!-- Promo Card 1: Official Mall -->
                    <div class="hero-side-card">
                        <div>
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <span class="badge bg-solqam text-white px-2 py-1 rounded">SOLQAM MALL</span>
                                <span class="text-success small fw-bold"><i class="bi bi-check2-circle me-1"></i>Official Sellers</span>
                            </div>
                            <h5 class="fw-bold text-dark mb-1">Authentic Tech &amp; Lifestyle</h5>
                            <p class="text-muted small mb-3">Top brands with direct brand warranty and 7-day doorstep return policy.</p>
                        </div>
                        <a href="<?= site_url('shop') ?>" class="btn btn-solqam-outline btn-sm w-100 rounded-pill">
                            Shop Solqam Mall <i class="bi bi-arrow-right ms-1"></i>
                        </a>
                    </div>

                    <!-- Promo Card 2: Sell on Solqam -->
                    <div class="hero-side-card bg-light border-0 shadow-sm" style="background: linear-gradient(135deg, #FEF2F2 0%, #FFFFFF 100%);">
                        <div>
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <span class="badge bg-solqam-accent text-white px-2 py-1 rounded">SELLER HUB</span>
                                <span class="text-danger small fw-bold">0% Registration Fee</span>
                            </div>
                            <h5 class="fw-bold text-dark mb-1">Sell to Millions Nationwide</h5>
                            <p class="text-muted small mb-3">Reach buyers across Karachi, Lahore, Rawalpindi, and 150+ Pakistani cities.</p>
                        </div>
                        <a href="<?= site_url('register?role=seller') ?>" class="btn btn-solqam btn-sm w-100 rounded-pill">
                            <i class="bi bi-shop me-1"></i> Register as a Seller
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Category Highlights (Daraz Class Circular / Bubble Tiles) -->
<section class="py-4">
    <div class="container">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h4 class="fw-bold text-dark mb-1">Categories</h4>
                <p class="text-muted small mb-0">Browse genuine collections curated for Pakistan</p>
            </div>
            <a href="<?= site_url('shop') ?>" class="text-solqam fw-bold text-decoration-none small">
                View All <i class="bi bi-chevron-right"></i>
            </a>
        </div>

        <div class="row g-3">
            <?php
            $categoryIcons = [
                'consumer-electronics' => 'bi-phone',
                'fashion-apparel' => 'bi-handbag',
                'groceries-essentials' => 'bi-basket2',
                'home-living' => 'bi-lamp',
                'beauty-personal-care' => 'bi-heart-pulse',
                'sports-outdoor' => 'bi-bicycle',
            ];
            ?>
            <?php foreach ($categories as $cat): ?>
                <?php
                $icon = 'bi-grid-fill';
                foreach ($categoryIcons as $key => $ico) {
                    if (strpos($cat['slug'], $key) !== false) {
                        $icon = $ico;
                        break;
                    }
                }
                ?>
                <div class="col-4 col-md-3 col-lg-2">
                    <a href="<?= site_url('shop?category=' . esc($cat['slug'])) ?>" class="category-bubble-card h-100">
                        <div class="category-bubble-icon">
                            <i class="bi <?= $icon ?>"></i>
                        </div>
                        <div class="category-bubble-title text-truncate w-100" title="<?= esc($cat['name']) ?>"><?= esc($cat['name']) ?></div>
                        <span class="category-bubble-count">Explore &rarr;</span>
                    </a>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<!-- Flash Sale Section (Daraz Style Countdown & Progress) -->
<section class="py-4">
    <div class="container">
        <div class="flash-sale-wrapper">
            <div class="flash-sale-header flex-wrap gap-2">
                <div class="d-flex align-items-center gap-3">
                    <div class="d-flex align-items-center gap-2">
                        <i class="bi bi-lightning-charge-fill text-solqam-accent fs-3"></i>
                        <h4 class="fw-bold mb-0 text-dark">Flash Sale</h4>
                    </div>
                    <div class="countdown-box ms-md-3" data-ends="<?= esc($flashSale['ends_at'] ?? '') ?>">
                        <span class="text-muted small fw-semibold me-1 d-none d-sm-inline">Ending in:</span>
                        <span class="countdown-digit" id="flash-hours">00</span>
                        <span class="countdown-separator">:</span>
                        <span class="countdown-digit" id="flash-minutes">00</span>
                        <span class="countdown-separator">:</span>
                        <span class="countdown-digit" id="flash-seconds">00</span>
                    </div>
                </div>
                <a href="<?= site_url('shop') ?>" class="btn btn-solqam-outline btn-sm px-3 rounded-pill">
                    Shop More Deals <i class="bi bi-arrow-right ms-1"></i>
                </a>
            </div>

            <!-- Product Grid -->
            <div class="row g-3">
                <?php $dealProducts = !empty($flashProducts) ? $flashProducts : $featuredProducts; ?>
                <?php if (empty($dealProducts)): ?>
                    <div class="col-12 text-center py-5">
                        <div class="p-4 bg-light rounded-4">
                            <i class="bi bi-box-seam text-muted fs-1 mb-2 d-block"></i>
                            <h6 class="fw-bold text-dark">No Products Listed Yet</h6>
                            <p class="text-muted small mb-3">Sellers can list products in their Seller Hub to display them live here.</p>
                            <a href="<?= site_url('login') ?>" class="btn btn-solqam btn-sm px-4">Seller Login</a>
                        </div>
                    </div>
                <?php else: ?>
                    <?php foreach ($dealProducts as $index => $product): ?>
                        <?php
                        $salePrice = $product['sale_price'] ?? $product['price'];
                        $origPrice = $product['original_price'] ?? ($product['compare_at_price'] ?? $salePrice);
                        $discountPct = $origPrice > 0 ? round((($origPrice - $salePrice) / $origPrice) * 100) : 0;
                        $soldCount = $product['sold_count'] ?? 0;
                        $pid = $product['product_id'] ?? $product['id'];
                        ?>
                        <div class="col-6 col-md-4 col-lg-3">
                            <div class="solqam-product-card">
                                <div class="card-media">
                                    <img src="<?= esc($product['primary_image'] ?: base_url('assets/images/product-placeholder.svg')) ?>" alt="<?= esc($product['name']) ?>" loading="lazy">
                                    <?php if (!empty($product['is_mall'])): ?>
                                    <span class="solqam-mall-badge">Mall</span>
                                    <?php endif; ?>
                                    <?php if ($discountPct > 0): ?>
                                    <span class="discount-chip">-<?= $discountPct ?>%</span>
                                    <?php endif; ?>
                                    <?= cashback_chip($product) ?>
                                </div>
                                <div class="card-body">
                                    <span class="product-category-tag"><?= esc($product['category_name'] ?? 'Genuine Item') ?></span>
                                    <a href="<?= site_url('product/' . $pid) ?>" class="product-title" title="<?= esc($product['name']) ?>">
                                        <?= esc($product['name']) ?>
                                    </a>

                                    <div class="star-rating">
                                        <i class="bi bi-star"></i>
                                        <i class="bi bi-star"></i>
                                        <i class="bi bi-star"></i>
                                        <i class="bi bi-star"></i>
                                        <i class="bi bi-star"></i>
                                        <span class="review-count">(0)</span>
                                    </div>

                                    <div class="stock-progress-wrap mb-2">
                                        <div class="d-flex justify-content-between">
                                            <span><?= $soldCount ?> Sold</span>
                                            <span><?= max(1, (int)$product['stock']) ?> Left</span>
                                        </div>
                                        <div class="progress solqam-progress">
                                            <div class="progress-bar" style="width: <?= min(90, max(25, ($soldCount * 3))) ?>%;"></div>
                                        </div>
                                    </div>

                                    <div class="price-row">
                                        <div>
                                            <div class="price-current">
                                                <span class="currency">Rs.</span><?= number_format($salePrice, 0) ?>
                                            </div>
                                            <div class="price-original">
                                                Rs. <?= number_format($origPrice, 0) ?>
                                            </div>
                                            <span class="free-shipping-tag">Free Shipping</span>
                                            <div class="small text-success fw-semibold mt-1"><?= esc(cashback_percent_label($product)) ?> cashback</div>
                                        </div>
                                        <form action="<?= site_url('cart/add') ?>" method="POST" class="m-0">
                                            <?= csrf_field() ?>
                                            <input type="hidden" name="product_id" value="<?= $pid ?>">
                                            <input type="hidden" name="quantity" value="1">
                                            <button type="submit" class="btn btn-solqam btn-sm p-2 rounded-circle" title="Add to Cart" style="width: 38px; height: 38px;">
                                                <i class="bi bi-cart-plus fs-6"></i>
                                            </button>
                                        </form>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>
</section>

<!-- Features & Trust Strip (Daraz Class) -->
<section class="features-strip mt-4">
    <div class="container">
        <div class="row g-4 text-center text-md-start">
            <div class="col-sm-6 col-lg-3">
                <div class="feature-box">
                    <div class="feature-icon-circle bg-primary bg-opacity-10 text-solqam">
                        <i class="bi bi-truck"></i>
                    </div>
                    <div>
                        <h6 class="fw-bold mb-1 text-dark">Nationwide Express Delivery</h6>
                        <small class="text-muted">Doorstep tracking across 150+ cities in Pakistan</small>
                    </div>
                </div>
            </div>
            <div class="col-sm-6 col-lg-3">
                <div class="feature-box">
                    <div class="feature-icon-circle bg-warning bg-opacity-10 text-warning">
                        <i class="bi bi-coin"></i>
                    </div>
                    <div>
                        <h6 class="fw-bold mb-1 text-dark">Wallet cashback per product</h6>
                        <small class="text-muted">Automatic wallet reward on every delivered order</small>
                    </div>
                </div>
            </div>
            <div class="col-sm-6 col-lg-3">
                <div class="feature-box">
                    <div class="feature-icon-circle bg-danger bg-opacity-10 text-solqam-accent">
                        <i class="bi bi-shield-check"></i>
                    </div>
                    <div>
                        <h6 class="fw-bold mb-1 text-dark">COD &amp; JazzCash Payments</h6>
                        <small class="text-muted">Zero fraud risk with verified instant escrow</small>
                    </div>
                </div>
            </div>
            <div class="col-sm-6 col-lg-3">
                <div class="feature-box">
                    <div class="feature-icon-circle bg-success bg-opacity-10 text-success">
                        <i class="bi bi-arrow-counterclockwise"></i>
                    </div>
                    <div>
                        <h6 class="fw-bold mb-1 text-dark">7-Day Easy Returns</h6>
                        <small class="text-muted">Direct refund to your Solqam Wallet Ledger</small>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Countdown Script -->
<script>
    (function() {
        const box = document.querySelector('.countdown-box');
        const hoursEl = document.getElementById('flash-hours');
        const minutesEl = document.getElementById('flash-minutes');
        const secondsEl = document.getElementById('flash-seconds');
        const ends = box && box.dataset.ends ? Date.parse(box.dataset.ends.replace(' ', 'T')) : 0;

        function tick() {
            let totalSeconds = ends ? Math.max(0, Math.floor((ends - Date.now()) / 1000)) : 0;
            const h = Math.floor(totalSeconds / 3600);
            const m = Math.floor((totalSeconds % 3600) / 60);
            const s = totalSeconds % 60;
            if (hoursEl) hoursEl.textContent = String(h).padStart(2, '0');
            if (minutesEl) minutesEl.textContent = String(m).padStart(2, '0');
            if (secondsEl) secondsEl.textContent = String(s).padStart(2, '0');
        }
        tick();
        setInterval(tick, 1000);
    })();
</script>
<?= $this->endSection() ?>

