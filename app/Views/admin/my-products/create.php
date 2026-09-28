<?= $this->extend('layouts/admin') ?>

<?= $this->section('content') ?>
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h4 class="fw-bold mb-1" style="color: #0F172A;">Add Direct Product</h4>
        <p class="text-secondary small mb-0">Same catalog fields as sellers — only filled details appear on the product page</p>
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
    <form action="<?= site_url('admin/my-products/store') ?>" method="POST" enctype="multipart/form-data">
        <?= csrf_field() ?>
        <?= view('shared/product_form_fields', ['categories' => $categories, 'product' => [], 'variants' => [], 'images' => []]) ?>
        <div class="p-3 bg-light rounded-3 border mb-4">
            <div class="d-flex align-items-center gap-2 mb-2 text-primary fw-bold small">
                <i class="bi bi-shield-check"></i> Admin First-Party Guarantee
            </div>
            <p class="small text-muted mb-0">
                This product is sold directly by Solqam Admin. You manage its orders. There is no platform commission on admin-store items. Set the cashback % on this form — that is what the buyer receives when paid.
            </p>
        </div>
        <div class="pt-3 border-top text-end">
            <a href="<?= site_url('admin/my-products') ?>" class="btn btn-light rounded-pill px-4 me-2">Cancel</a>
            <button type="submit" class="btn btn-primary rounded-pill px-4 shadow-sm">
                <i class="bi bi-cloud-arrow-up me-1"></i> Publish Direct Product
            </button>
        </div>
    </form>
</div>
<?= $this->endSection() ?>
