<?= $this->extend('layouts/main') ?>

<?= $this->section('content') ?>
<section class="py-4 py-lg-5">
    <div class="container">
        <div class="row g-3 g-lg-4 align-items-stretch">
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
                    <div class="sf-hero-inner">
                        <span class="sf-hero-badge mb-3">
                            <i class="bi bi-stars"></i> Solqam Festival · Pakistan
                        </span>
                        <h1 class="display-6 fw-black text-white fw-bold mb-3" style="max-width: 560px; line-height: 1.15;">
                            Shop genuine brands. Earn wallet cashback on every order.
                        </h1>
                        <p class="lead mb-4" style="max-width: 500px; font-size: 1.05rem; color: rgba(255,255,255,.82);">
                            Electronics, fashion, groceries and beauty from verified sellers — with Cash on Delivery, JazzCash, EasyPaisa and cards.
                        </p>
                        <div class="d-flex flex-wrap gap-3">
                            <a href="<?= site_url('shop') ?>" class="btn btn-solqam-accent px-4 py-2 rounded-pill">
                                <i class="bi bi-bag-check-fill me-2"></i> Shop mega deals
                            </a>
                            <a href="<?= site_url('register?role=seller') ?>" class="btn btn-outline-light px-4 py-2 rounded-pill fw-semibold">
                                <i class="bi bi-shop me-2"></i> Open a store
                            </a>
                        </div>
                    </div>

                    <div class="sf-hero-stats mt-4">
                        <div class="sf-stat">
                            <strong>150+</strong>
                            <span>Cities delivered</span>
                        </div>
                        <div class="sf-stat">
                            <strong>COD + cards</strong>
                            <span>Safe checkout</span>
                        </div>
                        <div class="sf-stat">
                            <strong>7-day</strong>
                            <span>Easy returns</span>
                        </div>
                    </div>
                </div>
                <?php endif; ?>
            </div>

            <div class="col-lg-4">
                <div class="sf-side-stack">
                    <div class="hero-side-card">
                        <div>
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <span class="badge bg-solqam text-white px-2 py-1 rounded">SOLQAM MALL</span>
                                <span class="text-success small fw-bold"><i class="bi bi-check2-circle me-1"></i>Official</span>
                            </div>
                            <h5 class="fw-bold text-dark mb-1">Authentic tech &amp; lifestyle</h5>
                            <p class="text-muted small mb-3">Brand warranty, 7-day doorstep returns, and Mall-only sellers.</p>
                        </div>
                        <a href="<?= site_url('shop?mall=1') ?>" class="btn btn-solqam-outline btn-sm w-100 rounded-pill">
                            Shop Solqam Mall <i class="bi bi-arrow-right ms-1"></i>
                        </a>
                    </div>

                    <div class="hero-side-card" style="background: linear-gradient(160deg, #FFF7ED 0%, #FFFFFF 55%);">
                        <div>
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <span class="badge bg-solqam-accent text-white px-2 py-1 rounded">SELLER HUB</span>
                                <span class="text-danger small fw-bold">0% join fee</span>
                            </div>
                            <h5 class="fw-bold text-dark mb-1">Sell to millions nationwide</h5>
                            <p class="text-muted small mb-3">Reach Karachi, Lahore, Islamabad and 150+ cities from one hub.</p>
                        </div>
                        <a href="<?= site_url('register?role=seller') ?>" class="btn btn-solqam btn-sm w-100 rounded-pill">
                            <i class="bi bi-shop me-1"></i> Register as a seller
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<section class="pb-2">
    <div class="container">
        <div class="sf-rail">
            <a href="<?= site_url('shop?sort=best_selling') ?>" class="sf-rail-card sf-rail-a">
                <div>
                    <h6><i class="bi bi-lightning-charge-fill me-1"></i> Flash Sale</h6>
                    <p>Limited-time SKUs with countdown pricing.</p>
                </div>
                <span class="small fw-bold">Shop now →</span>
            </a>
            <a href="<?= site_url('shop?mall=1') ?>" class="sf-rail-card sf-rail-b">
                <div>
                    <h6><i class="bi bi-award-fill me-1"></i> Solqam Mall</h6>
                    <p>Official stores, warranty and faster dispatch.</p>
                </div>
                <span class="small fw-bold">Browse Mall →</span>
            </a>
            <a href="<?= site_url('account/wallet') ?>" class="sf-rail-card sf-rail-c">
                <div>
                    <h6><i class="bi bi-coin me-1"></i> Wallet cashback</h6>
                    <p>Rewards credited after every delivered order.</p>
                </div>
                <span class="small fw-bold">Open wallet →</span>
            </a>
        </div>
    </div>
