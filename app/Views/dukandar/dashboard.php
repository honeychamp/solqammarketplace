<?= $this->extend('layouts/dukandar') ?>
<?= $this->section('content') ?>
<?php
$s = $summary ?? [];
$t = $today ?? [];
$shop = $shop ?? [];
$approved = ($shop['approval_status'] ?? '') === 'approved';
$fifo = $fifo ?? [];
$hour = (int) date('G');
$greet = $hour < 12 ? 'Good morning' : ($hour < 17 ? 'Good afternoon' : 'Good evening');
$visits = (int) ($s['visits'] ?? 0);
$avgBill = $visits > 0 ? ((float) ($s['sales'] ?? 0) / $visits) : 0;
$pending = (float) ($s['pending'] ?? 0);
$processing = (float) ($s['processing'] ?? 0);
$released = (float) ($s['released'] ?? 0);
$credits = (float) ($s['credits'] ?? 0);
?>
<div class="ops-hero merchant mb-4">
    <div class="d-flex flex-wrap justify-content-between align-items-start gap-3">
        <div class="d-flex gap-3 align-items-start min-w-0">
            <?php if (! empty($shop['shop_image']) || ! empty($shop['shop_image_in'])): ?>
                <div class="d-none d-sm-flex gap-2">
                    <?php if (! empty($shop['shop_image'])): ?>
                        <img src="<?= base_url($shop['shop_image']) ?>" alt="Outside" class="mhub-photo">
                    <?php endif; ?>
                    <?php if (! empty($shop['shop_image_in'])): ?>
                        <img src="<?= base_url($shop['shop_image_in']) ?>" alt="Inside" class="mhub-photo">
                    <?php endif; ?>
                </div>
            <?php endif; ?>
            <div class="min-w-0">
                <div class="ops-kicker">Solqam Card POS · <?= esc($shop['category'] ?? 'Shop') ?></div>
                <h2 class="fw-black mb-1 text-white text-truncate"><?= $greet ?></h2>
                <h4 class="text-white fw-bold mb-1 text-truncate"><?= esc($shop['shop_name'] ?? 'Merchant Hub') ?></h4>
                <p class="mb-0 text-white-50 small">
                    <?= esc($shop['city'] ?? '') ?><?= ! empty($shop['area']) ? ' · ' . esc($shop['area']) : '' ?>
                    <?php if (! empty($shop['merchant_code'])): ?> · <span class="font-monospace"><?= esc($shop['merchant_code']) ?></span><?php endif; ?>
                    <?php if (! empty($shop['opening_hours'])): ?> · <?= esc($shop['opening_hours']) ?><?php endif; ?>
                </p>
            </div>
        </div>
        <div class="d-flex flex-wrap gap-2 align-items-center">
            <?= merchant_status_badge((string) ($shop['approval_status'] ?? 'pending')) ?>
            <?php if ($approved): ?>
                <div class="ops-live-chip"><span class="ops-dot"></span> Accepting cards</div>
                <a href="<?= merchant_url('scan') ?>" class="btn rounded-pill px-3 text-white" style="background:linear-gradient(135deg,#F0142F,#cc0e24);"><i class="bi bi-qr-code-scan me-1"></i> Scan card</a>
                <a href="<?= merchant_url('credits') ?>" class="btn rounded-pill px-3 fw-bold" style="background:#F59E0B;color:#0F172A;">Pay Solqam<?= $pending > 0 ? ' · Rs. ' . number_format($pending, 0) : '' ?></a>
            <?php endif; ?>
        </div>
    </div>
    <div class="row g-3 mt-4">
        <div class="col-6 col-lg-3">
            <div class="ops-stat"><span>Today sales</span><strong>Rs. <?= number_format((float) ($t['sales'] ?? 0), 0) ?></strong><em><?= (int) ($t['scans'] ?? 0) ?> <?= (int) ($t['scans'] ?? 0) === 1 ? 'scan' : 'scans' ?> today</em></div>
        </div>
        <div class="col-6 col-lg-3">
            <div class="ops-stat"><span>Unpaid 5%</span><strong>Rs. <?= number_format($pending, 0) ?></strong><em>Pay Solqam · oldest first</em></div>
        </div>
        <div class="col-6 col-lg-3">
            <div class="ops-stat"><span>Awaiting admin</span><strong>Rs. <?= number_format($processing, 0) ?></strong><em>Screenshot in queue</em></div>
        </div>
        <div class="col-6 col-lg-3">
            <div class="ops-stat"><span>Released</span><strong>Rs. <?= number_format($released, 0) ?></strong><em>In customer wallets</em></div>
        </div>
    </div>
