<?= $this->extend('layouts/main') ?>

<?= $this->section('content') ?>
<?php
$avg = (float) ($ratingStats['average'] ?? 0);
$rcount = (int) ($ratingStats['count'] ?? 0);
$displayPrice = !empty($flashPrice) ? (float) $flashPrice : (float) $product['price'];
$origPrice = !empty($product['compare_at_price']) ? (float) $product['compare_at_price'] : ($flashPrice ? (float) $product['price'] : 0);
$discountPct = ($origPrice > $displayPrice && $origPrice > 0) ? round((($origPrice - $displayPrice) / $origPrice) * 100) : 0;
$gallery = $product['images'] ?? [];
if ($gallery === [] && !empty($product['primary_image'])) {
    $gallery = [['image_path' => $product['primary_image']]];
}
$brandName = trim((string) ($product['brand'] ?? ''));
if (strcasecmp($brandName, 'No Brand') === 0) {
    $brandName = '';
}
$returnDays = (int) ($product['return_days'] ?? 7);
$warranty = trim((string) ($product['warranty_info'] ?? ''));
$sizeGuide = trim((string) ($product['size_guide'] ?? ''));
$colorFamily = $colorFamily ?? [];
$sizeOptions = $sizeOptions ?? [];
$sizeOrder = ['XXS' => 1, 'XS' => 2, 'S' => 3, 'M' => 4, 'L' => 5, 'XL' => 6, 'XXL' => 7, 'XXXL' => 8];
usort($sizeOptions, static function ($a, $b) use ($sizeOrder) {
    $ia = $sizeOrder[strtoupper($a)] ?? 50;
    $ib = $sizeOrder[strtoupper($b)] ?? 50;
    return $ia === $ib ? strnatcasecmp($a, $b) : $ia <=> $ib;
});
$variants = $product['variants'] ?? [];
$firstVariant = $variants[0] ?? null;
$shippingZones = $shippingZones ?? [];
$ratingBreakdown = $ratingBreakdown ?? [5 => 0, 4 => 0, 3 => 0, 2 => 0, 1 => 0];
$soldCount = (int) ($product['sold_count'] ?? 0);
?>
<div class="container py-3 pdp-page">
    <nav aria-label="breadcrumb" class="mb-3">
        <ol class="breadcrumb small mb-0">
            <li class="breadcrumb-item"><a href="<?= site_url('/') ?>" class="text-solqam text-decoration-none">Home</a></li>
            <li class="breadcrumb-item"><a href="<?= site_url('shop') ?>" class="text-solqam text-decoration-none">Shop</a></li>
            <li class="breadcrumb-item"><a href="<?= site_url('shop?category=' . esc($product['category_slug'] ?? '')) ?>" class="text-solqam text-decoration-none"><?= esc($product['category_name'] ?? 'Category') ?></a></li>
            <li class="breadcrumb-item active text-truncate" style="max-width: 280px;"><?= esc($product['name']) ?></li>
        </ol>
    </nav>

    <div class="row g-3 align-items-start">
        <div class="col-lg-5">
            <div class="card border-0 shadow-sm rounded-4 p-3 bg-white pdp-gallery">
                <div class="pdp-main-stage" role="button" data-bs-toggle="modal" data-bs-target="#pdpZoomModal">
                    <img id="mainProductImg" src="<?= esc($product['primary_image'] ?: base_url('assets/images/product-placeholder.svg')) ?>" alt="<?= esc($product['name']) ?>">
                    <?php if (!empty($product['is_mall'])): ?>
                    <span class="solqam-mall-badge position-absolute top-0 start-0 m-3">Solqam Mall</span>
                    <?php endif; ?>
                    <span class="pdp-zoom-hint"><i class="bi bi-zoom-in"></i> Click to enlarge</span>
                </div>
                <?php if ($gallery): ?>
                <div class="pdp-thumbs mt-3">
                    <?php foreach ($gallery as $i => $img): ?>
                        <button type="button" class="pdp-thumb <?= $i === 0 ? 'is-active' : '' ?>" data-src="<?= esc($img['image_path']) ?>">
                            <img src="<?= esc($img['image_path']) ?>" alt="Photo <?= $i + 1 ?>">
                        </button>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>
            </div>
        </div>

        <div class="col-lg-4">
            <div class="card border-0 shadow-sm rounded-4 p-4 bg-white">
                <h1 class="pdp-title"><?= esc($product['name']) ?></h1>

                <div class="d-flex align-items-center gap-2 flex-wrap mb-3 pb-3 border-bottom">
                    <?php if ($rcount > 0): ?>
                        <span class="text-warning">
                            <?php for ($i = 1; $i <= 5; $i++): ?>
                                <i class="bi bi-star<?= $i <= round($avg) ? '-fill' : '' ?>"></i>
                            <?php endfor; ?>
                        </span>
                        <strong><?= number_format($avg, 1) ?></strong>
                        <a href="#reviews-pane" class="js-pdp-tab small text-solqam text-decoration-none" data-tab="#reviews-tab"><?= $rcount ?> Ratings</a>
                    <?php else: ?>
                        <span class="text-muted small">No Ratings</span>
                    <?php endif; ?>
                    <span class="text-muted">|</span>
                    <a href="#qa-pane" class="js-pdp-tab small text-solqam text-decoration-none" data-tab="#qa-tab"><?= count($questions ?? []) ?> Answered Questions</a>
                    <?php if ($soldCount > 0): ?>
                    <span class="text-muted">|</span>
                    <span class="small text-muted"><?= $soldCount ?> Sold</span>
                    <?php endif; ?>
                </div>

                <div class="pdp-price-box mb-3">
                    <div class="d-flex align-items-baseline gap-2 flex-wrap">
                        <div class="pdp-price">Rs. <span id="displayPrice"><?= number_format($displayPrice, 0) ?></span></div>
                        <?php if ($discountPct > 0): ?>
                        <div class="text-muted text-decoration-line-through">Rs. <?= number_format($origPrice, 0) ?></div>
                        <span class="badge bg-solqam-accent">-<?= $discountPct ?>%</span>
                        <?php endif; ?>
                    </div>
                    <div class="small text-success mt-2 fw-semibold"><?= esc(cashback_percent_label($product)) ?> wallet cashback on this product</div>
                </div>

                <?php if ($brandName !== ''): ?>
                <div class="pdp-meta-row">
                    <span class="pdp-meta-label">Brand</span>
                    <a href="<?= site_url('shop?brand=' . rawurlencode($brandName)) ?>" class="text-solqam text-decoration-none fw-semibold"><?= esc($brandName) ?></a>
                    <span class="text-muted small">More from <?= esc($brandName) ?></span>
                </div>
                <?php endif; ?>

                <form action="<?= site_url('cart/add') ?>" method="POST" id="pdpBuyForm">
                    <?= csrf_field() ?>
                    <input type="hidden" name="product_id" value="<?= (int) $product['id'] ?>">
                    <?php if ($variants): ?>
                    <input type="hidden" name="variant_id" id="variantIdInput" value="<?= $firstVariant['id'] ?? '' ?>">
                    <?php endif; ?>

                    <?php if ($colorFamily): ?>
                    <div class="pdp-meta-row align-items-start">
                        <span class="pdp-meta-label">Color Family</span>
                        <div class="flex-grow-1">
                            <div class="d-flex flex-wrap gap-2" id="colorFamily">
                                <?php foreach ($colorFamily as $ci => $color): ?>
                                    <button type="button" class="pdp-opt <?= $ci === 0 ? 'is-active' : '' ?>" data-color="<?= esc($color) ?>"><?= esc($color) ?></button>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>
                    <?php endif; ?>

                    <?php if ($sizeOptions || $sizeGuide !== ''): ?>
                    <div class="pdp-meta-row align-items-start">
                        <span class="pdp-meta-label">Size</span>
                        <div class="flex-grow-1">
                            <?php if ($sizeOptions): ?>
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <span class="small text-muted" id="selectedSizeLabel"><?= esc($sizeOptions[0]) ?></span>
                                <?php if ($sizeGuide !== ''): ?>
                                <button type="button" class="btn btn-link btn-sm p-0 text-solqam" data-bs-toggle="modal" data-bs-target="#sizeGuideModal">Size Guide</button>
                                <?php endif; ?>
                            </div>
                            <div class="d-flex flex-wrap gap-2" id="sizeFamily">
                                <?php foreach ($sizeOptions as $si => $size): ?>
                                    <button type="button" class="pdp-opt <?= $si === 0 ? 'is-active' : '' ?>" data-size="<?= esc($size) ?>"><?= esc($size) ?></button>
                                <?php endforeach; ?>
                            </div>
                            <?php elseif ($sizeGuide !== ''): ?>
                            <div class="d-flex justify-content-end mb-2">
                                <button type="button" class="btn btn-link btn-sm p-0 text-solqam" data-bs-toggle="modal" data-bs-target="#sizeGuideModal">Size Guide</button>
                            </div>
                            <?php endif; ?>
                            <div class="small text-muted mt-2" id="variantStockHint"></div>
                        </div>
                    </div>
                    <?php endif; ?>

                    <div class="pdp-meta-row align-items-center">
                        <span class="pdp-meta-label">Quantity</span>
                        <div class="input-group" style="max-width: 160px;">
                            <button class="btn btn-outline-secondary" type="button" onclick="const q=document.getElementById('qtyInput'); if(q.value>1) q.value--;">-</button>
                            <input type="number" id="qtyInput" name="quantity" class="form-control text-center fw-bold" value="1" min="1" max="<?= (int) $product['stock'] ?>">
                            <button class="btn btn-outline-secondary" type="button" onclick="const q=document.getElementById('qtyInput'); if(parseInt(q.value,10)<parseInt(q.max,10)) q.value++;">+</button>
                        </div>
                    </div>

                    <?php if ((int) $product['stock'] > 0): ?>
                    <div class="d-flex gap-2 mt-3">
                        <button type="submit" name="buy_now" value="1" class="btn btn-solqam-accent flex-grow-1 py-2 fw-bold">Buy Now</button>
                        <button type="submit" class="btn btn-solqam flex-grow-1 py-2 fw-bold">Add to Cart</button>
                        <button type="submit" formaction="<?= site_url('wishlist/toggle') ?>" formmethod="post" class="btn btn-outline-danger">
                            <i class="bi bi-heart<?= !empty($inWishlist) ? '-fill' : '' ?>"></i>
                        </button>
                    </div>
                    <?php else: ?>
                        <div class="alert alert-danger mt-3 mb-0">Sold Out</div>
                    <?php endif; ?>
                </form>
            </div>
        </div>

        <div class="col-lg-3">
            <div class="card border-0 shadow-sm rounded-4 p-3 bg-white mb-3">
                <h6 class="fw-bold mb-3">Delivery Options</h6>
                <div class="d-flex gap-2 mb-3">
                    <i class="bi bi-geo-alt text-solqam"></i>
                    <div class="small">
                        <div class="fw-semibold">Ship to Pakistan</div>
                        <div class="text-muted">Standard courier (seller ships)</div>
                    </div>
                </div>
                <?php if ($shippingZones): ?>
                    <ul class="list-unstyled small mb-3">
                        <?php foreach ($shippingZones as $zone): ?>
                            <li class="d-flex justify-content-between border-bottom py-1">
                                <span><?= esc($zone['city']) ?></span>
                                <span class="text-muted"><?= esc($zone['eta_days'] ?? '3-5') ?> days · Rs. <?= number_format((float) $zone['rate'], 0) ?></span>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                <?php else: ?>
                    <p class="small text-muted">Karachi, Lahore, Islamabad: 2–3 days. Nationwide: 3–5 days.</p>
                <?php endif; ?>
                <div class="d-flex gap-2">
                    <i class="bi bi-cash-stack text-success"></i>
                    <div class="small"><strong>Cash on Delivery</strong> available on checkout.</div>
                </div>
            </div>

            <div class="card border-0 shadow-sm rounded-4 p-3 bg-white mb-3">
                <h6 class="fw-bold mb-3">Return &amp; Warranty</h6>
                <div class="d-flex gap-2 <?= $warranty !== '' ? 'mb-2' : '' ?>">
                    <i class="bi bi-arrow-counterclockwise text-solqam-accent"></i>
                    <div class="small">
                        <strong><?= $returnDays ?> days easy return</strong>
                        <div class="text-muted"><a href="<?= site_url('returns-policy') ?>">Change of mind not always applicable.</a></div>
                    </div>
                </div>
                <?php if ($warranty !== ''): ?>
                <div class="d-flex gap-2">
                    <i class="bi bi-shield-check text-solqam"></i>
                    <div class="small">
                        <strong>Warranty</strong>
                        <div class="text-muted"><?= esc($warranty) ?></div>
                    </div>
                </div>
                <?php endif; ?>
            </div>

            <div class="card border-0 shadow-sm rounded-4 p-3 bg-white">
                <h6 class="fw-bold mb-3">Sold by</h6>
                <div class="d-flex gap-2 mb-2">
                    <div class="bg-solqam text-white rounded-circle d-flex align-items-center justify-content-center" style="width:40px;height:40px;"><i class="bi bi-shop"></i></div>
                    <div>
                        <div class="fw-bold"><?= esc($product['store_name'] ?? 'Solqam Seller') ?></div>
                        <div class="small text-muted"><?= (int) ($followerCount ?? 0) ?> Followers</div>
                    </div>
                </div>
                <div class="d-flex flex-wrap gap-2">
                    <a class="btn btn-sm btn-outline-primary" href="<?= site_url('store/' . $product['seller_id']) ?>">Visit Store</a>
                    <a class="btn btn-sm btn-solqam" href="<?= site_url('messages/start?seller_id=' . (int) $product['seller_id'] . '&product_id=' . (int) $product['id']) ?>">Chat</a>
                    <form action="<?= site_url('store/follow') ?>" method="POST">
                        <?= csrf_field() ?>
                        <input type="hidden" name="seller_id" value="<?= (int) $product['seller_id'] ?>">
                        <button class="btn btn-sm <?= !empty($isFollowing) ? 'btn-solqam' : 'btn-outline-secondary' ?>"><?= !empty($isFollowing) ? 'Following' : 'Follow' ?></button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <div class="card border-0 shadow-sm rounded-4 p-4 bg-white mt-4" id="pdpTabsCard">
        <ul class="nav nav-pills mb-4 gap-2 flex-wrap" id="productDetailTabs" role="tablist">
            <li class="nav-item"><button class="nav-link active rounded-pill px-4 fw-bold" id="desc-tab" data-bs-toggle="tab" data-bs-target="#desc-pane" type="button">Description</button></li>
            <li class="nav-item"><button class="nav-link rounded-pill px-4 fw-bold" id="spec-tab" data-bs-toggle="tab" data-bs-target="#spec-pane" type="button">Details</button></li>
            <li class="nav-item"><button class="nav-link rounded-pill px-4 fw-bold" id="reviews-tab" data-bs-toggle="tab" data-bs-target="#reviews-pane" type="button">Reviews (<?= $rcount ?>)</button></li>
            <li class="nav-item"><button class="nav-link rounded-pill px-4 fw-bold" id="qa-tab" data-bs-toggle="tab" data-bs-target="#qa-pane" type="button">Questions (<?= count($questions ?? []) ?>)</button></li>
        </ul>

        <div class="tab-content">
            <div class="tab-pane fade show active" id="desc-pane">
                <?php if (!empty($highlights)): ?>
                    <ul class="mb-3">
                        <?php foreach ($highlights as $line): ?>
                            <li><?= esc($line) ?></li>
                        <?php endforeach; ?>
                    </ul>
                <?php endif; ?>
                <div class="text-secondary" style="white-space: pre-line;"><?= esc($product['description'] ?? '') ?></div>
            </div>

            <div class="tab-pane fade" id="spec-pane">
                <table class="table table-bordered small mb-0">
                    <?php foreach ($specs as $label => $value): ?>
                        <tr>
                            <th class="bg-light" style="width: 34%;"><?= esc($label) ?></th>
                            <td><?= esc(is_array($value) ? implode(', ', $value) : $value) ?></td>
                        </tr>
                    <?php endforeach; ?>
                </table>
            </div>

            <div class="tab-pane fade" id="reviews-pane">
                <div class="row g-4 mb-4">
                    <div class="col-md-3 text-center">
                        <div class="display-5 fw-bold"><?= $rcount ? number_format($avg, 1) : '—' ?></div>
                        <div class="text-warning mb-1">
                            <?php for ($i = 1; $i <= 5; $i++): ?>
                                <i class="bi bi-star<?= $rcount && $i <= round($avg) ? '-fill' : '' ?>"></i>
                            <?php endfor; ?>
                        </div>
                        <div class="small text-muted"><?= $rcount ?> ratings</div>
                    </div>
                    <div class="col-md-9">
                        <?php foreach ([5, 4, 3, 2, 1] as $star): ?>
                            <?php $pct = $rcount ? round(($ratingBreakdown[$star] / $rcount) * 100) : 0; ?>
                            <div class="d-flex align-items-center gap-2 small mb-1">
                                <span style="width: 28px;"><?= $star ?>★</span>
                                <div class="progress flex-grow-1" style="height: 8px;"><div class="progress-bar bg-warning" style="width: <?= $pct ?>%"></div></div>
                                <span class="text-muted" style="width: 36px;"><?= (int) $ratingBreakdown[$star] ?></span>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>

                <?php if ($canReview && $deliveredOrderId): ?>
                    <div class="p-4 bg-light rounded-4 border mb-4">
                        <h6 class="fw-bold mb-2">Write a review</h6>
                        <form action="<?= site_url('account/orders/' . $deliveredOrderId . '/review') ?>" method="POST" enctype="multipart/form-data">
                            <?= csrf_field() ?>
                            <input type="hidden" name="product_id" value="<?= $product['id'] ?>">
                            <div class="row g-3 mb-3">
                                <div class="col-md-3">
                                    <select name="rating" class="form-select">
                                        <option value="5">5 stars</option>
                                        <option value="4">4 stars</option>
                                        <option value="3">3 stars</option>
                                        <option value="2">2 stars</option>
                                        <option value="1">1 star</option>
                                    </select>
                                </div>
                                <div class="col-md-9">
                                    <input type="text" name="comment" class="form-control" placeholder="Share product quality, fit, and delivery..." required>
                                </div>
                                <div class="col-12">
                                    <input type="file" name="review_image" class="form-control" accept="image/*">
                                </div>
                            </div>
                            <button type="submit" class="btn btn-solqam rounded-pill px-4">Submit Review</button>
                        </form>
                    </div>
                <?php endif; ?>

                <?php if (empty($reviews)): ?>
                    <p class="text-muted mb-0">No customer reviews yet.</p>
                <?php else: ?>
                    <?php foreach ($reviews as $rev): ?>
                        <div class="border-bottom py-3">
                            <div class="d-flex justify-content-between">
                                <strong><?= esc($rev['reviewer_name'] ?? 'Customer') ?></strong>
                                <span class="text-warning small">
                                    <?php for ($i = 1; $i <= 5; $i++): ?>
                                        <i class="bi bi-star<?= $i <= $rev['rating'] ? '-fill' : '' ?>"></i>
                                    <?php endfor; ?>
                                </span>
                            </div>
                            <p class="mb-1"><?= esc($rev['comment']) ?></p>
                            <?php if (!empty($rev['image_path'])): ?>
                                <img src="<?= esc($rev['image_path']) ?>" alt="Review photo" class="rounded-3 mb-2" style="max-width:160px;max-height:160px;object-fit:cover;">
                            <?php endif; ?>
                            <small class="text-muted"><?= date('d M Y', strtotime($rev['created_at'])) ?></small>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>

            <div class="tab-pane fade" id="qa-pane">
                <form action="<?= site_url('product/' . $product['id'] . '/question') ?>" method="POST" class="mb-4">
                    <?= csrf_field() ?>
                    <label class="form-label fw-bold">Ask a question</label>
                    <div class="input-group">
                        <input type="text" name="question" class="form-control" placeholder="Does this run true to size?" required>
                        <button class="btn btn-solqam" type="submit">Ask</button>
                    </div>
                </form>
                <?php if (empty($questions)): ?>
                    <p class="text-muted small mb-0">No questions yet.</p>
                <?php else: ?>
                    <?php foreach ($questions as $qa): ?>
                        <div class="border-bottom py-3">
                            <div class="fw-semibold"><i class="bi bi-question-circle text-solqam me-1"></i> <?= esc($qa['question']) ?></div>
                            <div class="small text-muted mb-1"><?= esc($qa['asker_name'] ?? 'Customer') ?> · <?= date('d M Y', strtotime($qa['created_at'])) ?></div>
                            <?php if (!empty($qa['answer'])): ?>
                                <div class="bg-light rounded-3 p-2 small"><strong>Seller:</strong> <?= esc($qa['answer']) ?></div>
                            <?php else: ?>
                                <div class="small text-warning">Awaiting seller reply</div>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <?php if (!empty($relatedProducts)): ?>
    <h5 class="fw-bold mt-5 mb-3">You may also like</h5>
    <div class="row g-3">
        <?php foreach ($relatedProducts as $rel): ?>
            <div class="col-6 col-md-3">
                <a href="<?= site_url('product/' . $rel['id']) ?>" class="text-decoration-none">
                    <div class="solqam-product-card">
                        <div class="card-media">
                            <img src="<?= esc($rel['primary_image'] ?: base_url('assets/images/product-placeholder.svg')) ?>" alt="<?= esc($rel['name']) ?>">
                            <?= cashback_chip($rel) ?>
                        </div>
                        <div class="card-body">
                            <div class="product-title"><?= esc($rel['name']) ?></div>
                            <div class="price-current">Rs. <?= number_format((float) $rel['price'], 0) ?></div>
                            <div class="small text-success fw-semibold mt-1"><?= esc(cashback_percent_label($rel)) ?> cashback</div>
                        </div>
                    </div>
                </a>
            </div>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>
