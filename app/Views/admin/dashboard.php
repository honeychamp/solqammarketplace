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
$chart = $chart ?? ['labels' => [], 'gmv' => [], 'comm' => [], 'orders' => []];
$pulse = $pulse ?? [];
$payMix = $payMix ?? ['labels' => ['COD'], 'values' => [0]];
$topSkus = $topSkus ?? [];
$gmvDelta = dash_delta((float) ($pulse['week_gmv'] ?? 0), (float) ($pulse['last_week_gmv'] ?? 0));
$ordDelta = dash_delta((float) ($pulse['week_orders'] ?? 0), (float) ($pulse['last_week_orders'] ?? 0));
$todayDelta = dash_delta((float) ($pulse['today_gmv'] ?? 0), (float) ($pulse['yesterday_gmv'] ?? 0));
$hour = (int) date('G');
$greet = $hour < 12 ? 'Good morning' : ($hour < 17 ? 'Good afternoon' : 'Good evening');
$pipeTotal = max(1, array_sum($statusCounts ?? []));
?>
<div class="ops-hero admin mb-4">
    <div class="d-flex flex-wrap justify-content-between align-items-start gap-3">
        <div>
            <div class="ops-kicker">Control tower · <?= date('l, d F Y') ?></div>
            <h2 class="fw-black mb-2 text-white"><?= $greet ?>, <?= esc(explode(' ', (string) session()->get('user.name'))[0] ?: 'Admin') ?></h2>
            <p class="mb-0 text-white-50">Live GMV, intake, fulfillment and settlement — the same density as a national marketplace ops desk.</p>
        </div>
        <div class="d-flex flex-wrap gap-2">
            <div class="ops-live-chip"><span class="ops-dot"></span> Systems live</div>
            <a href="<?= site_url('admin/reports') ?>" class="btn rounded-pill px-3 text-white" style="background:linear-gradient(135deg,#F0142F,#cc0e24);">Full analytics</a>
            <a href="<?= site_url('admin/orders') ?>" class="btn btn-light rounded-pill px-3">Order monitor</a>
            <a href="<?= site_url('admin/my-products/create') ?>" class="btn rounded-pill px-3 fw-bold" style="background:#F59E0B;color:#0F172A;">List SKU</a>
        </div>
    </div>
    <div class="row g-3 mt-4">
        <div class="col-6 col-lg-3">
            <div class="ops-stat"><span>Today GMV</span><strong>Rs. <?= number_format((float) ($pulse['today_gmv'] ?? 0), 0) ?></strong><em class="delta-<?= $todayDelta[1] ?>"><?= $todayDelta[0] ?> vs yesterday</em></div>
        </div>
        <div class="col-6 col-lg-3">
            <div class="ops-stat"><span>Today orders</span><strong><?= (int) ($pulse['today_orders'] ?? 0) ?></strong><em><?= (int) ($pulse['yesterday_orders'] ?? 0) ?> yesterday</em></div>
        </div>
        <div class="col-6 col-lg-3">
            <div class="ops-stat"><span>7-day GMV</span><strong>Rs. <?= number_format((float) ($pulse['week_gmv'] ?? 0), 0) ?></strong><em class="delta-<?= $gmvDelta[1] ?>"><?= $gmvDelta[0] ?> vs prior week</em></div>
        </div>
        <div class="col-6 col-lg-3">
            <div class="ops-stat"><span>Average order</span><strong>Rs. <?= number_format((float) ($aov ?? 0), 0) ?></strong><em><?= (int) $orderCount ?> lifetime orders</em></div>
        </div>
    </div>
</div>