</div>

<?php if (! $approved): ?>
    <div class="alert alert-warning rounded-4 border-0 mb-4">
        <strong>Shop under review.</strong> You can update the profile. Scanning cards starts after Solqam approves this merchant.
        <?= ! empty($shop['rejection_reason']) ? ' Reason: ' . esc($shop['rejection_reason']) : '' ?>
        <a class="alert-link ms-1" href="<?= merchant_url('profile') ?>">Edit shop profile</a>
    </div>
<?php endif; ?>

<div class="row g-3 mb-4">
    <div class="col-6 col-xl">
        <div class="kpi-tile h-100"><span>All sales</span><strong>Rs. <?= number_format((float) ($s['sales'] ?? 0), 0) ?></strong><em><?= $visits ?> visits</em></div>
    </div>
    <div class="col-6 col-xl">
        <div class="kpi-tile gold h-100"><span>Average bill</span><strong>Rs. <?= number_format($avgBill, 0) ?></strong><em>Lifetime POS</em></div>
    </div>
    <div class="col-6 col-xl">
        <div class="kpi-tile accent h-100"><span>Credits created</span><strong>Rs. <?= number_format($credits, 0) ?></strong><em>5% of bills</em></div>
    </div>
    <div class="col-6 col-xl">
        <div class="kpi-tile emerald h-100"><span>Released</span><strong>Rs. <?= number_format($released, 0) ?></strong><em>After admin approval</em></div>
    </div>
    <div class="col-6 col-xl">
        <div class="kpi-tile h-100"><span>Customers</span><strong><?= count($customers ?? []) ?></strong><em>Credit history</em></div>
    </div>
</div>

<div class="row g-4 mb-4">
    <div class="col-xl-7">
        <div class="command-panel p-4 h-100">
            <div class="d-flex flex-wrap justify-content-between align-items-start gap-2 mb-3">
                <div>
                    <h5 class="fw-bold mb-0">How POS works</h5>
                    <small class="text-muted">Scan → 5% unpaid → pay Solqam with screenshot → admin releases wallets</small>
                </div>
                <div class="d-flex flex-wrap gap-2">
                    <?php if (empty($isSeller)): ?>
                        <a class="btn btn-sm btn-outline-primary rounded-pill" href="<?= merchant_url('become-seller') ?>">Also sell online</a>
                    <?php else: ?>
                        <a class="btn btn-sm btn-outline-primary rounded-pill" href="<?= site_url('seller/dashboard') ?>">Seller Hub</a>
                    <?php endif; ?>
                </div>
            </div>
            <div class="mhub-step">
                <span class="n">1</span>
                <div><strong>Scan the customer card</strong><div class="small text-muted">QR or number. PIN / CVV when the bill needs it. Own card cannot be scanned.</div></div>
            </div>
            <div class="mhub-step">
                <span class="n">2</span>
                <div><strong>5% stays with you as unpaid</strong><div class="small text-muted">Customer does not get wallet credit yet. Oldest visit is first in line.</div></div>
            </div>
            <div class="mhub-step">
                <span class="n">3</span>
                <div><strong>Pay Solqam + screenshot</strong><div class="small text-muted">Partial amounts allowed. Admin must approve before wallets move.</div></div>
            </div>
            <?php if ($approved): ?>
                <?= view('partials/solqam_collection_accounts', [
                    'accounts'       => $accounts ?? [],
                    'hasPayTo'       => $hasPayTo ?? false,
                    'embed'          => true,
                    'collectionHint' => 'JazzCash / EasyPaisa / bank — same as Seller Hub. Partial payments allowed.',
                ]) ?>
            <?php endif; ?>
        </div>
    </div>
    <div class="col-xl-5">
        <div class="command-panel p-4 h-100">
            <?php if (! empty($merchantCard['pan'])): ?>
                <div class="d-flex justify-content-between align-items-start gap-2 mb-3">
                    <div>
                        <h6 class="fw-bold mb-1">Your merchant card</h6>
                        <div class="font-monospace fw-bold fs-5" style="color:#0B30E6;"><?= esc($merchantCard['pan_grouped'] ?? \App\Services\Card\SolqamCardNumber::display((string) $merchantCard['pan'])) ?></div>
                        <?php if (! empty($shop['category_code'])): ?>
                            <div class="small text-muted">Type <?= esc($shop['category_code']) ?> · <?= esc($shop['category'] ?? '') ?></div>
                        <?php endif; ?>
                    </div>
                    <a class="btn btn-sm btn-outline-dark rounded-pill" href="<?= site_url('card') ?>">Plastic</a>
                </div>
            <?php endif; ?>
            <h6 class="fw-bold mb-2">FIFO queue</h6>
            <?php if (empty($fifo)): ?>
                <p class="text-muted small mb-0">No unpaid visits. Next scan will appear here.</p>
            <?php else: ?>
                <div class="d-flex flex-column gap-2">
                    <?php foreach (array_slice($fifo, 0, 5) as $i => $q): ?>
                        <div class="d-flex justify-content-between align-items-center border rounded-4 px-3 py-2">
                            <div>
                                <div class="small text-muted">#<?= $i + 1 ?> · <?= esc($q['customer_name'] ?? 'Customer') ?></div>
                                <div class="font-monospace small"><?= esc(merchant_scan_pan($q)) ?></div>
                            </div>
                            <div class="fw-bold text-danger">Rs. <?= number_format((float) ($q['remaining'] ?? $q['unpaid'] ?? $q['credit_amount'] ?? 0), 0) ?></div>
                        </div>
                    <?php endforeach; ?>
                </div>
                <?php if ($approved && $pending > 0): ?>
                    <a class="btn btn-solqam rounded-pill w-100 mt-3" href="<?= merchant_url('credits') ?>">Pay oldest first · Rs. <?= number_format($pending, 0) ?></a>
                <?php endif; ?>
            <?php endif; ?>
        </div>
    </div>
