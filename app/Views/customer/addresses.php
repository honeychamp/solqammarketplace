<?= $this->extend('layouts/main') ?>

<?= $this->section('content') ?>
<div class="container py-4">
    <div class="d-flex flex-wrap justify-content-between align-items-center mb-4 gap-2">
        <div>
            <h3 class="fw-bold mb-1" style="color: #0F172A;"><i class="bi bi-geo-alt-fill text-primary me-2"></i> Saved Addresses</h3>
            <p class="text-secondary small mb-0">Manage your shipping and delivery destinations across Pakistan.</p>
        </div>
        <button type="button" class="btn btn-sol-primary btn-sm rounded-pill px-3 shadow-sm" data-bs-toggle="modal" data-bs-target="#newAddrModal">
            <i class="bi bi-plus-lg me-1"></i> Add New Address
        </button>
    </div>

    <div class="row g-3">
        <?php if (empty($addresses)): ?>
            <div class="col-12">
                <div class="card border-0 shadow-sm rounded-4 p-5 text-center bg-white">
                    <div class="d-inline-flex p-4 rounded-circle mx-auto mb-3" style="background: rgba(11, 48, 230, 0.08); color: var(--sol-primary);">
                        <i class="bi bi-geo-alt fs-1"></i>
                    </div>
                    <h5 class="fw-bold text-dark mb-1">No saved addresses yet</h5>
                    <p class="text-secondary small mb-3">Add a delivery address to speed up your checkout on Solqam.</p>
                    <div>
                        <button type="button" class="btn btn-sol-primary btn-sm rounded-pill px-4" data-bs-toggle="modal" data-bs-target="#newAddrModal">
                            Add First Address
                        </button>
                    </div>
                </div>
            </div>
        <?php else: ?>
            <?php foreach ($addresses as $addr): ?>
                <div class="col-md-6">
                    <div class="card border-0 shadow-sm rounded-4 p-4 bg-white h-100 position-relative" style="<?= $addr['is_default'] ? 'border-left: 4px solid var(--sol-primary) !important;' : '' ?>">
                        <div class="d-flex justify-content-between align-items-start mb-2">
                            <h5 class="fw-bold mb-0 text-dark"><?= esc($addr['recipient_name']) ?></h5>
                            <?php if ($addr['is_default']): ?>
                                <span class="badge rounded-pill bg-primary px-2.5 py-1 text-uppercase" style="font-size: 0.7rem; letter-spacing: 0.5px;">Default</span>
                            <?php endif; ?>
                        </div>
                        <div class="text-secondary mb-1"><?= esc($addr['street_address']) ?></div>
                        <div class="text-secondary mb-2"><?= esc($addr['city']) ?>, <?= esc($addr['province']) ?> <?= esc($addr['postal_code']) ?></div>
                        <div class="small text-muted"><i class="bi bi-telephone me-1 text-primary"></i> <?= esc($addr['phone']) ?></div>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</div>

<!-- Modal for New Address -->
<div class="modal fade" id="newAddrModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content rounded-4 border-0">
            <div class="modal-header">
                <h5 class="modal-title fw-bold" style="color: #0F172A;">Add New Delivery Address</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form action="<?= site_url('account/addresses') ?>" method="POST">
                <?= csrf_field() ?>
                <div class="modal-body">
                    <div class="row g-2 mb-2">
                        <div class="col-6">
                            <label class="form-label small fw-semibold text-muted">Recipient Full Name</label>
                            <input type="text" name="recipient_name" class="form-control form-control-sm" required>
                        </div>
                        <div class="col-6">
                            <label class="form-label small fw-semibold text-muted">Mobile Number</label>
                            <input type="text" name="phone" class="form-control form-control-sm" placeholder="03XXXXXXXXX" required>
                        </div>
                    </div>
                    <div class="mb-2">
                        <label class="form-label small fw-semibold text-muted">Street / House / Sector</label>
                        <textarea name="street_address" class="form-control form-control-sm" rows="2" required></textarea>
                    </div>
                    <div class="row g-2 mb-3">
                        <div class="col-4">
                            <label class="form-label small fw-semibold text-muted">City</label>
                            <input type="text" name="city" class="form-control form-control-sm" required>
                        </div>
                        <div class="col-4">
                            <label class="form-label small fw-semibold text-muted">Province</label>
                            <select name="province" class="form-select form-select-sm">
                                <option value="Punjab">Punjab</option>
                                <option value="Sindh">Sindh</option>
                                <option value="Khyber Pakhtunkhwa">Khyber Pakhtunkhwa</option>
                                <option value="Balochistan">Balochistan</option>
                                <option value="Islamabad Capital Territory">Islamabad</option>
                            </select>
                        </div>
                        <div class="col-4">
                            <label class="form-label small fw-semibold text-muted">Postal Code</label>
                            <input type="text" name="postal_code" class="form-control form-control-sm">
                        </div>
                    </div>
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" name="is_default" value="1" id="defCheck">
                        <label class="form-check-label small" for="defCheck">Set as primary default delivery destination</label>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light rounded-pill" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-sol-primary rounded-pill px-4 fw-bold">Save Destination</button>
                </div>
            </form>
        </div>
    </div>
</div>
<?= $this->endSection() ?>
