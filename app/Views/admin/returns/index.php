<?= $this->extend('layouts/admin') ?>

<?= $this->section('content') ?>
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h4 class="fw-bold mb-1">Returns &amp; Refunds Management</h4>
        <p class="text-secondary small mb-0">Approve to credit the buyer and cut the same goods from seller or Solqam Mall wallets.</p>
    </div>
</div>

<div class="card-custom p-4">
    <?php if (empty($returns)): ?>
        <p class="text-secondary text-center py-5 mb-0">No return requests found.</p>
    <?php else: ?>
        <div class="table-responsive">
            <table class="table align-middle">
                <thead class="table-light small">
                    <tr>
                        <th>Request #</th>
                        <th>Order #</th>
                        <th>Customer</th>
                        <th>Reason</th>
                        <th>Claim Amount</th>
                        <th>Status</th>
                        <th class="text-end">Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($returns as $r): ?>
                        <tr>
                            <td>#<?= $r['id'] ?></td>
                            <td><strong><?= esc($r['order_number']) ?></strong></td>
                            <td>
                                <div><?= esc($r['customer_name']) ?></div>
                                <small class="text-muted"><i class="bi bi-telephone me-1"></i> <?= esc($r['customer_phone']) ?></small>
                            </td>
                            <td>
                                <div class="fw-semibold text-dark"><?= esc($r['reason']) ?></div>
                                <small class="text-secondary"><?= esc($r['customer_note']) ?></small>
                            </td>
                            <td class="fw-bold text-success fs-6">Rs. <?= number_format($r['refund_amount'], 2) ?></td>
                            <td>
                                <span class="badge bg-<?= match($r['status']) { 'refunded' => 'success', 'requested' => 'warning text-dark', default => 'danger' } ?>">
                                    <?= ucfirst($r['status']) ?>
                                </span>
                            </td>
                            <td class="text-end">
                                <?php if ($r['status'] === 'requested'): ?>
                                    <form action="<?= site_url('admin/returns/' . $r['id'] . '/approve') ?>" method="POST" class="d-inline">
                                        <?= csrf_field() ?>
                                        <button type="submit" class="btn btn-sm btn-success rounded-pill px-3 me-1" onclick="return confirm('Approve? Buyer wallet +Rs. <?= number_format($r['refund_amount'], 2) ?>. Seller/admin wallets will be cut for the goods.');">
                                            <i class="bi bi-check-lg"></i> Approve &amp; Refund
                                        </button>
                                    </form>
                                    <button type="button" class="btn btn-sm btn-outline-danger rounded-pill px-3" data-bs-toggle="modal" data-bs-target="#rejectReturnModal_<?= $r['id'] ?>">
                                        <i class="bi bi-x-lg"></i> Reject
                                    </button>

                                    <!-- Reject Return Modal -->
                                    <div class="modal fade" id="rejectReturnModal_<?= $r['id'] ?>" tabindex="-1">
                                        <div class="modal-dialog">
                                            <div class="modal-content rounded-4 border-0">
                                                <div class="modal-header">
                                                    <h5 class="modal-title fw-bold">Reject Return Request #<?= $r['id'] ?></h5>
                                                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                                </div>
                                                <form action="<?= site_url('admin/returns/' . $r['id'] . '/reject') ?>" method="POST">
                                                    <?= csrf_field() ?>
                                                    <div class="modal-body">
                                                        <label class="form-label small fw-bold">Rejection Note</label>
                                                        <textarea name="admin_note" class="form-control" rows="3" placeholder="Provide justification..." required></textarea>
                                                    </div>
                                                    <div class="modal-footer">
                                                        <button type="button" class="btn btn-light rounded-pill" data-bs-dismiss="modal">Cancel</button>
                                                        <button type="submit" class="btn btn-danger rounded-pill px-4">Confirm Rejection</button>
                                                    </div>
                                                </form>
                                            </div>
                                        </div>
                                    </div>
                                <?php else: ?>
                                    <small class="text-muted">Processed on <?= date('d M Y', strtotime($r['processed_at'] ?? $r['updated_at'])) ?></small>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>
<?= $this->endSection() ?>
