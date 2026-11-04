<?php $megaTree = category_tree(); ?>
<nav class="solqam-category-bar d-none d-md-block">
    <div class="container mega-bar-inner">
        <div class="d-flex align-items-center mega-bar-row">
            <a href="<?= site_url('shop?sort=best_selling') ?>" class="category-nav-link text-solqam-accent fw-bold mega-pin">
                <i class="bi bi-lightning-charge-fill"></i> Flash Deals
            </a>
            <div class="mega-wrap">
                <a href="<?= site_url('shop') ?>" class="category-nav-link mega-all-btn mega-pin">
                    <i class="bi bi-grid-3x3-gap-fill"></i> Categories
                    <i class="bi bi-chevron-down small"></i>
                </a>
                <div class="mega-strip-scroller">
                    <button type="button" class="mega-strip-arrow is-prev" aria-label="Previous categories">
                        <i class="bi bi-chevron-left"></i>
                    </button>
                    <div class="mega-strip-track">
                        <?php foreach ($megaTree as $navCat): ?>
                            <a href="<?= site_url('shop?category=' . esc($navCat['slug'])) ?>"
                               class="category-nav-link mega-strip-l1"
                               data-mega="<?= esc($navCat['slug'], 'attr') ?>">
                                <?= esc($navCat['name']) ?>
                            </a>
                        <?php endforeach; ?>
                    </div>
                    <button type="button" class="mega-strip-arrow is-next" aria-label="Next categories">
                        <i class="bi bi-chevron-right"></i>
                    </button>
                </div>
                <div class="mega-panel" role="menu">
                        <?php if (empty($megaTree)): ?>
                            <div class="p-4 text-muted small">Add categories in Admin → Categories (up to 4 levels).</div>
                        <?php else: ?>
                            <ul class="mega-l1 list-unstyled mb-0">
                                <?php foreach ($megaTree as $i => $l1): ?>
                                    <li class="mega-l1-item <?= $i === 0 ? 'is-active' : '' ?>" data-mega="<?= esc($l1['slug'], 'attr') ?>">
                                        <a class="mega-l1-link" href="<?= site_url('shop?category=' . esc($l1['slug'])) ?>">
                                            <span><i class="bi <?= esc($l1['icon'] ?: 'bi-grid') ?> me-2"></i><?= esc($l1['name']) ?></span>
                                            <i class="bi bi-chevron-right small text-muted"></i>
                                        </a>
                                    </li>
                                <?php endforeach; ?>
                            </ul>
                            <div class="mega-panes">
                                <?php foreach ($megaTree as $i => $l1): ?>
                                    <div class="mega-l2-grid <?= $i === 0 ? 'is-active' : '' ?>" data-mega="<?= esc($l1['slug'], 'attr') ?>">
                                        <?php if (empty($l1['children'])): ?>
                                            <div class="mega-col">
                                                <a class="mega-l2" href="<?= site_url('shop?category=' . esc($l1['slug'])) ?>">Shop all <?= esc($l1['name']) ?></a>
                                            </div>
                                        <?php endif; ?>
                                        <?php foreach ($l1['children'] ?? [] as $l2): ?>
                                            <div class="mega-col">
                                                <a class="mega-l2" href="<?= site_url('shop?category=' . esc($l2['slug'])) ?>"><?= esc($l2['name']) ?></a>
                                                <?php foreach ($l2['children'] ?? [] as $l3): ?>
                                                    <a class="mega-l3" href="<?= site_url('shop?category=' . esc($l3['slug'])) ?>"><?= esc($l3['name']) ?></a>
                                                    <?php if (!empty($l3['children'])): ?>
                                                        <div class="mega-l4">
                                                            <?php foreach ($l3['children'] as $l4): ?>
                                                                <a href="<?= site_url('shop?category=' . esc($l4['slug'])) ?>"><?= esc($l4['name']) ?></a>
                                                            <?php endforeach; ?>
                                                        </div>
                                                    <?php endif; ?>
                                                <?php endforeach; ?>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                </div>
            </div>
            <a href="<?= site_url('shop') ?>" class="text-solqam text-decoration-none small fw-bold d-none d-xl-inline mega-pin ms-2">
                All Categories <i class="bi bi-chevron-right"></i>
            </a>
        </div>
    </div>
</nav>

<div class="d-md-none border-bottom bg-white">
    <div class="container py-2">
        <button class="btn btn-solqam-outline btn-sm w-100 rounded-pill" type="button" data-bs-toggle="offcanvas" data-bs-target="#solqamCatDrawer">
            <i class="bi bi-grid-3x3-gap me-1"></i> Categories
        </button>
    </div>
</div>
<div class="offcanvas offcanvas-start" tabindex="-1" id="solqamCatDrawer" aria-labelledby="solqamCatLabel">
    <div class="offcanvas-header border-bottom">
        <h5 class="offcanvas-title fw-bold" id="solqamCatLabel">Categories</h5>
        <button type="button" class="btn-close" data-bs-dismiss="offcanvas" aria-label="Close"></button>
    </div>
    <div class="offcanvas-body pt-2">
        <a class="cat-m-link cat-m-flash" href="<?= site_url('shop?sort=best_selling') ?>"><i class="bi bi-lightning-charge-fill me-2"></i>Flash Deals</a>
        <a class="cat-m-link fw-bold" href="<?= site_url('shop') ?>"><i class="bi bi-grid-3x3-gap-fill me-2"></i>All Categories</a>
        <?php foreach ($megaTree as $i => $l1): ?>
            <?php
            $cid = 'catm_' . preg_replace('/[^a-z0-9]+/i', '_', (string) $l1['slug']) . '_' . $i;
            $kids = $l1['children'] ?? [];
            ?>
            <?php if ($kids === []): ?>
                <a class="cat-m-link" href="<?= site_url('shop?category=' . esc($l1['slug'])) ?>">
                    <i class="bi <?= esc($l1['icon'] ?: 'bi-grid') ?> me-2"></i><?= esc($l1['name']) ?>
                </a>
            <?php else: ?>
                <div class="cat-m-group">
                    <button class="cat-m-toggle" type="button" data-bs-toggle="collapse" data-bs-target="#<?= esc($cid, 'attr') ?>" aria-expanded="false">
                        <span><i class="bi <?= esc($l1['icon'] ?: 'bi-grid') ?> me-2"></i><?= esc($l1['name']) ?></span>
                        <i class="bi bi-chevron-down"></i>
                    </button>
                    <div class="collapse" id="<?= esc($cid, 'attr') ?>">
                        <a class="cat-m-sub" href="<?= site_url('shop?category=' . esc($l1['slug'])) ?>">Shop all <?= esc($l1['name']) ?></a>
                        <?php foreach ($kids as $l2): ?>
                            <a class="cat-m-sub" href="<?= site_url('shop?category=' . esc($l2['slug'])) ?>"><?= esc($l2['name']) ?></a>
                            <?php foreach ($l2['children'] ?? [] as $l3): ?>
                                <a class="cat-m-sub is-l3" href="<?= site_url('shop?category=' . esc($l3['slug'])) ?>"><?= esc($l3['name']) ?></a>
                            <?php endforeach; ?>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endif; ?>
        <?php endforeach; ?>
    </div>
</div>
