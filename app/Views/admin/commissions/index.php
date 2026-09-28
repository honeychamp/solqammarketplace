<?= $this->extend('layouts/admin') ?>

<?= $this->section('content') ?>
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h4 class="fw-bold mb-1">Global Marketplace Commission Rule</h4>
        <p class="text-secondary small mb-0">Fee on seller listings only. Admin-store products have 0% commission.</p>
    </div>
</div>

<div class="row g-4">
    <div class="col-md-6">
        <div class="card-custom p-4">
            <h5 class="fw-bold mb-3 border-bottom pb-2">Active Commission Rule</h5>

            <form action="<?= site_url('admin/commissions') ?>" method="POST">
                <?= csrf_field() ?>

                <div class="mb-3">
                    <label class="form-label small fw-bold">Rule Name</label>
                    <input type="text" class="form-control" value="<?= esc($rule['name']) ?>" disabled>
                </div>

                <div class="mb-4">
                    <label class="form-label small fw-bold">Commission Rate (%)</label>
                    <div class="input-group">
                        <input type="number" step="0.1" name="percentage" class="form-control form-control-lg fw-bold" value="<?= esc($rule['percentage']) ?>" min="0" max="50" required>
                        <span class="input-group-text fs-5 fw-bold">%</span>
                    </div>
                    <small class="text-secondary mt-1 d-block">
                        Applied to seller products only. Your own catalog items never take commission.
                    </small>
                </div>

                <button type="submit" class="btn btn-success rounded-pill px-4 fw-bold">
                    <i class="bi bi-check-circle me-1"></i> Update Commission Rate
                </button>
            </form>
        </div>
    </div>

    <div class="col-md-6">
        <div class="card-custom p-4 bg-light">
            <h5 class="fw-bold mb-3"><i class="bi bi-calculator text-primary me-2"></i> How Commission is Deducted</h5>
            <p class="small text-secondary">
                When a customer completes checkout, the platform commission is calculated as:
            </p>
            <div class="p-3 bg-white rounded-3 border mb-3">
                <code>Platform Commission = (Item Subtotal &times; <?= esc($rule['percentage']) ?>) / 100</code>
            </div>
            <p class="small text-secondary mb-0">
                Seller items: Platform Commission = (Item Subtotal × rate) / 100. Admin first-party items: Rs. 0. Buyer cashback is the % the seller/admin set on that product. Prepaid: instant. COD: after delivery.
            </p>
        </div>
    </div>
</div>
<?= $this->endSection() ?>