</section>

<section class="py-4 py-lg-5">
    <div class="container">
        <div class="sf-section-head">
            <div>
                <div class="sf-eyebrow">Browse</div>
                <h4 class="fw-bold text-dark">Shop by category</h4>
            </div>
            <a href="<?= site_url('shop') ?>" class="text-solqam fw-bold text-decoration-none small">
                View all <i class="bi bi-chevron-right"></i>
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
                        <div class="category-bubble-icon<?= !empty($cat['image']) ? ' has-photo' : '' ?>">
                            <?php if (!empty($cat['image'])): ?>
                                <img src="<?= esc($cat['image']) ?>" alt="<?= esc($cat['name']) ?>">
                            <?php else: ?>
                                <i class="bi <?= $icon ?>"></i>
                            <?php endif; ?>
                        </div>
                        <div class="category-bubble-title text-truncate w-100" title="<?= esc($cat['name']) ?>"><?= esc($cat['name']) ?></div>
                        <span class="category-bubble-count">Explore</span>
                    </a>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<section class="pb-4">
    <div class="container">
        <div class="flash-sale-wrapper">
            <div class="flash-sale-header flex-wrap gap-2">
                <div class="d-flex align-items-center gap-3">
                    <div>
                        <div class="sf-eyebrow mb-1"><i class="bi bi-lightning-charge-fill"></i> Limited time</div>
                        <h4 class="fw-bold mb-0 text-dark">Flash Sale</h4>
                    </div>
                    <div class="countdown-box ms-md-2" data-ends="<?= esc($flashSale['ends_at'] ?? '') ?>">
                        <span class="text-muted small fw-semibold me-1 d-none d-sm-inline">Ends in</span>
                        <span class="countdown-digit">00</span>
                        <span class="countdown-separator">:</span>
                        <span class="countdown-digit">00</span>
                        <span class="countdown-separator">:</span>
                        <span class="countdown-digit">00</span>
                    </div>
                </div>
                <a href="<?= site_url('shop') ?>" class="btn btn-solqam-outline btn-sm px-3 rounded-pill">
                    Shop more deals <i class="bi bi-arrow-right ms-1"></i>
                </a>
            </div>

            <div class="row g-3">
                <?= view('customer/_deal_grid', [
                    'dealProducts' => $flashProducts ?? [],
                    'emptyTitle'   => 'No flash deals live',
                    'emptyCopy'    => 'Timer stays 00 until an active Flash Sale has approved SKUs. Sellers join from Seller Hub; admin approves.',
                ]) ?>
            </div>
        </div>
    </div>
</section>