<div class="row g-3 mb-4">
    <div class="col-6 col-xl">
        <div class="kpi-tile h-100"><span>Lifetime GMV</span><strong>Rs. <?= number_format($totalGmv, 0) ?></strong></div>
    </div>
    <div class="col-6 col-xl">
        <div class="kpi-tile accent h-100"><span>Commission</span><strong>Rs. <?= number_format($totalCommissionEarned, 0) ?></strong><em>by product category</em></div>
    </div>
    <div class="col-6 col-xl">
        <div class="kpi-tile gold h-100"><span>Wallet spend</span><strong>Rs. <?= number_format($walletSpend ?? 0, 0) ?></strong></div>
    </div>
    <div class="col-6 col-xl">
        <div class="kpi-tile emerald h-100"><span>Cashback issued</span><strong>Rs. <?= number_format($totalCashback ?? $walletCashback ?? 0, 0) ?></strong></div>
    </div>
    <div class="col-6 col-xl">
        <div class="kpi-tile accent h-100"><span>Network</span><strong><?= (int) $totalCustomers ?> / <?= (int) $totalSellers ?></strong><em>buyers / sellers · <?= (int) $pendingSellerCount ?> pending</em></div>
    </div>
    <div class="col-6 col-xl">
        <div class="kpi-tile gold h-100"><span>Catalog</span><strong><?= (int) $totalProducts ?></strong><em><?= (int) $myProductCount ?> first-party SKUs</em></div>
    </div>
</div>

<div class="row g-4 mb-4">
    <div class="col-xl-7">
        <div class="command-panel p-4 h-100">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <div>
                    <h5 class="fw-bold mb-0">Revenue velocity</h5>
                    <small class="text-muted">14-day GMV, commission and order count</small>
                </div>
                <span class="badge text-bg-light border">PKR</span>
            </div>
            <div class="chart-shell"><canvas id="adminTrend" height="140"></canvas></div>
        </div>
    </div>
    <div class="col-xl-5">
        <div class="row g-4 h-100">
            <div class="col-md-6 col-xl-12 col-xxl-6">
                <div class="command-panel p-4 h-100">
                    <h6 class="fw-bold mb-3">Payment mix</h6>
                    <canvas id="adminPay" height="180"></canvas>
                </div>
            </div>
            <div class="col-md-6 col-xl-12 col-xxl-6">
                <div class="command-panel p-4 h-100">
                    <h6 class="fw-bold mb-3">Fulfillment funnel</h6>
                    <?php
                    $statuses = [
                        'placed' => ['#F59E0B', 'Placed'],
                        'confirmed' => ['#0B30E6', 'Confirmed'],
                        'shipped' => ['#3d5df0', 'Shipped'],
                        'delivered' => ['#10B981', 'Delivered'],
                        'cancelled' => ['#F0142F', 'Cancelled'],
                    ];
                    foreach ($statuses as $key => $s):
                        $n = (int) ($statusCounts[$key] ?? 0);
                        $pct = round($n / $pipeTotal * 100);
                    ?>
                        <div class="mb-2">
                            <div class="d-flex justify-content-between small mb-1"><span style="color:<?= $s[0] ?>"><?= $s[1] ?></span><span class="fw-bold"><?= $n ?></span></div>
                            <div class="ops-bar"><span style="width:<?= $pct ?>%;background:<?= $s[0] ?>"></span></div>
                        </div>
                    <?php endforeach; ?>
                    <a href="<?= site_url('admin/orders') ?>" class="btn btn-sm btn-outline-primary w-100 mt-2 rounded-pill">Open live monitor</a>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row g-4 mb-4">
    <div class="col-lg-7">
        <div class="command-panel p-4 h-100">
            <div class="d-flex justify-content-between mb-3">
                <h5 class="fw-bold mb-0">Top moving SKUs</h5>
                <a href="<?= site_url('admin/products') ?>" class="small">Moderation</a>
            </div>
            <?php if (empty($topSkus)): ?>
                <p class="text-muted mb-0">No order volume yet. First sales will rank here.</p>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table ops-table align-middle mb-0">
                        <thead><tr><th>Product</th><th>Units</th><th class="text-end">Revenue</th></tr></thead>
                        <tbody>
                        <?php foreach ($topSkus as $sku): ?>
                            <tr>
                                <td class="fw-semibold"><?= esc($sku['product_name']) ?></td>
                                <td><?= (int) $sku['units'] ?></td>
                                <td class="text-end font-monospace">Rs. <?= number_format((float) $sku['revenue'], 0) ?></td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>
    <div class="col-lg-5">
        <div class="command-panel p-4 h-100">
            <h5 class="fw-bold mb-3">Attention queue</h5>
            <div class="ops-queue">
                <a href="#hub-sellers"><strong><?= (int) $pendingSellerCount ?></strong><span>Seller applications</span></a>
                <a href="#hub-returns"><strong><?= count($pendingReturns) ?></strong><span>Returns to wallet</span></a>
                <a href="#hub-payouts"><strong><?= count($pendingPayouts) ?></strong><span>Unpaid payouts</span></a>
                <a href="#hub-tickets"><strong><?= count($openTickets) ?></strong><span>Open tickets</span></a>
            </div>
        </div>
    </div>
