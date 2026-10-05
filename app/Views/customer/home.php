<?= $this->extend('layouts/main') ?>

<?= $this->section('content') ?>
<section class="py-4 py-lg-5">
    <div class="container">
        <div class="row g-3 g-lg-4 align-items-stretch">
            <div class="col-lg-8">
                <?php
                $heroSlides = !empty($heroBanners) ? $heroBanners : [[
                    'title'       => 'Shop genuine brands. Earn wallet cashback on every order.',
                    'subtitle'    => 'Electronics, fashion, groceries and beauty from verified sellers — with Cash on Delivery, JazzCash, EasyPaisa and cards.',
                    'link_url'    => site_url('shop'),
                    'image_path'  => '',
                    'badge_text'  => 'Solqam Marketplace',
                    'button_text' => 'Shop now',
                ]];
                ?>
                <div id="heroCarousel" class="carousel slide h-100 rounded-4 overflow-hidden shadow-sm" data-bs-ride="carousel">
                    <div class="carousel-inner h-100">
                        <?php foreach ($heroSlides as $i => $banner): ?>
                            <?php
                            $img = trim((string) ($banner['image_path'] ?? ''));
                            if ($img !== '' && ! preg_match('#^https?://#i', $img) && strpos($img, '//') !== 0) {
                                $img = base_url(ltrim($img, '/'));
                            }
                            $href  = $banner['link_url'] ?: site_url('shop');
                            $btn   = trim((string) ($banner['button_text'] ?? $banner['button'] ?? '')) ?: 'Shop now';
                            $badge = trim((string) ($banner['badge_text'] ?? $banner['badge'] ?? '')) ?: 'Solqam Marketplace';
                            $photoStyle = $img !== ''
                                ? 'background-image: url(\'' . htmlspecialchars($img, ENT_QUOTES, 'UTF-8') . '\');'
                                : '';
                            ?>
                            <div class="carousel-item <?= $i === 0 ? 'active' : '' ?>">
                                <div class="hero-slider-card <?= $img !== '' ? 'has-photo' : '' ?> p-4 p-md-5 d-flex flex-column justify-content-between h-100" style="<?= $photoStyle ?>">
                                    <div class="sf-hero-inner">
                                        <span class="sf-hero-badge mb-3">
                                            <i class="bi bi-stars"></i> <?= esc($badge) ?>
                                        </span>
                                        <h1 class="display-6 fw-black text-white fw-bold mb-3" style="max-width: 560px; line-height: 1.15;">
                                            <?= esc($banner['title']) ?>
                                        </h1>
                                        <p class="lead mb-4" style="max-width: 500px; font-size: 1.05rem; color: rgba(255,255,255,.82);">
                                            <?= esc($banner['subtitle']) ?>
                                        </p>
                                        <div class="d-flex flex-wrap gap-3">
                                            <a href="<?= esc($href) ?>" class="btn btn-solqam-accent px-4 py-2 rounded-pill">
                                                <i class="bi bi-bag-check-fill me-2"></i> <?= esc($btn) ?>
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
                            </div>
                        <?php endforeach; ?>
                    </div>
                    <?php if (count($heroSlides) > 1): ?>
                        <button class="carousel-control-prev" type="button" data-bs-target="#heroCarousel" data-bs-slide="prev"><span class="carousel-control-prev-icon"></span></button>
                        <button class="carousel-control-next" type="button" data-bs-target="#heroCarousel" data-bs-slide="next"><span class="carousel-control-next-icon"></span></button>
                    <?php endif; ?>
                </div>
            </div>

            <div class="col-lg-4">
                <div class="sf-side-stack">
                    <?php
                    $defaultSides = [
                        [
                            'badge_text'  => 'SOLQAM MALL',
                            'title'       => 'Authentic tech & lifestyle',
                            'subtitle'    => 'Brand warranty, 7-day doorstep returns, and Mall-only sellers.',
                            'button_text' => 'Shop Solqam Mall',
                            'link_url'    => site_url('shop?mall=1'),
                            'image_path'  => '',
                            'accent'      => false,
                        ],
                        [
                            'badge_text'  => 'SELLER HUB',
                            'title'       => 'Sell to millions nationwide',
                            'subtitle'    => 'Reach Karachi, Lahore, Islamabad and 150+ cities from one hub.',
                            'button_text' => 'Register as a seller',
                            'link_url'    => site_url('register?role=seller'),
                            'image_path'  => '',
                            'accent'      => true,
                        ],
                    ];
                    $sideCards = !empty($sideBanners) ? $sideBanners : $defaultSides;
                    foreach ($sideCards as $si => $side):
                        $sideHref = $side['link_url'] ?: site_url('shop');
                        $sideBtn  = trim((string) ($side['button_text'] ?? '')) ?: 'Shop now';
                        $sideBadge = trim((string) ($side['badge_text'] ?? '')) ?: 'SOLQAM';
                        $sideImg  = trim((string) ($side['image_path'] ?? ''));
                        if ($sideImg !== '' && ! preg_match('#^https?://#i', $sideImg) && strpos($sideImg, '//') !== 0) {
                            $sideImg = base_url(ltrim($sideImg, '/'));
                        }
                        $sideStyle = $sideImg !== ''
                            ? 'background-image: linear-gradient(180deg, rgba(255,255,255,.92), rgba(255,255,255,.86)), url(\'' . htmlspecialchars($sideImg, ENT_QUOTES, 'UTF-8') . '\'); background-size: cover; background-position: center;'
                            : (($side['accent'] ?? ($si === 1)) ? 'background: linear-gradient(160deg, #FFF7ED 0%, #FFFFFF 55%);' : '');
                    ?>
                    <div class="hero-side-card" style="<?= $sideStyle ?>">
                        <div>
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <span class="badge <?= ($si % 2 === 1) ? 'bg-solqam-accent' : 'bg-solqam' ?> text-white px-2 py-1 rounded"><?= esc($sideBadge) ?></span>
                            </div>
                            <h5 class="fw-bold text-dark mb-1"><?= esc($side['title']) ?></h5>
                            <p class="text-muted small mb-3"><?= esc($side['subtitle']) ?></p>
                        </div>
                        <a href="<?= esc($sideHref) ?>" class="btn <?= ($si % 2 === 1) ? 'btn-solqam' : 'btn-solqam-outline' ?> btn-sm w-100 rounded-pill">
                            <?= esc($sideBtn) ?> <i class="bi bi-arrow-right ms-1"></i>
                        </a>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </div>
