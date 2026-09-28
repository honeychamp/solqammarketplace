<?= $this->extend('layouts/main') ?>
<?= $this->section('content') ?>
<div class="container py-4">
    <h3 class="fw-bold mb-4"><i class="bi bi-heart text-danger me-2"></i> My Wishlist</h3>
    <?php if (empty($items)): ?>
        <div class="card border-0 shadow-sm rounded-4 p-5 text-center">
            <p class="text-muted mb-3">No saved items yet.</p>
            <a href="<?= site_url('shop') ?>" class="btn btn-solqam rounded-pill">Browse Shop</a>
        </div>
    <?php else: ?>
        <div class="row g-3">
            <?php foreach ($items as $item): ?>
                <div class="col-6 col-md-3">
                    <div class="solqam-product-card h-100">
                        <div class="card-media">
                            <img src="<?= esc($item['primary_image'] ?: base_url('assets/images/product-placeholder.svg')) ?>" alt="<?= esc($item['name']) ?>">
                            <?= cashback_chip($item) ?>
                        </div>
                        <div class="card-body">
                            <a href="<?= site_url('product/' . $item['product_id']) ?>" class="product-title"><?= esc($item['name']) ?></a>
                            <div class="price-current mt-1">Rs. <?= number_format((float) $item['price'], 0) ?></div>
                            <div class="small text-success fw-semibold mt-1"><?= esc(cashback_percent_label($item)) ?> wallet cashback</div>
                            <form action="<?= site_url('wishlist/toggle') ?>" method="POST" class="mt-2">
                                <?= csrf_field() ?>
                                <input type="hidden" name="product_id" value="<?= $item['product_id'] ?>">
                                <button class="btn btn-sm btn-outline-danger rounded-pill">Remove</button>
                            </form>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>
<?= $this->endSection() ?>