</div>

<div class="row g-4">
    <div class="col-lg-7" id="hub-orders">
        <div class="command-panel p-4 mb-4">
            <h5 class="fw-bold mb-1">First-party fulfillment</h5>
            <p class="small text-muted">Seller cut follows the product category. Admin SKUs skip commission. Prepaid cashback is instant; COD after delivery.</p>
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
            <h5 class="fw-bold mb-3">Catalog moderation</h5>
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
            <h5 class="fw-bold mb-3">Seller commission by category</h5>
            <p class="small text-muted">Cut follows the product’s category. Your own SKUs stay at 0%.</p>
            <?php foreach (($categoryRates ?? []) as $crow): ?>
                <form action="<?= site_url('admin/categories/update/' . $crow['id']) ?>" method="POST" class="d-flex align-items-center gap-2 mb-2">
                    <?= csrf_field() ?>
                    <input type="hidden" name="hub" value="admin">
                    <div class="small flex-grow-1 text-truncate"><?= esc($crow['name']) ?></div>
                    <div class="input-group input-group-sm" style="max-width:140px;">
                        <input type="number" step="0.01" min="0" max="50" name="commission_percent" class="form-control" value="<?= esc($crow['commission_percent'] ?? $crow['effective_commission'] ?? 10) ?>">
                        <span class="input-group-text">%</span>
                    </div>
                    <button class="btn btn-sm btn-primary">Save</button>
                </form>
            <?php endforeach; ?>
            <a href="<?= site_url('admin/categories') ?>" class="small">Category images &amp; all rates →</a>
        </div>
    </div>
</div>
<?= $this->endSection() ?>
<?= $this->section('scripts') ?>
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
(() => {
    const grid = { color: 'rgba(148,163,184,.25)' };
    const trend = document.getElementById('adminTrend');
    if (trend) {
        new Chart(trend, {
            type: 'line',
            data: {
                labels: <?= json_encode($chart['labels'] ?? []) ?>,
                datasets: [
                    { label: 'GMV', data: <?= json_encode($chart['gmv'] ?? []) ?>, yAxisID: 'y', borderColor: '#0B30E6', backgroundColor: 'rgba(11,48,230,.12)', fill: true, tension: .35, borderWidth: 2.5, pointRadius: 0 },
                    { label: 'Commission', data: <?= json_encode($chart['comm'] ?? []) ?>, yAxisID: 'y', borderColor: '#F0142F', tension: .35, borderWidth: 2, pointRadius: 0 },
                    { label: 'Orders', data: <?= json_encode($chart['orders'] ?? []) ?>, yAxisID: 'y1', borderColor: '#10B981', borderDash: [5,4], tension: .3, borderWidth: 2, pointRadius: 0 }
                ]
            },
            options: {
                interaction: { mode: 'index', intersect: false },
                plugins: { legend: { position: 'bottom' } },
                scales: {
                    y: { beginAtZero: true, grid, ticks: { callback: v => 'Rs ' + Number(v).toLocaleString() } },
                    y1: { beginAtZero: true, position: 'right', grid: { drawOnChartArea: false } }
                }
            }
        });
    }
    const pay = document.getElementById('adminPay');
    if (pay) {
        new Chart(pay, {
            type: 'doughnut',
            data: {
                labels: <?= json_encode($payMix['labels'] ?? []) ?>,
                datasets: [{ data: <?= json_encode($payMix['values'] ?? []) ?>, backgroundColor: ['#0B30E6','#F0142F','#F59E0B','#10B981','#3d5df0'], borderWidth: 0 }]
            },
            options: { plugins: { legend: { position: 'bottom' } }, cutout: '62%' }
        });
    }
})();
</script>
<?= $this->endSection() ?>
