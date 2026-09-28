<?= $this->extend('layouts/admin') ?>
<?= $this->section('content') ?>
<?php
$pendingSellers = $pendingSellers ?? [];
$pendingSellerCount = $pendingSellerCount ?? count($pendingSellers);
$openOrders = $openOrders ?? [];
$pendingReturns = $pendingReturns ?? [];
$pendingPayouts = $pendingPayouts ?? [];
$openTickets = $openTickets ?? [];
$moderationProducts = $moderationProducts ?? [];
$commission = $commission ?? ['percentage' => 10];
$chart = $chart ?? ['labels' => [], 'gmv' => [], 'comm' => []];
$hour = (int) date('G');
$greet = $hour < 12 ? 'Good morning' : ($hour < 17 ? 'Good afternoon' : 'Good evening');
?>
<div class="command-hero admin mb-4">
    <div class="d-flex flex-wrap justify-content-between align-items-end gap-3">
        <div>
            <div class="command-kicker">Solqam Admin · Live marketplace</div>
            <h2 class="fw-black mb-1 text-white"><?= $greet ?>, <?= esc(explode(' ', (string) session()->get('user.name'))[0] ?: 'Admin') ?></h2>
            <p class="mb-0 text-white-50">GMV, commission, wallets, and fulfillment — one console.</p>
        </div>
        <div class="d-flex flex-wrap gap-2">
            <a href="<?= site_url('admin/customers') ?>" class="btn btn-light rounded-pill">Customer 360</a>
            <a href="<?= site_url('admin/my-orders') ?>" class="btn btn-outline-light rounded-pill">My orders</a>
            <a href="<?= site_url('admin/my-products/create') ?>" class="btn btn-outline-light rounded-pill">Add product</a>
        </div>
    </div>
</div>

<div class="row g-3 mb-4">
    <div class="col-6 col-xl-2">
        <div class="kpi-tile h-100"><span>GMV</span><strong>Rs. <?= number_format($totalGmv, 0) ?></strong></div>
    </div>
    <div class="col-6 col-xl-2">
        <div class="kpi-tile accent h-100"><span>Commission</span><strong>Rs. <?= number_format($totalCommissionEarned, 0) ?></strong></div>
    </div>
    <div class="col-6 col-xl-2">
        <div class="kpi-tile gold h-100"><span>Wallet spend</span><strong>Rs. <?= number_format($walletSpend ?? 0, 0) ?></strong></div>
    </div>
    <div class="col-6 col-xl-2">
        <div class="kpi-tile emerald h-100"><span>Cashback</span><strong>Rs. <?= number_format($totalCashback ?? $walletCashback ?? 0, 0) ?></strong></div>
    </div>
    <div class="col-6 col-xl-2">
        <div class="kpi-tile h-100"><span>Buyers / Sellers</span><strong><?= (int) $totalCustomers ?> / <?= (int) $totalSellers ?></strong><em><?= (int) $pendingSellerCount ?> pending</em></div>
    </div>
    <div class="col-6 col-xl-2">
        <div class="kpi-tile h-100"><span>Orders</span><strong><?= (int) $orderCount ?></strong><em><?= (int) $myProductCount ?> admin SKUs</em></div>
    </div>
</div>

<div class="row g-4 mb-4">
    <div class="col-lg-8">
        <div class="command-panel p-4 h-100">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <div>
                    <h5 class="fw-bold mb-0">7-day velocity</h5>
                    <small class="text-muted">GMV vs platform commission</small>
                </div>
            </div>
            <div class="chart-shell"><canvas id="adminTrend" height="120"></canvas></div>
        </div>
    </div>
    <div class="col-lg-4">
        <div class="command-panel p-4 h-100">
            <h5 class="fw-bold mb-3">Pipeline</h5>
            <?php
            $statuses = [
                'placed' => ['#F59E0B', 'Placed'],
                'confirmed' => ['#3B82F6', 'Confirmed'],
                'shipped' => ['#8B5CF6', 'Shipped'],
                'delivered' => ['#10B981', 'Delivered'],
                'cancelled' => ['#EF4444', 'Cancelled'],
            ];
            foreach ($statuses as $key => $s): ?>
                <div class="d-flex justify-content-between align-items-center py-2 border-bottom">
                    <span class="small fw-semibold" style="color:<?= $s[0] ?>"><?= $s[1] ?></span>
                    <span class="fw-black"><?= $statusCounts[$key] ?? 0 ?></span>
                </div>
            <?php endforeach; ?>
            <a href="<?= site_url('admin/orders') ?>" class="btn btn-sm btn-outline-primary w-100 mt-3 rounded-pill">Open live monitor</a>
        </div>
    </div>
</div>

