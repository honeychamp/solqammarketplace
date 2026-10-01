<?php
$product   = $product ?? [];
$colClass  = $colClass ?? 'col-6 col-md-4 col-lg-2';
$pid       = (int) ($product['id'] ?? 0);
$listPrice = (float) ($product['flash_price'] ?? $product['price'] ?? 0);
$origPrice = !empty($product['compare_at_price']) ? (float) $product['compare_at_price'] : (float) ($product['price'] ?? 0);
if (!empty($product['flash_price']) && $origPrice < (float) $product['price']) {
    $origPrice = (float) $product['price'];
}
$img = trim((string) ($product['primary_image'] ?? ''));
if ($img !== '' && ! preg_match('#^https?://#i', $img) && strpos($img, '//') !== 0) {
    $img = base_url(ltrim($img, '/'));
}
if ($img === '') {
    $img = base_url('assets/images/product-placeholder.svg');
}
?>
<div class="<?= esc($colClass, 'attr') ?>">
    <div class="solqam-product-card">
        <div class="card-media">
            <img src="<?= esc($img) ?>" alt="<?= esc($product['name'] ?? '') ?>" loading="lazy">
            <?php if (!empty($product['is_mall'])): ?>
                <span class="solqam-mall-badge">Mall</span>
            <?php endif; ?>
            <?= cashback_chip($product) ?>
            <?php if (!empty($product['is_sponsored'])): ?>
                <span class="discount-chip">Ad</span>
            <?php elseif ($origPrice > $listPrice && $origPrice > 0): ?>
                <span class="discount-chip">-<?= round((($origPrice - $listPrice) / $origPrice) * 100) ?>%</span>
            <?php endif; ?>
        </div>
        <div class="card-body">
            <span class="product-category-tag"><?= esc($product['category_name'] ?? 'General') ?></span>
            <a href="<?= site_url('product/' . $pid) ?>" class="product-title" title="<?= esc($product['name'] ?? '') ?>">
                <?= esc($product['name'] ?? '') ?>
            </a>
            <div class="star-rating">
                <i class="bi bi-star"></i><i class="bi bi-star"></i><i class="bi bi-star"></i><i class="bi bi-star"></i><i class="bi bi-star"></i>
                <span class="review-count">(0)</span>
            </div>
            <div class="small text-muted mb-2 text-truncate">
                <i class="bi bi-shop text-solqam me-1"></i> <?= esc($product['store_name'] ?? 'Vendor Store') ?>
            </div>
            <div class="price-row">
                <div>
                    <div class="price-current"><span class="currency">Rs.</span><?= number_format($listPrice, 0) ?></div>
                    <?php if ($origPrice > $listPrice): ?>
                        <div class="price-original">Rs. <?= number_format($origPrice, 0) ?></div>
                    <?php endif; ?>
                    <?= delivery_tag_html() ?>
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
