<?php $megaTree = category_tree(); ?>
<nav class="solqam-category-bar d-none d-md-block">
    <div class="container mega-bar-inner">
        <div class="d-flex align-items-center justify-content-between">
            <div class="d-flex align-items-center gap-1 overflow-x-auto mega-strip">
                <a href="<?= site_url('shop?sort=best_selling') ?>" class="category-nav-link text-solqam-accent fw-bold">
                    <i class="bi bi-lightning-charge-fill"></i> Flash Deals
                </a>
                <div class="mega-wrap">
                    <a href="<?= site_url('shop') ?>" class="category-nav-link mega-all-btn">
                        <i class="bi bi-grid-3x3-gap-fill"></i> Categories
                        <i class="bi bi-chevron-down small"></i>
                    </a>
                    <?php foreach ($megaTree as $navCat): ?>
                        <a href="<?= site_url('shop?category=' . esc($navCat['slug'])) ?>"
                           class="category-nav-link mega-strip-l1"
                           data-mega="<?= esc($navCat['slug'], 'attr') ?>">
                            <?= esc($navCat['name']) ?>
                        </a>
                    <?php endforeach; ?>
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
            </div>
            <a href="<?= site_url('shop') ?>" class="text-solqam text-decoration-none small fw-bold d-none d-xl-inline">
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
<div class="offcanvas offcanvas-start" tabindex="-1" id="solqamCatDrawer">
    <div class="offcanvas-header">
        <h5 class="offcanvas-title fw-bold">Categories</h5>
        <button type="button" class="btn-close" data-bs-dismiss="offcanvas"></button>
    </div>
    <div class="offcanvas-body pt-0">
        <a class="d-block py-2 fw-semibold text-solqam-accent text-decoration-none" href="<?= site_url('shop?sort=best_selling') ?>">Flash Deals</a>
        <?php
        $walkMobile = static function (array $nodes, int $depth) use (&$walkMobile): void {
            foreach ($nodes as $n) {
                $pad = 8 + $depth * 14;
                echo '<a class="d-block py-2 text-decoration-none ' . ($depth === 0 ? 'fw-bold text-dark' : 'text-secondary small') . '" style="padding-left:' . $pad . 'px" href="' . site_url('shop?category=' . esc($n['slug'])) . '">' . esc($n['name']) . '</a>';
                if (! empty($n['children'])) {
                    $walkMobile($n['children'], $depth + 1);
                }
            }
        };
        $walkMobile($megaTree, 0);
        ?>
    </div>
</div>