<?php if (!empty($megaSale)): ?>
<section class="pb-4">
    <div class="container">
        <div class="flash-sale-wrapper" style="border-color: rgba(240,20,47,.35);">
            <div class="flash-sale-header flex-wrap gap-2">
                <div class="d-flex align-items-center gap-3 flex-wrap">
                    <div>
                        <div class="sf-eyebrow mb-1 text-solqam-accent">Festival</div>
                        <h4 class="fw-bold mb-0 text-dark"><?= esc($megaSale['title'] ?? 'Mega Sale') ?></h4>
                    </div>
                    <div class="countdown-box ms-md-2" data-ends="<?= esc($megaSale['ends_at'] ?? '') ?>">
                        <span class="text-muted small fw-semibold me-1 d-none d-sm-inline">Ends in</span>
                        <span class="countdown-digit">00</span>
                        <span class="countdown-separator">:</span>
                        <span class="countdown-digit">00</span>
                        <span class="countdown-separator">:</span>
                        <span class="countdown-digit">00</span>
                    </div>
                </div>
                <a href="<?= site_url('shop') ?>" class="btn btn-solqam-accent btn-sm px-3 rounded-pill text-white">Shop festival</a>
            </div>
            <?php if (!empty($megaSale['rules_note'])): ?>
                <p class="small text-muted mb-3"><?= esc($megaSale['rules_note']) ?></p>
            <?php endif; ?>
            <div class="row g-3">
                <?= view('customer/_deal_grid', [
                    'dealProducts' => $megaProducts ?? [],
                    'emptyTitle'   => 'Festival is live — deals coming',
                    'emptyCopy'    => 'Sellers can join this Mega Sale from Seller Hub. Approved SKUs appear here.',
                ]) ?>
            </div>
        </div>
    </div>
</section>
<?php endif; ?>

<section class="features-strip">
    <div class="container">
        <div class="row g-3">
            <div class="col-sm-6 col-lg-3">
                <div class="feature-box">
                    <div class="feature-icon-circle bg-primary bg-opacity-10 text-solqam">
                        <i class="bi bi-truck"></i>
                    </div>
                    <div>
                        <h6 class="fw-bold mb-1 text-dark">Nationwide delivery</h6>
                        <small class="text-muted">Doorstep tracking across 150+ cities</small>
                    </div>
                </div>
            </div>
            <div class="col-sm-6 col-lg-3">
                <div class="feature-box">
                    <div class="feature-icon-circle bg-warning bg-opacity-10 text-warning">
                        <i class="bi bi-coin"></i>
                    </div>
                    <div>
                        <h6 class="fw-bold mb-1 text-dark">Wallet cashback</h6>
                        <small class="text-muted">Automatic reward after delivery</small>
                    </div>
                </div>
            </div>
            <div class="col-sm-6 col-lg-3">
                <div class="feature-box">
                    <div class="feature-icon-circle bg-danger bg-opacity-10 text-solqam-accent">
                        <i class="bi bi-shield-check"></i>
                    </div>
                    <div>
                        <h6 class="fw-bold mb-1 text-dark">Secure payments</h6>
                        <small class="text-muted">COD, JazzCash, EasyPaisa &amp; cards</small>
                    </div>
                </div>
            </div>
            <div class="col-sm-6 col-lg-3">
                <div class="feature-box">
                    <div class="feature-icon-circle bg-success bg-opacity-10 text-success">
                        <i class="bi bi-arrow-counterclockwise"></i>
                    </div>
                    <div>
                        <h6 class="fw-bold mb-1 text-dark">7-day returns</h6>
                        <small class="text-muted">Refunds to your Solqam Wallet</small>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<script>
    (function() {
        document.querySelectorAll('.countdown-box').forEach(function (box) {
            const digits = box.querySelectorAll('.countdown-digit');
            const ends = box.dataset.ends ? Date.parse(box.dataset.ends.replace(' ', 'T')) : 0;
            function tick() {
                let totalSeconds = ends ? Math.max(0, Math.floor((ends - Date.now()) / 1000)) : 0;
                const h = Math.floor(totalSeconds / 3600);
                const m = Math.floor((totalSeconds % 3600) / 60);
                const s = totalSeconds % 60;
                if (digits[0]) digits[0].textContent = String(h).padStart(2, '0');
                if (digits[1]) digits[1].textContent = String(m).padStart(2, '0');
                if (digits[2]) digits[2].textContent = String(s).padStart(2, '0');
            }
            tick();
            setInterval(tick, 1000);
        });
    })();
</script>
<?= $this->endSection() ?>