</div>

<div class="modal fade" id="pdpZoomModal" tabindex="-1">
    <div class="modal-dialog modal-xl modal-dialog-centered">
        <div class="modal-content bg-dark border-0">
            <div class="modal-header border-0">
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body text-center">
                <img id="pdpZoomImg" src="<?= esc($product['primary_image'] ?: base_url('assets/images/product-placeholder.svg')) ?>" class="img-fluid" alt="<?= esc($product['name']) ?>">
            </div>
        </div>
    </div>
</div>

<?php if ($sizeGuide !== ''): ?>
<div class="modal fade" id="sizeGuideModal" tabindex="-1">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Size Guide</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <pre class="mb-0 small" style="white-space: pre-wrap; font-family: inherit;"><?= esc($sizeGuide) ?></pre>
            </div>
        </div>
    </div>
</div>
<?php endif; ?>
<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<script>
(function () {
    const variants = <?= json_encode($variants, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT) ?>;
    const variantInput = document.getElementById('variantIdInput');
    const priceEl = document.getElementById('displayPrice');
    const qty = document.getElementById('qtyInput');
    const stockHint = document.getElementById('variantStockHint');
    const basePrice = <?= json_encode($displayPrice) ?>;
    const baseStock = <?= (int) $product['stock'] ?>;
    let color = document.querySelector('#colorFamily .pdp-opt.is-active')?.dataset.color || '';
    let size = document.querySelector('#sizeFamily .pdp-opt.is-active')?.dataset.size || '';

    function findVariant() {
        if (!variants.length) return null;
        return variants.find(function (v) {
            const c = (v.color || '').trim();
            const s = (v.size || '').trim();
            const colorOk = !color || !c || c === color;
            const sizeOk = !size || !s || s === size;
            return colorOk && sizeOk;
        }) || null;
    }

    function applyVariant() {
        const v = findVariant();
        if (v) {
            if (variantInput) variantInput.value = v.id;
            const p = parseFloat(v.price || 0);
            priceEl.textContent = Math.round(p > 0 ? p : basePrice).toLocaleString();
            const st = parseInt(v.stock, 10) || 0;
            qty.max = st || 1;
            if (parseInt(qty.value, 10) > st) qty.value = st > 0 ? st : 1;
            stockHint.textContent = st > 0 ? (st + ' in stock') : 'Out of stock for this option';
        } else if (!variants.length) {
            qty.max = baseStock;
            stockHint.textContent = '';
        } else {
            if (variantInput) variantInput.value = '';
            stockHint.textContent = 'This color / size combination is not available';
        }
        const sizeLabel = document.getElementById('selectedSizeLabel');
        if (sizeLabel && size) sizeLabel.textContent = size;
    }

    document.querySelectorAll('#colorFamily .pdp-opt').forEach(function (btn) {
        btn.addEventListener('click', function () {
            document.querySelectorAll('#colorFamily .pdp-opt').forEach(function (b) { b.classList.remove('is-active'); });
            btn.classList.add('is-active');
            color = btn.dataset.color;
            applyVariant();
        });
    });
    document.querySelectorAll('#sizeFamily .pdp-opt').forEach(function (btn) {
        btn.addEventListener('click', function () {
            document.querySelectorAll('#sizeFamily .pdp-opt').forEach(function (b) { b.classList.remove('is-active'); });
            btn.classList.add('is-active');
            size = btn.dataset.size;
            applyVariant();
        });
    });
    applyVariant();

    const main = document.getElementById('mainProductImg');
    const zoom = document.getElementById('pdpZoomImg');
    document.querySelectorAll('.pdp-thumb').forEach(function (btn) {
        btn.addEventListener('click', function () {
            document.querySelectorAll('.pdp-thumb').forEach(function (b) { b.classList.remove('is-active'); });
            btn.classList.add('is-active');
            main.src = btn.dataset.src;
            zoom.src = btn.dataset.src;
        });
    });

    function showTab(tabBtnSel) {
        const trigger = document.querySelector(tabBtnSel);
        if (trigger) bootstrap.Tab.getOrCreateInstance(trigger).show();
    }
    document.querySelectorAll('.js-pdp-tab').forEach(function (a) {
        a.addEventListener('click', function (e) {
            e.preventDefault();
            showTab(a.dataset.tab);
            document.getElementById('pdpTabsCard')?.scrollIntoView({ behavior: 'smooth' });
        });
    });
    if (location.hash === '#reviews-pane') showTab('#reviews-tab');
    if (location.hash === '#qa-pane') showTab('#qa-tab');
})();
</script>
<?= $this->endSection() ?>
