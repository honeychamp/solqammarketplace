<?= $this->extend('layouts/admin') ?>

<?= $this->section('content') ?>
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h4 class="fw-bold mb-1" style="color: #0F172A;">Edit Direct Product</h4>
        <p class="text-secondary small mb-0">Update details for <?= esc($product['name']) ?></p>
    </div>
    <a href="<?= site_url('admin/my-products') ?>" class="btn btn-light btn-sm rounded-pill border px-3">
        <i class="bi bi-arrow-left me-1"></i> Back to My Products
    </a>
</div>

<?php if (session()->getFlashdata('errors')): ?>
    <div class="alert alert-danger alert-dismissible fade show rounded-3" role="alert">
        <ul class="mb-0 small">
            <?php foreach (session()->getFlashdata('errors') as $err): ?>
                <li><?= esc($err) ?></li>
            <?php endforeach; ?>
        </ul>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>

<div class="card-custom p-4" style="max-width: 860px;">
    <form action="<?= site_url('admin/my-products/update/' . $product['id']) ?>" method="POST" enctype="multipart/form-data">
        <?= csrf_field() ?>
        <?= view('shared/product_form_fields', [
            'categories' => $categories,
            'product'    => $product,
            'variants'   => $variants ?? [],
            'images'     => $images ?? [],
            'showStatus' => true,
        ]) ?>
        <div class="pt-3 border-top text-end">
            <a href="<?= site_url('admin/my-products') ?>" class="btn btn-light rounded-pill px-4 me-2">Cancel</a>
            <button type="submit" class="btn btn-primary rounded-pill px-4 shadow-sm">
                <i class="bi bi-save me-1"></i> Save Changes
            </button>
        </div>
    </form>
</div>
<?= $this->endSection() ?>
