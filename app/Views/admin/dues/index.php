<?= $this->extend('layouts/admin') ?>
<?= $this->section('content') ?>
<div class="d-flex justify-content-between align-items-start flex-wrap gap-2 mb-4">
    <div>
        <h4 class="fw-bold mb-1">COD dues (seller self-delivery)</h4>
        <p class="text-secondary small mb-0">Seller collected cash. They still owe Solqam <strong>commission + buyer cashback</strong>. Online payout wallet auto-cuts this. Partial cash is allowed. Unpaid next Delivered is blocked. At Rs. <?= number_format((float) ($limit ?? 1000), 0) ?> unpaid the store auto-suspends.</p>
    </div>
</div>
<?php if (empty($sellers)): ?>
    <div class="card-custom p-5 text-center text-secondary">No unpaid COD dues right now.</div>
<?php else: ?>
    <div class="card-custom p-4">
        <div class="table-responsive">
            <table class="table align-middle">
                <thead class="table-light small">
                    <tr>
                        <th>Seller</th>
                        <th>Unpaid to Solqam</th>
                        <th>Commission</th>
                        <th>Cashback</th>
                        <th>Cash they collected</th>
                        <th>Status</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($sellers as $s): ?>
                        <tr>
                            <td>
                                <div class="fw-bold"><?= esc($s['store_name']) ?></div>
                                <div class="small text-muted"><?= esc($s['seller_name']) ?> · <?= esc($s['phone']) ?></div>
                            </td>
                            <td class="fw-bold text-danger">Rs. <?= number_format((float) $s['unpaid'], 0) ?></td>
                            <td>Rs. <?= number_format((float) $s['commission'], 0) ?></td>
                            <td>Rs. <?= number_format((float) $s['cashback'], 0) ?></td>
                            <td>Rs. <?= number_format((float) $s['collected'], 0) ?></td>
                            <td>
                                <?php if (($s['approval'] ?? '') === 'suspended'): ?>
                                    <span class="badge bg-danger">Suspended</span>
                                <?php else: ?>
                                    <span class="badge bg-warning text-dark">Hold on next Delivered</span>
                                <?php endif; ?>
                            </td>
                            <td class="text-end">
                                <?php if (! empty($s['profile_id'])): ?>
                                    <a class="btn btn-sm btn-primary rounded-pill" href="<?= site_url('admin/sellers/' . (int) $s['profile_id']) ?>">Full hisaab</a>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
<?php endif; ?>
<?= $this->endSection() ?>