</div>

<div class="command-panel p-4 mb-4">
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
        <div>
            <h5 class="fw-bold mb-0">Credits given to customers</h5>
            <small class="text-muted">One row per customer. Unpaid is still owed. Payments go to the earliest visit first.</small>
        </div>
        <?php if ($approved && $pending > 0): ?>
            <a class="btn btn-solqam rounded-pill" href="<?= merchant_url('credits') ?>">Pay all unpaid · Rs. <?= number_format($pending, 0) ?></a>
        <?php else: ?>
            <a class="small" href="<?= merchant_url('customers') ?>">Full history</a>
        <?php endif; ?>
    </div>
    <?= view('partials/merchant_customer_credits', ['customers' => $customers ?? [], 'showPayHint' => $approved, 'compact' => true]) ?>
</div>

<div class="command-panel p-4">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h5 class="fw-bold mb-0">Recent card sales</h5>
        <?php if ($approved): ?><a class="small" href="<?= merchant_url('credits') ?>">All receipts</a><?php endif; ?>
    </div>
    <?php if (empty($scans)): ?>
        <p class="text-muted small mb-0">No scans yet.<?= $approved ? ' Open Scan card to start POS.' : ' Scanning unlocks after approval.' ?></p>
    <?php else: ?>
        <div class="table-responsive">
            <table class="table ops-table align-middle mb-0">
                <thead><tr><th>Receipt</th><th>Customer</th><th>Card</th><th>Bill</th><th>Credit</th><th>Status</th><th>When</th></tr></thead>
                <tbody>
                <?php foreach (array_slice($scans, 0, 12) as $row): ?>
                    <tr>
                        <td class="font-monospace"><?= esc(merchant_receipt($row['id'])) ?></td>
                        <td class="fw-semibold"><?= esc($row['customer_name'] ?? 'Customer') ?></td>
                        <td class="font-monospace small"><?= esc(merchant_scan_pan($row)) ?></td>
                        <td>Rs. <?= number_format((float) $row['bill_amount'], 0) ?></td>
                        <td>Rs. <?= number_format((float) $row['credit_amount'], 0) ?></td>
                        <td><?= merchant_status_badge((string) $row['status']) ?></td>
                        <td class="small text-muted"><?= esc($row['created_at'] ?? '') ?></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>
<?= $this->endSection() ?>