</section>

<section class="pb-2">
    <div class="container">
        <div class="sf-rail <?= empty($hasFlashDeal) ? 'sf-rail-compact' : '' ?>">
            <?php if (!empty($hasFlashDeal)): ?>
            <a href="<?= site_url('shop?sort=best_selling') ?>" class="sf-rail-card sf-rail-a">
                <div>
                    <h6><i class="bi bi-lightning-charge-fill me-1"></i> Flash Sale</h6>
                    <p>Limited-time SKUs with countdown pricing.</p>
                </div>
                <span class="small fw-bold">Shop now →</span>
            </a>
            <?php endif; ?>
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

<?php if (!empty($hasFlashDeal)): ?>
<section class="pb-4">
    <div class="container">
        <div class="flash-sale-wrapper">
            <div class="flash-sale-header flex-wrap gap-2">
                <div class="d-flex align-items-center gap-3">
                    <div>
                        <div class="sf-eyebrow mb-1"><i class="bi bi-lightning-charge-fill"></i> Limited time</div>
                        <h4 class="fw-bold mb-0 text-dark"><?= esc($flashSale['title'] ?? 'Flash Sale') ?></h4>
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
                    'emptyCopy'    => 'Timed deals will appear here when a flash sale is running.',
                ]) ?>
            </div>
        </div>
    </div>
</section>
<?php endif; ?>

<?php if (!empty($megaSale) && !empty($megaProducts)): ?>
<section class="pb-4">
    <div class="container">
        <div class="flash-sale-wrapper" style="border-color: rgba(240,20,47,.35);">
            <div class="flash-sale-header flex-wrap gap-2">
                <div class="d-flex align-items-center gap-3 flex-wrap">
                    <div>
                        <div class="sf-eyebrow mb-1 text-solqam-accent">Mega sale</div>
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
                <a href="<?= site_url('shop') ?>" class="btn btn-solqam-accent btn-sm px-3 rounded-pill text-white">Shop the sale</a>
            </div>
            <?php if (!empty($megaSale['rules_note'])): ?>
                <p class="small text-muted mb-3"><?= esc($megaSale['rules_note']) ?></p>
            <?php endif; ?>
            <div class="row g-3">
                <?= view('customer/_deal_grid', [
                    'dealProducts' => $megaProducts ?? [],
                    'emptyTitle'   => 'Mega sale is on',
                    'emptyCopy'    => 'Sale products will appear here once they are listed.',
                ]) ?>
            </div>
        </div>
    </div>
</section>
<?php endif; ?>

<section class="py-4 py-lg-5" id="products">
    <div class="container">
        <div class="sf-section-head">
            <div>
                <div class="sf-eyebrow">Catalog</div>
                <h4 class="fw-bold text-dark">All products</h4>
            </div>
            <span class="text-muted small"><?= number_format((int) ($homeTotal ?? 0)) ?> items</span>
        </div>
        <?php if (empty($homeProducts)): ?>
            <div class="text-center py-5 bg-white rounded-4 shadow-sm border p-5">
                <i class="bi bi-bag fs-1 text-muted mb-3 d-block"></i>
                <h5 class="fw-bold text-dark">No products yet</h5>
                <p class="text-muted small mb-0">Approved seller listings will appear here.</p>
            </div>
        <?php else: ?>
            <div class="row g-3">
                <?php foreach ($homeProducts as $product): ?>
                    <?= view('customer/_product_tile', ['product' => $product, 'colClass' => 'col-6 col-md-4 col-lg-2']) ?>
                <?php endforeach; ?>
            </div>
            <?= view('customer/_page_links', [
                'pages'   => (int) ($homePages ?? 1),
                'pageNow' => (int) ($homePage ?? 1),
                'baseUrl' => site_url('/'),
                'anchor'  => '#products',
            ]) ?>
        <?php endif; ?>
    </div>
</section>

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
