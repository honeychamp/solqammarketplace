<?= $this->extend('layouts/main') ?>

<?= $this->section('content') ?>
<div class="container py-4">
    <!-- Breadcrumb & Results Bar -->
    <nav aria-label="breadcrumb" class="mb-3">
        <ol class="breadcrumb small">
            <li class="breadcrumb-item"><a href="<?= site_url('/') ?>" class="text-decoration-none text-solqam">Home</a></li>
            <li class="breadcrumb-item active" aria-current="page">Catalog &amp; Shop</li>
        </ol>
    </nav>

    <div class="row g-4">
        <!-- Daraz-Style Sidebar Filters -->
        <div class="col-lg-3">
            <div class="card border-0 shadow-sm rounded-4 p-4 bg-white mb-3">
                <div class="d-flex justify-content-between align-items-center mb-3 pb-2 border-bottom">
                    <h6 class="fw-bold mb-0 text-dark"><i class="bi bi-sliders me-2 text-solqam"></i>Filter By</h6>
                    <a href="<?= site_url('shop') ?>" class="text-solqam-accent small text-decoration-none fw-bold">Reset All</a>
                </div>

                <form action="<?= site_url('shop') ?>" method="GET">
                    <?php if (!empty($filters['search'])): ?>
                        <input type="hidden" name="q" value="<?= esc($filters['search']) ?>">
                    <?php endif; ?>

                    <!-- Category Selector -->
                    <div class="mb-4">
                        <label class="form-label small fw-bold text-muted text-uppercase mb-2">Categories</label>
                        <div class="d-flex flex-column gap-1">
                            <a href="<?= site_url('shop' . (!empty($filters['search']) ? '?q=' . urlencode($filters['search']) : '')) ?>" class="text-decoration-none small py-1 px-2 rounded-2 <?= empty($filters['category_id']) && empty($filters['category_slug']) ? 'bg-solqam text-white fw-bold' : 'text-dark hover-bg' ?>">
                                All Categories
                            </a>
                            <?php foreach ($categories as $cat): ?>
                                <?php $isSelected = ($filters['category_id'] == $cat['id'] || $filters['category_slug'] == $cat['slug']); ?>
                                <a href="<?= site_url('shop?category=' . esc($cat['slug']) . (!empty($filters['search']) ? '&q=' . urlencode($filters['search']) : '')) ?>" class="text-decoration-none small py-1 px-2 rounded-2 d-flex justify-content-between align-items-center <?= $isSelected ? 'bg-solqam text-white fw-bold' : 'text-secondary hover-bg' ?>">
                                    <span><?= esc($cat['name']) ?></span>
                                    <?php if ($isSelected): ?>
                                        <i class="bi bi-check2"></i>
                                    <?php endif; ?>
                                </a>
                                <?php foreach ($cat['children'] ?? [] as $child): ?>
                                    <?php $childSel = ($filters['category_id'] == $child['id'] || $filters['category_slug'] == $child['slug']); ?>
                                    <a href="<?= site_url('shop?category=' . esc($child['slug']) . (!empty($filters['search']) ? '&q=' . urlencode($filters['search']) : '')) ?>" class="text-decoration-none small py-1 ps-3 pe-2 rounded-2 <?= $childSel ? 'bg-solqam text-white fw-bold' : 'text-muted' ?>">
                                        — <?= esc($child['name']) ?>
                                    </a>
                                <?php endforeach; ?>
                            <?php endforeach; ?>
                        </div>
                    </div>

                    <!-- Price Filter -->
                    <div class="mb-4">
                        <label class="form-label small fw-bold text-muted text-uppercase mb-2">Price (PKR)</label>
                        <div class="input-group input-group-sm mb-2">
                            <span class="input-group-text bg-light">Min</span>
                            <input type="number" name="min_price" class="form-control" placeholder="0" value="<?= esc($filters['min_price'] ?? '') ?>">
                        </div>
                        <div class="input-group input-group-sm">
                            <span class="input-group-text bg-light">Max</span>
                            <input type="number" name="max_price" class="form-control" placeholder="e.g. 15000" value="<?= esc($filters['max_price'] ?? '') ?>">
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
                        <input class="form-check-input" type="checkbox" name="mall" value="1" id="mallOnly" <?= !empty($filters['mall']) ? 'checked' : '' ?>>
                        <label class="form-check-label small" for="mallOnly">Solqam Mall only</label>
                    </div>
                    <div class="form-check mb-4">
                        <input class="form-check-input" type="checkbox" name="sponsored" value="1" id="sponsoredOnly" <?= !empty($filters['sponsored']) ? 'checked' : '' ?>>
                        <label class="form-check-label small" for="sponsoredOnly">Sponsored listings</label>
                    </div>

                    <!-- Sort Filter -->
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
            </div>

            <!-- Cashback Assurance Widget -->
            <div class="card border-0 rounded-4 p-3 text-white shadow-sm" style="background: linear-gradient(135deg, #071C8A 0%, #0B30E6 100%);">
                <div class="d-flex align-items-center gap-2 mb-2">
                    <i class="bi bi-wallet2 text-warning fs-4"></i>
                    <h6 class="fw-bold mb-0">Wallet cashback</h6>
                </div>
                <p class="small mb-0 text-white-50">Each product lists its own cashback %. Prepaid: credited instantly. COD / Pay later: after delivery.</p>
            </div>
        </div>

        <!-- Products Grid & Sort Header -->
        <div class="col-lg-9">
            <!-- Toolbar -->
            <div class="card border-0 shadow-sm rounded-4 p-3 bg-white mb-3">
                <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
                    <div>
                        <span class="text-muted small">Showing results:</span>
                        <strong class="text-dark ms-1"><?= number_format((int) ($catalogTotal ?? count($products))) ?> Products Found</strong>
                        <?php if (!empty($filters['search'])): ?>
                            <span class="badge bg-light text-solqam border ms-2">Keyword: "<?= esc($filters['search']) ?>"</span>
                        <?php endif; ?>
                        <?php if (!empty($searchHint)): ?>
                            <div class="small text-muted mt-1">Showing close matches for “<?= esc($searchHint) ?>”</div>
                        <?php endif; ?>
                    </div>
                    <div class="d-flex align-items-center gap-2">
                        <span class="small text-muted d-none d-sm-inline">Verified Pakistani Vendors</span>
                        <span class="badge bg-success-subtle text-success border border-success-subtle">
                            <i class="bi bi-patch-check-fill me-1"></i> COD Available
                        </span>
                    </div>
                </div>
            </div>

            <?php if (empty($products)): ?>
                <div class="text-center py-5 bg-white rounded-4 shadow-sm border p-5">
                    <i class="bi bi-search fs-1 text-muted mb-3 d-block"></i>
                    <h5 class="fw-bold text-dark">No products yet</h5>
                    <p class="text-muted small mb-4">Sellers can add products from Seller Hub. Shop will show them here.</p>
                    <a href="<?= site_url('shop') ?>" class="btn btn-solqam rounded-pill px-4">Browse Full Catalog</a>
                </div>
            <?php else: ?>
                <div class="row g-3">
                    <?php foreach ($products as $product): ?>
                        <?php
                        $origPrice = !empty($product['compare_at_price']) ? (float) $product['compare_at_price'] : (float) $product['price'];
                        ?>
                        <div class="col-6 col-md-4">
                            <div class="solqam-product-card">
                                <div class="card-media">
                                    <img src="<?= esc($product['primary_image'] ?: base_url('assets/images/product-placeholder.svg')) ?>" alt="<?= esc($product['name']) ?>" loading="lazy">
                                    <?php if (!empty($product['is_mall'])): ?>
                                    <span class="solqam-mall-badge">Mall</span>
                                    <?php endif; ?>
                                    <?= cashback_chip($product) ?>
                                    <?php if (!empty($product['is_sponsored'])): ?>
                                    <span class="discount-chip">Ad</span>
                                    <?php elseif (!empty($product['compare_at_price']) && $product['compare_at_price'] > $product['price']): ?>
                                    <span class="discount-chip">-<?= round((($product['compare_at_price'] - $product['price']) / $product['compare_at_price']) * 100) ?>%</span>
                                    <?php endif; ?>
                                </div>
                                <div class="card-body">
                                    <span class="product-category-tag"><?= esc($product['category_name'] ?? 'General') ?></span>
                                    <a href="<?= site_url('product/' . $product['id']) ?>" class="product-title" title="<?= esc($product['name']) ?>">
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

                                    <div class="small text-muted mb-2">
                                        <i class="bi bi-shop text-solqam me-1"></i> <?= esc($product['store_name'] ?? 'Vendor Store') ?>
                                    </div>

                                    <div class="price-row">
                                        <div>
                                            <div class="price-current">
                                                <span class="currency">Rs.</span><?= number_format($product['price'], 0) ?>
                                            </div>
                                            <?php if ($origPrice > (float) $product['price']): ?>
                                            <div class="price-original">
                                                Rs. <?= number_format($origPrice, 0) ?>
                                            </div>
                                            <?php endif; ?>
                                            <span class="free-shipping-tag">Free Delivery</span>
                                            <div class="small text-success fw-semibold mt-1"><?= esc(cashback_percent_label($product)) ?> cashback</div>
                                        </div>
                                        <div class="d-flex gap-1">
                                        <a href="<?= site_url('compare/toggle/' . $product['id']) ?>" class="btn btn-outline-secondary btn-sm p-2 rounded-circle" title="Compare" style="width: 38px; height: 38px;">
                                            <i class="bi bi-plus-slash-minus"></i>
                                        </a>
                                        <form action="<?= site_url('cart/add') ?>" method="POST" class="m-0">
                                            <?= csrf_field() ?>
                                            <input type="hidden" name="product_id" value="<?= $product['id'] ?>">
                                            <input type="hidden" name="quantity" value="1">
                                            <button type="submit" class="btn btn-solqam btn-sm p-2 rounded-circle" title="Add to Cart" style="width: 38px; height: 38px;">
                                                <i class="bi bi-cart-plus fs-6"></i>
                                            </button>
                                        </form>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
                <?php
                $catalogPages = (int) ($catalogPages ?? 1);
                $pageNow = (int) ($filters['page'] ?? 1);
                $qs = $filters;
                unset($qs['page'], $qs['limit'], $qs['category_ids']);
                $qs = array_filter($qs, static fn ($v) => $v !== null && $v !== '' && $v !== []);
                if ($catalogPages > 1):
                    $from = max(1, $pageNow - 4);
                    $to = min($catalogPages, $pageNow + 4);
                ?>
                <nav class="mt-4 d-flex justify-content-center">
                    <ul class="pagination flex-wrap">
                        <?php if ($pageNow > 1): ?>
                            <li class="page-item"><a class="page-link" href="<?= esc(site_url('shop') . '?' . http_build_query(array_merge($qs, ['page' => $pageNow - 1]))) ?>">Prev</a></li>
                        <?php endif; ?>
                        <?php for ($i = $from; $i <= $to; $i++): ?>
                            <li class="page-item <?= $i === $pageNow ? 'active' : '' ?>">
                                <a class="page-link" href="<?= esc(site_url('shop') . '?' . http_build_query(array_merge($qs, ['page' => $i]))) ?>"><?= $i ?></a>
                            </li>
                        <?php endfor; ?>
                        <?php if ($pageNow < $catalogPages): ?>
                            <li class="page-item"><a class="page-link" href="<?= esc(site_url('shop') . '?' . http_build_query(array_merge($qs, ['page' => $pageNow + 1]))) ?>">Next</a></li>
                        <?php endif; ?>
                    </ul>
                </nav>
                <?php endif; ?>
            <?php endif; ?>
            <?php if (!empty($recent)): ?>
            <div class="mt-4">
                <h6 class="fw-bold">Recently viewed</h6>
                <div class="d-flex flex-wrap gap-2">
                    <?php foreach ($recent as $r): ?>
                        <a class="badge rounded-pill text-bg-light border text-decoration-none" href="<?= site_url('product/' . $r['id']) ?>"><?= esc($r['name']) ?></a>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>
<?= $this->endSection() ?>
