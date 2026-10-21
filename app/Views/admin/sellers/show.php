<?= $this->extend('layouts/admin') ?>

<?= $this->section('content') ?>
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h4 class="fw-bold mb-1">Seller Profile: <?= esc($seller['store_name']) ?></h4>
        <p class="text-secondary small mb-0">Registered on <?= date('d M Y, h:i A', strtotime($seller['registered_date'] ?? $seller['created_at'])) ?></p>
    </div>
    <a href="<?= site_url('admin/sellers') ?>" class="btn btn-light btn-sm rounded-pill border px-3">
        <i class="bi bi-arrow-left me-1"></i> Back to Sellers
    </a>
</div>

<div class="row g-4">
    <div class="col-lg-8">
        <!-- Store & Business Profile -->
        <div class="card-custom p-4 mb-4">
            <div class="d-flex justify-content-between align-items-start mb-3 border-bottom pb-2">
                <h5 class="fw-bold mb-0">Business &amp; Store Details</h5>
                <span class="badge fs-6 bg-<?= match($seller['approval_status']) { 'approved' => 'success', 'pending' => 'warning text-dark', 'suspended' => 'danger', default => 'danger' } ?>">
                    <?= ucfirst($seller['approval_status']) ?>
                </span>
            </div>

            <div class="row g-3 mb-3">
                <div class="col-md-6">
                    <div class="small text-secondary">Store Brand Name</div>
                    <div class="fw-bold text-dark fs-5"><?= esc($seller['store_name']) ?></div>
                </div>
                <div class="col-md-6">
                    <div class="small text-secondary">Registered Legal Entity / Business Name</div>
                    <div class="fw-bold text-dark fs-5"><?= esc($seller['business_name'] ?: 'Sole Proprietor') ?></div>
                </div>
            </div>

            <div class="row g-3 mb-3">
                <div class="col-md-6">
                    <div class="small text-secondary">National Tax No (NTN) or CNIC</div>
                    <div class="fw-semibold text-dark"><code><?= esc($seller['cnic_or_ntn'] ?: 'Not Provided') ?></code></div>
                </div>
                <div class="col-md-6">
                    <div class="small text-secondary">Operating City</div>
                    <div class="fw-semibold text-dark"><?= esc($seller['city'] ?: 'Pakistan') ?></div>
                </div>
            </div>

            <div class="mb-3">
                <div class="small text-secondary">Warehouse / Commercial Address</div>
                <div class="p-2 bg-light rounded text-dark"><?= esc($seller['business_address'] ?: 'No physical address stated.') ?></div>
            </div>

            <?php if (!empty($seller['rejection_reason'])): ?>
                <div class="alert alert-danger py-2 small mb-0">
                    <strong>Previous Rejection Note:</strong> <?= esc($seller['rejection_reason']) ?>
                </div>
            <?php endif; ?>
        </div>

        <?php $life = $life ?? []; $dueSummary = $dueSummary ?? []; $dueLines = $dueLines ?? []; ?>
        <div class="card-custom p-4 mb-4">
            <div class="d-flex justify-content-between align-items-center mb-3 border-bottom pb-2">
                <h5 class="fw-bold mb-0">Money — lifetime + COD dues</h5>
                <?php if ((float) ($dueSummary['unpaid'] ?? 0) > 0): ?>
                    <form method="POST" action="<?= site_url('admin/dues/seller/' . (int) $seller['user_id'] . '/offset') ?>">
                        <?= csrf_field() ?>
                        <button class="btn btn-sm btn-outline-primary rounded-pill">Cut from payout wallet</button>
                    </form>
                <?php endif; ?>
            </div>
            <div class="row g-3 mb-3">
                <div class="col-md-3"><div class="small text-muted">Orders</div><div class="fw-bold"><?= (int) ($life['order_count'] ?? 0) ?></div></div>
                <div class="col-md-3"><div class="small text-muted">Goods total</div><div class="fw-bold">Rs. <?= number_format((float) ($life['goods'] ?? 0), 0) ?></div></div>
                <div class="col-md-3"><div class="small text-muted">Commission</div><div class="fw-bold text-primary">Rs. <?= number_format((float) ($life['commission'] ?? 0), 0) ?></div></div>
                <div class="col-md-3"><div class="small text-muted">Cashback</div><div class="fw-bold text-success">Rs. <?= number_format((float) ($life['cashback'] ?? 0), 0) ?></div></div>
                <div class="col-md-3"><div class="small text-muted">Seller net (goods − comm − CB)</div><div class="fw-bold">Rs. <?= number_format((float) ($life['net'] ?? 0), 0) ?></div></div>
                <div class="col-md-3"><div class="small text-muted">COD unpaid to Solqam</div><div class="fw-bold text-danger">Rs. <?= number_format((float) ($dueSummary['unpaid'] ?? 0), 0) ?></div></div>
                <div class="col-md-3"><div class="small text-muted">COD dues paid</div><div class="fw-bold">Rs. <?= number_format((float) ($dueSummary['paid'] ?? 0), 0) ?></div></div>
                <div class="col-md-3"><div class="small text-muted">Cash seller collected (COD self-ship)</div><div class="fw-bold">Rs. <?= number_format((float) ($dueSummary['collected'] ?? 0), 0) ?></div></div>
            </div>
            <?php if ($dueLines === []): ?>
                <p class="small text-muted mb-0">No COD self-delivery dues yet.</p>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-sm align-middle mb-0">
                        <thead class="table-light small">
                            <tr>
                                <th>Order</th>
                                <th>Collected</th>
                                <th>Commission</th>
                                <th>Cashback</th>
                                <th>Due</th>
                                <th>Left</th>
                                <th>Paid?</th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($dueLines as $d): ?>
                                <tr>
                                    <td>
                                        <a href="<?= site_url('admin/orders/' . (int) $d['order_id']) ?>"><?= esc($d['order_number'] ?? ('#' . $d['order_id'])) ?></a>
                                        <div class="small text-muted">Order Rs. <?= number_format((float) ($d['total_amount'] ?? 0), 0) ?> · payable Rs. <?= number_format((float) ($d['final_payable'] ?? 0), 0) ?> · wallet Rs. <?= number_format((float) ($d['wallet_amount_used'] ?? 0), 0) ?></div>
                                    </td>
                                    <td>Rs. <?= number_format((float) $d['collected_cash'], 0) ?></td>
                                    <td>Rs. <?= number_format((float) $d['commission_amount'], 0) ?></td>
                                    <td>Rs. <?= number_format((float) $d['cashback_amount'], 0) ?></td>
                                    <?php $left = \App\Services\Commission\SellerDueService::remaining($d); ?>
                                    <td class="fw-bold">Rs. <?= number_format((float) $d['due_amount'], 0) ?></td>
                                    <td class="<?= $left > 0 ? 'text-danger fw-bold' : 'text-success' ?>">Rs. <?= number_format($left, 0) ?></td>
                                    <td>
                                        <?php if ($d['status'] === 'paid'): ?>
                                            <span class="badge bg-success">Paid <?= !empty($d['paid_at']) ? date('d M', strtotime($d['paid_at'])) : '' ?></span>
                                        <?php elseif ($d['status'] === 'waived'): ?>
                                            <span class="badge bg-secondary">Waived</span>
                                        <?php elseif ((float) ($d['paid_amount'] ?? 0) > 0): ?>
                                            <span class="badge bg-warning text-dark">Partial Rs. <?= number_format((float) $d['paid_amount'], 0) ?></span>
                                        <?php else: ?>
                                            <span class="badge bg-danger">Unpaid</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="text-end">
                                        <a class="btn btn-sm btn-outline-secondary rounded-pill" href="<?= site_url('admin/dues/' . (int) $d['id'] . '/receipt') ?>">Receipt</a>
                                        <?php if ($d['status'] === 'unpaid' && $left > 0): ?>
                                            <form method="POST" action="<?= site_url('admin/dues/' . (int) $d['id'] . '/paid') ?>" class="d-inline-flex gap-1 align-items-center mt-1">
                                                <?= csrf_field() ?>
                                                <input type="number" step="1" min="1" name="amount" class="form-control form-control-sm" style="width:90px" placeholder="<?= (int) $left ?>" value="<?= (int) $left ?>">
                                                <input type="hidden" name="paid_note" value="Received from seller">
                                                <button class="btn btn-sm btn-success rounded-pill" onclick="return confirm('Record this cash received?');">Receive</button>
                                            </form>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>

        <!-- Banking Settlement Info -->
        <div class="card-custom p-4">
            <h5 class="fw-bold mb-3 border-bottom pb-2">Settlement Bank Account Details</h5>
            <div class="row g-3">
                <div class="col-md-4">
                    <div class="small text-secondary">Bank Name</div>
                    <div class="fw-bold text-dark"><?= esc($seller['bank_name'] ?: 'Not Stated') ?></div>
                </div>
                <div class="col-md-4">
                    <div class="small text-secondary">Account Title</div>
                    <div class="fw-bold text-dark"><?= esc($seller['bank_account_title'] ?: 'Not Stated') ?></div>
                </div>
                <div class="col-md-4">
                    <div class="small text-secondary">IBAN / Account No</div>
                    <div class="fw-bold text-dark"><code><?= esc($seller['account_number_or_iban'] ?: 'Not Stated') ?></code></div>
                </div>
            </div>
        </div>
    </div>

    <!-- Owner Contact & Actions -->
    <div class="col-lg-4">
        <div class="card-custom p-4 mb-4">
            <h5 class="fw-bold mb-3 border-bottom pb-2">Owner Contact</h5>
            <div class="small">
                <div class="mb-2"><strong>Name:</strong> <?= esc($seller['owner_name']) ?></div>
                <div class="mb-2"><strong>Email:</strong> <?= esc($seller['owner_email']) ?></div>
                <div class="mb-3"><strong>Phone:</strong> <?= esc($seller['owner_phone']) ?></div>
                <div><strong>User Status:</strong> <span class="badge bg-<?= ($seller['user_status'] === 'active') ? 'success' : 'secondary' ?>"><?= ucfirst($seller['user_status']) ?></span></div>
                <div class="mt-3 p-3 rounded-3 bg-light">
                    <div class="small text-secondary">Unique customers</div>
                    <div class="fw-bold fs-3 mb-0"><?= count($buyers ?? []) ?></div>
                    <div class="small text-muted">Buyers who ordered from this store</div>
                </div>
            </div>
        </div>

        <!-- Account Actions -->
        <div class="card-custom p-4">
            <h5 class="fw-bold mb-3 border-bottom pb-2">Account Management</h5>

            <?php if ($seller['user_status'] !== 'active' || $seller['approval_status'] !== 'approved'): ?>
                <form action="<?= site_url('admin/sellers/' . $seller['id'] . '/approve') ?>" method="POST" class="mb-2">
                    <?= csrf_field() ?>
                    <button type="submit" class="btn btn-success w-100 py-2 rounded-pill fw-bold" onclick="return confirm('Approve this seller? They will be able to list products and manage orders.');">
                        <i class="bi bi-check-circle me-1"></i> Approve seller
                    </button>
                </form>
            <?php endif; ?>

            <?php if ($seller['user_status'] === 'active'): ?>
                <button type="button" class="btn btn-outline-danger w-100 py-2 rounded-pill fw-bold" data-bs-toggle="modal" data-bs-target="#rejectModal">
                    <i class="bi bi-slash-circle me-1"></i> Suspend Seller
                </button>

                <!-- Suspend Modal -->
                <div class="modal fade" id="rejectModal" tabindex="-1">
                    <div class="modal-dialog">
                        <div class="modal-content rounded-4 border-0">
                            <div class="modal-header">
                                <h5 class="modal-title fw-bold text-danger">Suspend Seller Account</h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                            </div>
                            <form action="<?= site_url('admin/sellers/' . $seller['id'] . '/reject') ?>" method="POST">
                                <?= csrf_field() ?>
                                <div class="modal-body">
                                    <label class="form-label small fw-bold">Reason for Suspension</label>
                                    <textarea name="rejection_reason" class="form-control" rows="3" placeholder="Provide reason for suspension..." required></textarea>
                                </div>
                                <div class="modal-footer">
                                    <button type="button" class="btn btn-light rounded-pill" data-bs-dismiss="modal">Cancel</button>
                                    <button type="submit" class="btn btn-danger rounded-pill px-4">Confirm Suspension</button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php $buyers = $buyers ?? []; ?>
