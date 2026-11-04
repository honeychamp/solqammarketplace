<?php $fid = $filterId ?? 'desk'; ?>
<div class="d-flex justify-content-between align-items-center mb-3 pb-2 border-bottom">
    <h6 class="fw-bold mb-0 text-dark"><i class="bi bi-sliders me-2 text-solqam"></i>Filter By</h6>
    <a href="<?= site_url('shop') ?>" class="text-solqam-accent small text-decoration-none fw-bold">Reset All</a>
</div>
<form action="<?= site_url('shop') ?>" method="GET">
    <?php if (!empty($filters['search'])): ?>
        <input type="hidden" name="q" value="<?= esc($filters['search']) ?>">
    <?php endif; ?>
    <div class="mb-4">
        <label class="form-label small fw-bold text-muted text-uppercase mb-2">Categories</label>
        <div class="d-flex flex-column gap-1 shop-cat-list">
            <a href="<?= site_url('shop' . (!empty($filters['search']) ? '?q=' . urlencode($filters['search']) : '')) ?>" class="text-decoration-none small py-1 px-2 rounded-2 <?= empty($filters['category_id']) && empty($filters['category_slug']) ? 'bg-solqam text-white fw-bold' : 'text-dark hover-bg' ?>">
                All Categories
            </a>
            <?= view('shared/category_filter_nodes', [
                'nodes'   => $categories,
                'filters' => $filters,
                'depth'   => 0,
            ]) ?>
        </div>
    </div>
    <div class="mb-4">
        <label class="form-label small fw-bold text-muted text-uppercase mb-2">Price (PKR)</label>
        <div class="input-group input-group-sm mb-2">
            <span class="input-group-text bg-light">Min</span>
            <input type="number" name="min_price" class="form-control" placeholder="0" value="<?= esc($filters['min_price'] ?? '') ?>">
        </div>
        <div class="input-group input-group-sm">
            <span class="input-group-text bg-light">Max</span>
            <input type="number" name="max_price" class="form-control" placeholder="Max" value="<?= esc($filters['max_price'] ?? '') ?>">
        </div>
    </div>
    <?php if (!empty($brands)): ?>
    <div class="mb-4">
        <label class="form-label small fw-bold text-muted text-uppercase mb-2">Brand</label>
        <select name="brand" class="form-select form-select-sm rounded-3">
            <option value="">All brands</option>
            <?php foreach ($brands as $b): ?>
                <option value="<?= esc($b['brand']) ?>" <?= ($filters['brand'] ?? '') === $b['brand'] ? 'selected' : '' ?>><?= esc($b['brand']) ?></option>
            <?php endforeach; ?>
        </select>
    </div>
    <?php endif; ?>
    <div class="form-check mb-2">
        <input class="form-check-input" type="checkbox" name="mall" value="1" id="mallOnly_<?= esc($fid, 'attr') ?>" <?= !empty($filters['mall']) ? 'checked' : '' ?>>
        <label class="form-check-label small" for="mallOnly_<?= esc($fid, 'attr') ?>">Solqam Mall only</label>
    </div>
    <div class="form-check mb-4">
        <input class="form-check-input" type="checkbox" name="sponsored" value="1" id="sponsoredOnly_<?= esc($fid, 'attr') ?>" <?= !empty($filters['sponsored']) ? 'checked' : '' ?>>
        <label class="form-check-label small" for="sponsoredOnly_<?= esc($fid, 'attr') ?>">Sponsored listings</label>
    </div>
    <div class="mb-4">
        <label class="form-label small fw-bold text-muted text-uppercase mb-2">Sort By</label>
        <select name="sort" class="form-select form-select-sm rounded-3">
            <option value="latest" <?= ($filters['sort'] === 'latest') ? 'selected' : '' ?>>Latest Arrivals</option>
            <option value="price_low" <?= ($filters['sort'] === 'price_low') ? 'selected' : '' ?>>Price: Low to High</option>
            <option value="price_high" <?= ($filters['sort'] === 'price_high') ? 'selected' : '' ?>>Price: High to Low</option>
            <option value="name_asc" <?= ($filters['sort'] === 'name_asc') ? 'selected' : '' ?>>Name: A to Z</option>
            <option value="best_selling" <?= ($filters['sort'] === 'best_selling') ? 'selected' : '' ?>>Best Selling</option>
            <option value="rating" <?= ($filters['sort'] === 'rating') ? 'selected' : '' ?>>Top Rated</option>
        </select>
    </div>
    <button type="submit" class="btn btn-solqam btn-sm w-100 rounded-pill py-2">
        <i class="bi bi-funnel-fill me-1"></i> Apply Filters
    </button>
</form>
