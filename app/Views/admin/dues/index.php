<?= $this->extend('layouts/admin') ?>
<?= $this->section('content') ?>
<div class="d-flex justify-content-between align-items-start flex-wrap gap-2 mb-4">
    <div>
        <h4 class="fw-bold mb-1">COD dues (seller self-delivery)</h4>
        <p class="text-secondary small mb-0">Seller collected cash. They still owe Solqam <strong>commission + buyer cashback</strong>. They send JazzCash/bank (SLA &amp; limits) then upload a screenshot → Processing. You mark Paid when money arrives. Online payout wallet auto-cuts this. Next Delivered is blocked until paid. At Rs. <?= number_format((float) ($limit ?? 5000), 0) ?> unpaid the store auto-suspends.</p>
    </div>
</div>
<?php
$processing = $processing ?? [];
$procBySeller = [];
foreach ($processing as $d) {
    $sid = (int) ($d['seller_id'] ?? 0);
    if (! isset($procBySeller[$sid])) {
        $procBySeller[$sid] = ['seller_id' => $sid, 'store' => $d['store_name'] ?? 'Seller', 'name' => $d['seller_name'] ?? '', 'total' => 0.0, 'n' => 0];
    }
    $procBySeller[$sid]['total'] += \App\Services\Commission\SellerDueService::remaining($d);
    $procBySeller[$sid]['n']++;
}
?>
<?php if ($processing !== []): ?>
    <div class="card-custom p-4 mb-4">
        <h5 class="fw-bold mb-3">Processing — screenshot received</h5>
        <?php if (count($procBySeller) > 0): ?>
            <div class="d-flex flex-wrap gap-2 mb-3">
                <?php foreach ($procBySeller as $g): ?>
                    <form method="POST" action="<?= site_url('admin/dues/seller/' . (int) $g['seller_id'] . '/paid-all') ?>" class="d-inline">
                        <?= csrf_field() ?>
                        <input type="hidden" name="paid_note" value="Confirmed total screenshot transfer">
                        <button class="btn btn-sm btn-success rounded-pill" onclick="return confirm('<?= esc($g['store'], 'js') ?>: Rs. <?= number_format($g['total'], 0) ?> (<?= (int) $g['n'] ?> dues) received? Mark ALL paid.');">
                            Mark all paid — <?= esc($g['store']) ?> · Rs. <?= number_format($g['total'], 0) ?>
                        </button>
                    </form>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
        <div class="table-responsive">
            <table class="table align-middle">
                <thead class="table-light small">
                    <tr>
                        <th>Seller</th>
                        <th>Order</th>
                        <th>Due</th>
                        <th>Screenshot</th>
                        <th>Note</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($processing as $d): ?>
                        <?php $left = \App\Services\Commission\SellerDueService::remaining($d); ?>
                        <tr>
                            <td>
                                <div class="fw-bold"><?= esc($d['store_name'] ?? 'Seller') ?></div>
                                <div class="small text-muted"><?= esc($d['seller_name'] ?? '') ?></div>
                            </td>
                            <td>
                                <a href="<?= site_url('admin/orders/' . (int) $d['order_id']) ?>"><?= esc($d['order_number'] ?? ('#' . $d['order_id'])) ?></a>
                                <div class="small text-muted"><?= ! empty($d['submitted_at']) ? date('d M H:i', strtotime($d['submitted_at'])) : '' ?></div>
                            </td>
                            <td class="fw-bold">Rs. <?= number_format($left, 0) ?></td>
                            <td>
                                <?php if (! empty($d['proof_path'])): ?>
                                    <a href="<?= esc(media_url($d['proof_path'])) ?>" target="_blank">
                                        <img src="<?= esc(media_url($d['proof_path'])) ?>" alt="Proof" style="height:56px;width:56px;object-fit:cover;border-radius:8px;">
                                    </a>
                                <?php else: ?>
                                    —
                                <?php endif; ?>
                            </td>
                            <td class="small"><?= esc($d['proof_note'] ?? '') ?></td>
                            <td class="text-end">
                                <form method="POST" action="<?= site_url('admin/dues/' . (int) $d['id'] . '/paid') ?>" class="d-inline">
                                    <?= csrf_field() ?>
                                    <input type="hidden" name="amount" value="<?= (int) $left ?>">
                                    <input type="hidden" name="paid_note" value="Confirmed screenshot transfer">
                                    <button class="btn btn-sm btn-outline-success rounded-pill" onclick="return confirm('Money received? Mark this due paid.');">Mark paid</button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
<?php endif; ?>
<?php if (empty($sellers)): ?>
    <?php if ($processing === []): ?>
        <div class="card-custom p-5 text-center text-secondary">No unpaid COD dues right now.</div>
    <?php endif; ?>
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
                                    <a class="btn btn-sm btn-primary rounded-pill" href="<?= site_url('admin/sellers/' . (int) $s['profile_id']) ?>">Full account</a>
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
