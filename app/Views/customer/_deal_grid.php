<?php
$dealProducts = $dealProducts ?? [];
if ($dealProducts === []): ?>
    <div class="col-12 text-center py-5">
        <div class="sf-empty">
            <i class="bi bi-lightning-charge text-solqam fs-1 mb-2 d-block"></i>
            <h6 class="fw-bold text-dark"><?= esc($emptyTitle ?? 'No deals live yet') ?></h6>
            <p class="text-muted small mb-0"><?= esc($emptyCopy ?? 'When a campaign is running and deals are approved, they show here.') ?></p>
        </div>
    </div>
<?php else: ?>
    <?php foreach ($dealProducts as $product): ?>
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
                        <i class="bi bi-star"></i><i class="bi bi-star"></i><i class="bi bi-star"></i><i class="bi bi-star"></i><i class="bi bi-star"></i>
                        <span class="review-count">(0)</span>
                    </div>
                    <div class="stock-progress-wrap mb-2">
                        <div class="d-flex justify-content-between">
                            <span><?= $soldCount ?> Sold</span>
                            <span><?= max(1, (int) ($product['stock'] ?? 1)) ?> Left</span>
                        </div>
                        <div class="progress solqam-progress">
                            <div class="progress-bar" style="width: <?= min(90, max(25, ($soldCount * 3))) ?>%;"></div>
                        </div>
                    </div>
                    <div class="price-row">
                        <div>
                            <div class="price-current"><span class="currency">Rs.</span><?= number_format((float) $salePrice, 0) ?></div>
                            <div class="price-original">Rs. <?= number_format((float) $origPrice, 0) ?></div>
                            <?= delivery_tag_html() ?>
                            <div class="small text-success fw-semibold mt-1"><?= esc(cashback_percent_label($product)) ?> cashback</div>
                        </div>
                        <form action="<?= site_url('cart/add') ?>" method="POST" class="m-0">
                            <?= csrf_field() ?>
                            <input type="hidden" name="product_id" value="<?= (int) $pid ?>">
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
