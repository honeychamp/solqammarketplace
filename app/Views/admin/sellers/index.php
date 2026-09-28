<?= $this->extend('layouts/admin') ?>

<?= $this->section('content') ?>
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h4 class="fw-bold mb-1">Marketplace Sellers</h4>
        <p class="text-secondary small mb-0">View all registered marketplace sellers, store details, and vendor profiles</p>
    </div>
</div>

<div class="card-custom p-4">
    <?php if (empty($sellers)): ?>
        <p class="text-secondary text-center py-5 mb-0">No sellers found under this filter.</p>
    <?php else: ?>
        <div class="table-responsive">
            <table class="table align-middle">
                <thead class="table-light small">
                    <tr>
                        <th>Store / Business</th>
                        <th>Owner</th>
                        <th>City</th>
                        <th>CNIC / NTN</th>
                        <th>Status</th>
                        <th class="text-end">Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($sellers as $s): ?>
                        <tr>
                            <td>
                                <div class="fw-bold text-dark"><?= esc($s['store_name']) ?></div>
                                <small class="text-muted"><?= esc($s['business_name'] ?: 'Proprietorship') ?></small>
                            </td>
                            <td>
                                <div><?= esc($s['owner_name']) ?></div>
                                <small class="text-muted"><i class="bi bi-telephone me-1"></i> <?= esc($s['owner_phone']) ?></small>
                            </td>
                            <td><?= esc($s['city'] ?: 'Pakistan') ?></td>
                            <td><code><?= esc($s['cnic_or_ntn'] ?: 'Not Provided') ?></code></td>
                            <td>
                                <span class="badge bg-<?= match($s['approval_status']) { 'approved' => 'success', 'pending' => 'warning text-dark', default => 'danger' } ?>">
                                    <?= ucfirst($s['approval_status']) ?>
                                </span>
                            </td>
                            <td class="text-end">
                                <a href="<?= site_url('admin/sellers/' . $s['id']) ?>" class="btn btn-sm btn-outline-primary rounded-pill px-3">
                                    View Details
                                </a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>
<?= $this->endSection() ?>