<div class="card-custom p-4 mt-4" id="seller-buyers">
    <div class="d-flex justify-content-between align-items-center mb-3 border-bottom pb-2">
        <h5 class="fw-bold mb-0">Store customers (<?= count($buyers) ?>)</h5>
        <span class="small text-muted">People who placed at least one order with this seller</span>
    </div>
    <?php if ($buyers === []): ?>
        <p class="text-secondary text-center py-4 mb-0">No customers yet for this store.</p>
    <?php else: ?>
        <div class="table-responsive">
            <table class="table align-middle mb-0">
                <thead class="table-light small">
                    <tr>
                        <th>Customer</th>
                        <th>Phone</th>
                        <th>Email</th>
                        <th>Orders</th>
                        <th>Goods</th>
                        <th>Status</th>
                        <th class="text-end">Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($buyers as $b): ?>
                        <tr>
                            <td>
                                <div class="fw-bold text-dark"><?= esc($b['name']) ?></div>
                                <small class="text-muted">ID: #<?= (int) $b['id'] ?></small>
                            </td>
                            <td><code><?= esc($b['phone']) ?></code></td>
                            <td><?= esc($b['email']) ?></td>
                            <td><?= (int) $b['orders'] ?></td>
                            <td>Rs. <?= number_format((float) $b['goods'], 0) ?></td>
                            <td>
                                <span class="badge bg-<?= (($b['status'] ?? '') === 'active') ? 'success' : 'secondary' ?>">
                                    <?= esc(ucfirst((string) ($b['status'] ?: '—'))) ?>
                                </span>
                            </td>
                            <td class="text-end">
                                <a href="<?= site_url('admin/customers/' . $b['id']) ?>" class="btn btn-sm btn-outline-primary rounded-pill px-3">360</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>
<?= $this->endSection() ?>
