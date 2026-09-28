<?= $this->extend('layouts/main') ?>
<?= $this->section('content') ?>
<div class="container py-4">
    <div class="card border-0 shadow-sm rounded-4 p-4 mb-4">
        <div class="d-flex justify-content-between align-items-center flex-wrap gap-3">
            <div>
                <h3 class="fw-bold mb-1"><?= esc($profile['store_name']) ?></h3>
                <p class="text-muted mb-0"><?= esc($profile['city'] ?? '') ?> · <?= (int) $followerCount ?> followers</p>
            </div>
            <form action="<?= site_url('store/follow') ?>" method="POST">
                <?= csrf_field() ?>
                <input type="hidden" name="seller_id" value="<?= (int) $sellerId ?>">
                <button class="btn <?= $isFollowing ? 'btn-solqam' : 'btn-solqam-outline' ?> rounded-pill px-4">
                    <?= $isFollowing ? 'Following' : 'Follow Store' ?>
                </button>
            </form>
        </div>
    </div>
    <div class="row g-3">
        <?php foreach ($products as $product): ?>
            <div class="col-6 col-md-3">
                <a href="<?= site_url('product/' . $product['id']) ?>" class="text-decoration-none">
                    <div class="solqam-product-card h-100">
                        <div class="card-media">
                            <img src="<?= esc($product['primary_image'] ?: base_url('assets/images/product-placeholder.svg')) ?>" alt="<?= esc($product['name']) ?>">
                            <?= cashback_chip($product) ?>
                        </div>
                        <div class="card-body">
                            <div class="product-title"><?= esc($product['name']) ?></div>
                            <div class="price-current">Rs. <?= number_format((float) $product['price'], 0) ?></div>
                            <div class="small text-success fw-semibold mt-1"><?= esc(cashback_percent_label($product)) ?> wallet cashback</div>
                        </div>
                    </div>
                </a>
            </div>
        <?php endforeach; ?>
        <?php if (empty($products)): ?>
            <p class="text-muted">This store has no live products yet.</p>
        <?php endif; ?>
    </div>
</div>
<?= $this->endSection() ?>