<div class="row g-4">
    <div class="col-lg-7" id="hub-orders">
        <div class="command-panel p-4 mb-4">
            <h5 class="fw-bold mb-1">Your products to fulfill</h5>
            <p class="small text-muted">No commission on admin SKUs. Cashback is whatever you set on each product. Prepaid instantly; COD after delivery.</p>
            <?php if (empty($openOrders)): ?>
                <p class="text-muted mb-0">No open admin packages.</p>
            <?php else: ?>
                <?php foreach ($openOrders as $o): ?>
                    <div class="pulse-row mb-3">
                        <div class="d-flex justify-content-between flex-wrap gap-2 mb-2">
                            <div>
                                <strong><?= esc($o['order_number']) ?></strong>
                                <div class="small"><a href="<?= site_url('admin/customers/' . $o['user_id']) ?>"><?= esc($o['customer_name']) ?></a> · <?= esc($o['customer_phone'] ?? '') ?></div>
                            </div>
                            <div class="fw-bold">Rs. <?= number_format((float) $o['final_payable'] ?: $o['total_amount'], 0) ?></div>
                        </div>
                        <form action="<?= site_url('admin/orders/' . $o['id'] . '/status') ?>" method="POST" class="d-flex gap-2 flex-wrap">
                            <?= csrf_field() ?>
                            <input type="hidden" name="hub" value="admin">
                            <select name="status" class="form-select form-select-sm" style="max-width:180px">
                                <?php foreach (['confirmed','shipped','delivered'] as $st): ?>
                                    <option value="<?= $st ?>" <?= ($o['admin_pkg_status'] ?? $o['status']) === $st ? 'selected' : '' ?>><?= ucfirst($st) ?></option>
                                <?php endforeach; ?>
                            </select>
                            <button class="btn btn-sm btn-primary">Update</button>
                            <a class="btn btn-sm btn-light border" href="<?= site_url('admin/orders/' . $o['id']) ?>">Order</a>
                            <a class="btn btn-sm btn-outline-primary" href="<?= site_url('admin/customers/' . $o['user_id']) ?>">360</a>
                        </form>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>

        <div class="command-panel p-4 mb-4" id="hub-products">
            <h5 class="fw-bold mb-3">Product moderation</h5>
            <?php if (empty($moderationProducts)): ?>
                <p class="text-muted mb-0">No listings yet.</p>
            <?php else: ?>
                <?php foreach ($moderationProducts as $p): ?>
                    <div class="d-flex justify-content-between align-items-center border-bottom py-2 gap-2">
                        <div class="text-truncate">
                            <div class="fw-semibold"><?= esc($p['name']) ?></div>
                            <small class="text-muted"><?= esc($p['store_name'] ?? 'Store') ?> · <?= esc($p['status']) ?></small>
                        </div>
                        <form action="<?= site_url('admin/products/' . $p['id'] . '/toggle') ?>" method="POST">
                            <?= csrf_field() ?>
                            <input type="hidden" name="hub" value="admin">
                            <button class="btn btn-sm <?= ($p['status'] ?? '') === 'active' ? 'btn-outline-danger' : 'btn-outline-success' ?>">
                                <?= ($p['status'] ?? '') === 'active' ? 'Hide' : 'Show' ?>
                            </button>
                        </form>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>

    <div class="col-lg-5">
        <div class="command-panel p-4 mb-4" id="hub-sellers">
            <h5 class="fw-bold mb-3">Seller applications</h5>
            <?php if (empty($pendingSellers)): ?>
                <p class="text-muted small mb-0">No pending sellers.</p>
            <?php else: ?>
                <?php foreach ($pendingSellers as $s): ?>
                    <div class="pulse-row mb-2">
                        <div class="fw-bold"><?= esc($s['store_name'] ?: 'Store') ?></div>
                        <div class="small text-muted mb-2"><?= esc($s['owner_name']) ?> · <?= esc($s['owner_email']) ?></div>
                        <div class="d-flex gap-2">
                            <form action="<?= site_url('admin/sellers/' . $s['id'] . '/approve') ?>" method="POST">
                                <?= csrf_field() ?>
                                <input type="hidden" name="hub" value="admin">
                                <button class="btn btn-sm btn-success">Approve</button>
                            </form>
                            <form action="<?= site_url('admin/sellers/' . $s['id'] . '/reject') ?>" method="POST">
                                <?= csrf_field() ?>
                                <input type="hidden" name="hub" value="admin">
                                <button class="btn btn-sm btn-outline-danger">Reject</button>
                            </form>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>

        <div class="command-panel p-4 mb-4" id="hub-returns">
            <h5 class="fw-bold mb-3">Returns to wallet</h5>
            <?php if (empty($pendingReturns)): ?>
                <p class="text-muted small mb-0">No return requests.</p>
            <?php else: ?>
                <?php foreach ($pendingReturns as $r): ?>
                    <div class="pulse-row mb-2">
                        <div class="small text-muted"><?= esc($r['order_number'] ?? '') ?> · <?= esc($r['customer_name'] ?? '') ?></div>
                        <div class="fw-semibold">Rs. <?= number_format((float) $r['refund_amount'], 0) ?></div>
                        <div class="d-flex gap-2 mt-2">
                            <form action="<?= site_url('admin/returns/' . $r['id'] . '/approve') ?>" method="POST">
                                <?= csrf_field() ?>
                                <input type="hidden" name="hub" value="admin">
                                <button class="btn btn-sm btn-success">Refund wallet</button>
                            </form>
                            <form action="<?= site_url('admin/returns/' . $r['id'] . '/reject') ?>" method="POST">
                                <?= csrf_field() ?>
                                <input type="hidden" name="hub" value="admin">
                                <button class="btn btn-sm btn-outline-danger">Reject</button>
                            </form>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>

        <div class="command-panel p-4 mb-4" id="hub-payouts">
            <h5 class="fw-bold mb-3">Seller payouts</h5>
            <?php if (empty($pendingPayouts)): ?>
                <p class="text-muted small mb-0">No unpaid payouts.</p>
            <?php else: ?>
                <?php foreach ($pendingPayouts as $p): ?>
                    <div class="d-flex justify-content-between align-items-center border-bottom py-2 gap-2">
                        <div>
                            <div class="fw-semibold"><?= esc($p['store_name'] ?? $p['seller_name'] ?? 'Seller') ?></div>
                            <small class="text-muted"><?= esc($p['order_number'] ?? '') ?> · Rs. <?= number_format((float) $p['amount'], 0) ?></small>
                        </div>
                        <form action="<?= site_url('admin/payouts/' . $p['id'] . '/paid') ?>" method="POST">
                            <?= csrf_field() ?>
                            <input type="hidden" name="hub" value="admin">
                            <button class="btn btn-sm btn-outline-success">Mark paid</button>
                        </form>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>

        <div class="command-panel p-4 mb-4" id="hub-tickets">
            <h5 class="fw-bold mb-3">Help tickets</h5>
            <?php if (empty($openTickets)): ?>
                <p class="text-muted small mb-0">No open tickets.</p>
            <?php else: ?>
                <?php foreach ($openTickets as $t): ?>
                    <div class="border-bottom pb-3 mb-3">
                        <div class="fw-semibold"><?= esc($t['subject']) ?></div>
                        <div class="small text-muted mb-2"><?= esc($t['customer_name'] ?? '') ?></div>
                        <form action="<?= site_url('admin/tickets/' . $t['id'] . '/reply') ?>" method="POST">
                            <?= csrf_field() ?>
                            <input type="hidden" name="hub" value="admin">
                            <input type="hidden" name="status" value="replied">
                            <textarea name="message" class="form-control form-control-sm mb-2" rows="2" required></textarea>
                            <button class="btn btn-sm btn-primary">Send reply</button>
                        </form>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>

        <div class="command-panel p-4" id="hub-commission">
            <h5 class="fw-bold mb-3">Seller commission rate</h5>
            <form action="<?= site_url('admin/commissions') ?>" method="POST" class="d-flex gap-2">
                <?= csrf_field() ?>
                <input type="hidden" name="hub" value="admin">
                <div class="input-group">
                    <input type="number" step="0.01" min="0" max="50" name="percentage" class="form-control" value="<?= esc($commission['percentage'] ?? 10) ?>">
                    <span class="input-group-text">%</span>
                </div>
                <button class="btn btn-primary">Save</button>
            </form>
            <p class="small text-muted mt-2 mb-0">Does not apply to your own catalog.</p>
        </div>
    </div>
</div>
<?= $this->endSection() ?>
<?= $this->section('scripts') ?>
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
(() => {
    const el = document.getElementById('adminTrend');
    if (!el || typeof Chart === 'undefined') return;
    new Chart(el, {
        type: 'line',
        data: {
            labels: <?= json_encode($chart['labels'] ?? []) ?>,
            datasets: [
                { label: 'GMV', data: <?= json_encode($chart['gmv'] ?? []) ?>, borderColor: '#0B30E6', backgroundColor: 'rgba(11,48,230,.12)', fill: true, tension: .4, borderWidth: 2 },
                { label: 'Commission', data: <?= json_encode($chart['comm'] ?? []) ?>, borderColor: '#F0142F', tension: .4, borderWidth: 2 }
            ]
        },
        options: { plugins: { legend: { display: true } }, scales: { y: { beginAtZero: true } } }
    });
})();
</script>
<?= $this->endSection() ?>
