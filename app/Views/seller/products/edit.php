<?= $this->extend('layouts/seller') ?>

<?= $this->section('content') ?>
<div class="card border-0 shadow-sm rounded-4 p-4 bg-white" style="max-width: 800px;">
    <div class="d-flex justify-content-between align-items-center mb-4 border-bottom pb-3">
        <h4 class="fw-bold mb-0">Edit Product</h4>
        <a href="<?= site_url('seller/products') ?>" class="btn btn-light btn-sm rounded-pill border px-3">
            <i class="bi bi-arrow-left me-1"></i> Back
        </a>
    </div>

    <?php if (session()->getFlashdata('errors')): ?>
        <div class="alert alert-danger small">
            <ul class="mb-0">
                <?php foreach (session()->getFlashdata('errors') as $err): ?>
                    <li><?= esc($err) ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>

    <form action="<?= site_url('seller/products/update/' . $product['id']) ?>" method="POST" enctype="multipart/form-data">
        <?= csrf_field() ?>
        <?= view('shared/product_form_fields', [
            'categories' => $categories,
            'product'    => $product,
            'variants'   => $variants ?? [],
            'images'     => $images ?? [],
            'showStatus' => true,
        ]) ?>
        <div class="d-flex justify-content-end gap-2">
            <a href="<?= site_url('seller/products') ?>" class="btn btn-light rounded-pill px-4">Cancel</a>
            <button type="submit" class="btn btn-primary rounded-pill px-5 fw-bold">Update Changes</button>
        </div>
    </form>
</div>
<?= $this->endSection() ?>
