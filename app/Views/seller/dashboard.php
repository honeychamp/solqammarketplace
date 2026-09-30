<?= $this->extend('layouts/seller') ?>
<?= $this->section('content') ?>
<?php
$openOrders = $openOrders ?? [];
$questions = $questions ?? [];
$products = $products ?? [];
$payouts = $payouts ?? [];
$chats = $chats ?? [];
$chart = $chart ?? ['labels' => [], 'gmv' => [], 'comm' => [], 'orders' => []];
$pulse = $pulse ?? [];
$payMix = $payMix ?? ['labels' => ['COD'], 'values' => [0]];
$topSkus = $topSkus ?? [];
$slaBreaches = $slaBreaches ?? [];
$gmvDelta = dash_delta((float) ($pulse['week_gmv'] ?? 0), (float) ($pulse['last_week_gmv'] ?? 0));
$todayDelta = dash_delta((float) ($pulse['today_gmv'] ?? 0), (float) ($pulse['yesterday_gmv'] ?? 0));
$health = max(12, 100 - (count($slaBreaches) * 18));
$hour = (int) date('G');
$greet = $hour < 12 ? 'Good morning' : ($hour < 17 ? 'Good afternoon' : 'Good evening');
?>
<div class="ops-hero seller mb-4">
    <div class="d-flex flex-wrap justify-content-between align-items-start gap-3">
        <div>
            <div class="ops-kicker">Merchant intelligence · <?= esc(session()->get('user.store_name') ?? 'Your store') ?></div>
            <h2 class="fw-black mb-2 text-white"><?= $greet ?></h2>
            <p class="mb-0 text-white-50">Sales velocity, SLA health, stock risk and settlement — built like a full seller command center.</p>
        </div>
        <div class="d-flex flex-wrap gap-2">
            <div class="ops-live-chip"><span class="ops-dot"></span> Store online</div>
            <a href="<?= site_url('seller/products/create') ?>" class="btn rounded-pill px-3 text-white" style="background:linear-gradient(135deg,#F0142F,#cc0e24);">Add product</a>
            <a href="<?= site_url('seller/performance') ?>" class="btn rounded-pill px-3 fw-bold" style="background:#F59E0B;color:#0F172A;">SKU ranking</a>
            <a href="#hub-orders" class="btn btn-light rounded-pill px-3">Fulfill</a>
        </div>
    </div>
    <div class="row g-3 mt-4">
        <div class="col-6 col-lg-3">
            <div class="ops-stat"><span>Today sales</span><strong>Rs. <?= number_format((float) ($pulse['today_gmv'] ?? 0), 0) ?></strong><em class="delta-<?= $todayDelta[1] ?>"><?= $todayDelta[0] ?> vs yesterday</em></div>
        </div>
        <div class="col-6 col-lg-3">
            <div class="ops-stat"><span>7-day sales</span><strong>Rs. <?= number_format((float) ($pulse['week_gmv'] ?? 0), 0) ?></strong><em class="delta-<?= $gmvDelta[1] ?>"><?= $gmvDelta[0] ?> vs prior week</em></div>
        </div>
        <div class="col-6 col-lg-3">
            <div class="ops-stat"><span>Average order</span><strong>Rs. <?= number_format((float) ($aov ?? 0), 0) ?></strong><em><?= (int) $totalOrders ?> orders lifetime</em></div>
        </div>
        <div class="col-6 col-lg-3">
            <div class="ops-stat"><span>SLA health</span><strong><?= (int) $health ?>%</strong><em><?= count($slaBreaches) ?> overdue packages</em></div>
        </div>
    </div>
</div>

<div class="row g-3 mb-4">
    <div class="col-6 col-xl">
        <div class="kpi-tile h-100"><span>Gross merchandise</span><strong>Rs. <?= number_format($totalSales, 0) ?></strong></div>
    </div>
    <div class="col-6 col-xl">
        <div class="kpi-tile accent h-100"><span>Commission cut</span><strong>Rs. <?= number_format($totalCommission ?? 0, 0) ?></strong></div>
    </div>
    <div class="col-6 col-xl">
        <div class="kpi-tile emerald h-100"><span>Cashback funded</span><strong>Rs. <?= number_format($totalCashback ?? 0, 0) ?></strong></div>
    </div>
    <div class="col-6 col-xl">
        <div class="kpi-tile gold h-100"><span>Net to you</span><strong>Rs. <?= number_format($netEarnings ?? 0, 0) ?></strong></div>
    </div>
    <div class="col-6 col-xl">
        <div class="kpi-tile h-100"><span>Payout wallet</span><strong>Rs. <?= number_format($sellerWallet ?? 0, 0) ?></strong><em>Pending Rs. <?= number_format($pendingPayout ?? 0, 0) ?></em></div>
    </div>
    <div class="col-6 col-xl">
        <div class="kpi-tile accent h-100"><span>Demand</span><strong><?= (int) $totalOrders ?></strong><em><?= count($openOrders) ?> open · <?= (int) ($buyerCount ?? 0) ?> buyers · <?= (int) ($lowStockCount ?? 0) ?> low stock</em></div>
    </div>
</div>

<div class="row g-4 mb-4">
    <div class="col-xl-7">
        <div class="command-panel p-4 h-100">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <div>
                    <h5 class="fw-bold mb-0">Sales velocity</h5>
                    <small class="text-muted">14-day goods, commission and order volume</small>
                </div>
            </div>
            <div class="chart-shell"><canvas id="sellerTrend" height="140"></canvas></div>
        </div>
    </div>
    <div class="col-xl-5">
        <div class="command-panel p-4 h-100">
            <h6 class="fw-bold mb-3">Checkout mix</h6>
            <canvas id="sellerPay" height="200"></canvas>
            <div class="d-flex justify-content-between small mt-3 pt-3 border-top">
                <span class="text-muted">Paid out</span><strong>Rs. <?= number_format($paidPayout ?? 0, 0) ?></strong>
            </div>
            <a href="<?= site_url('seller/payouts') ?>" class="btn btn-sm btn-outline-primary w-100 mt-3 rounded-pill">Settlement ledger</a>
        </div>
    </div>
</div>

<div class="row g-4 mb-4">
    <div class="col-lg-7">
        <div class="command-panel p-4 h-100">
            <div class="d-flex justify-content-between mb-3">
                <h5 class="fw-bold mb-0">Best sellers</h5>
                <a class="small" href="<?= site_url('seller/performance') ?>">Full ranking</a>
            </div>
            <?php if (empty($topSkus)): ?>
                <p class="text-muted mb-0">No SKU velocity yet. Orders will populate this ranking.</p>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table ops-table align-middle mb-0">
                        <thead><tr><th>SKU</th><th>Units</th><th class="text-end">Revenue</th></tr></thead>
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
        <div class="command-panel p-4 h-100" id="hub-sla">
            <h5 class="fw-bold mb-1">SLA control</h5>
            <p class="small text-muted mb-3">Confirm <?= (int) ($slaConfirmHours ?? 24) ?>h · Ship <?= (int) ($slaShipHours ?? 72) ?>h</p>
            <div class="ops-health mb-3"><span style="width:<?= (int) $health ?>%"></span></div>
            <?php if (empty($slaBreaches)): ?>
                <p class="text-success mb-0 small">All open packages inside SLA.</p>
            <?php else: ?>
                <?php foreach ($slaBreaches as $item): ?>
                    <div class="d-flex justify-content-between small py-1 border-bottom">
                        <a href="<?= site_url('seller/orders/' . $item['order_id']) ?>"><?= esc($item['order_number']) ?></a>
                        <span class="text-danger"><?= esc($item['_sla'] ?? 'Overdue') ?></span>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
</div>

<div class="row g-4">
    <div class="col-lg-7" id="hub-orders">
        <div class="command-panel p-4 mb-4">
            <h5 class="fw-bold mb-1">Fulfillment desk</h5>
            <p class="small text-muted">Confirm, ship, deliver. Open 360 for the buyer money trail.</p>
            <?php if (empty($openOrders)): ?>
                <p class="text-muted mb-0">No open packages.</p>
            <?php else: ?>
                <?php foreach ($openOrders as $item): ?>
                    <?php $pkg = $item['fulfillment_status'] ?? $item['order_status']; ?>
                    <div class="pulse-row mb-3">
                        <div class="d-flex justify-content-between flex-wrap gap-2 mb-2">
                            <div>
                                <strong><?= esc($item['order_number']) ?></strong>
                                <div class="small"><?= esc($item['product_name']) ?> × <?= (int) $item['quantity'] ?></div>
                                <div class="small text-muted">
                                    <a href="<?= site_url('seller/customers/' . (int) ($item['customer_id'] ?? 0)) ?>"><?= esc($item['customer_name']) ?></a>
                                    · <?= esc($item['customer_phone'] ?? '') ?>
                                </div>
                            </div>
                            <span class="badge text-bg-light"><?= esc(ucfirst((string) $pkg)) ?></span>
                        </div>
                        <form action="<?= site_url('seller/orders/' . $item['order_id'] . '/status') ?>" method="POST" class="row g-2 align-items-end">
                            <?= csrf_field() ?>
                            <input type="hidden" name="hub" value="seller">
                            <div class="col-md-3">
                                <select name="status" class="form-select form-select-sm" required>
                                    <option value="confirmed" <?= $pkg === 'confirmed' ? 'selected' : '' ?>>Confirmed</option>
                                    <option value="shipped" <?= $pkg === 'shipped' ? 'selected' : '' ?>>Shipped</option>
                                    <option value="delivered" <?= $pkg === 'delivered' ? 'selected' : '' ?>>Delivered</option>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <input type="text" name="courier" class="form-control form-control-sm" placeholder="Courier">
                            </div>
                            <div class="col-md-3">
                                <input type="text" name="tracking_number" class="form-control form-control-sm" placeholder="Tracking">
                            </div>
                            <div class="col-md-3">
                                <button class="btn btn-solqam btn-sm w-100">Update</button>
                            </div>
                        </form>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>

        <div class="command-panel p-4" id="hub-stock">
            <h5 class="fw-bold mb-3">Inventory control</h5>
            <?php if (empty($products)): ?>
                <p class="text-muted mb-0">No products yet. <a href="<?= site_url('seller/products/create') ?>">List one</a>.</p>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table ops-table align-middle mb-0">
                        <thead><tr><th>Product</th><th>Stock / status</th></tr></thead>
                        <tbody>
                        <?php foreach ($products as $p): ?>
                            <tr>
                                <td>
                                    <div class="fw-semibold"><?= esc($p['name']) ?></div>
                                    <small class="text-muted">Rs. <?= number_format((float) $p['price'], 0) ?><?= (int) $p['stock'] <= 5 ? ' · Low stock' : '' ?></small>
                                </td>
                                <td>
                                    <form action="<?= site_url('seller/products/' . $p['id'] . '/stock') ?>" method="POST" class="d-flex gap-2 flex-wrap">
                                        <?= csrf_field() ?>
                                        <input type="hidden" name="hub" value="seller">
                                        <input type="number" name="stock" class="form-control form-control-sm" style="width:90px" min="0" value="<?= (int) $p['stock'] ?>">
                                        <select name="status" class="form-select form-select-sm" style="width:120px">
                                            <option value="active" <?= ($p['status'] ?? '') === 'active' ? 'selected' : '' ?>>Active</option>
                                            <option value="inactive" <?= ($p['status'] ?? '') === 'inactive' ? 'selected' : '' ?>>Hidden</option>
                                        </select>
                                        <button class="btn btn-sm btn-solqam-outline">Save</button>
                                        <a class="btn btn-sm btn-light border" href="<?= site_url('seller/products/edit/' . $p['id']) ?>">Edit</a>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <div class="col-lg-5">
        <div class="command-panel p-4 mb-4" id="hub-qa">
            <h5 class="fw-bold mb-3">Unanswered questions</h5>
            <?php if (empty($questions)): ?>
                <p class="text-muted small mb-0">No pending questions.</p>
            <?php else: ?>
                <?php foreach ($questions as $q): ?>
                    <div class="border-bottom pb-3 mb-3">
                        <div class="small text-muted"><?= esc($q['product_name']) ?></div>
                        <div class="fw-semibold mb-2"><?= esc($q['question']) ?></div>
                        <form action="<?= site_url('seller/questions/' . $q['id'] . '/answer') ?>" method="POST">
                            <?= csrf_field() ?>
                            <input type="hidden" name="hub" value="seller">
                            <textarea name="answer" class="form-control form-control-sm mb-2" rows="2" required></textarea>
                            <button class="btn btn-sm btn-solqam">Publish</button>
                        </form>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
        <div class="command-panel p-4 mb-4" id="hub-chat">
            <h5 class="fw-bold mb-3">Buyer chats</h5>
            <?php if (empty($chats)): ?>
                <p class="text-muted small mb-0">No conversations yet.</p>
            <?php else: ?>
                <?php foreach ($chats as $c): ?>
                    <a href="<?= site_url('seller/messages/' . $c['id']) ?>" class="d-flex justify-content-between border-bottom py-2 text-decoration-none text-dark">
                        <span><?= esc($c['customer_name'] ?? 'Buyer') ?></span>
                        <span class="small text-muted"><?= esc($c['product_name'] ?? '') ?></span>
                    </a>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
        <div class="command-panel p-4" id="hub-payouts">
            <h5 class="fw-bold mb-3">Recent payouts</h5>
            <?php if (empty($payouts)): ?>
                <p class="text-muted small mb-0">Payouts appear after paid / delivered orders.</p>
            <?php else: ?>
                <?php foreach ($payouts as $p): ?>
                    <div class="d-flex justify-content-between small py-1 border-bottom">
                        <span><?= esc($p['order_number'] ?? ('#' . $p['order_id'])) ?></span>
                        <span>Rs. <?= number_format((float) $p['amount'], 0) ?> · <?= esc($p['status']) ?></span>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
</div>
<?= $this->endSection() ?>
<?= $this->section('scripts') ?>
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
(() => {
    const grid = { color: 'rgba(148,163,184,.25)' };
    const el = document.getElementById('sellerTrend');
    if (el) {
        new Chart(el, {
            type: 'bar',
            data: {
                labels: <?= json_encode($chart['labels'] ?? []) ?>,
                datasets: [
                    { type: 'bar', label: 'Goods', data: <?= json_encode($chart['gmv'] ?? []) ?>, backgroundColor: 'rgba(11,48,230,.82)', borderRadius: 7, yAxisID: 'y' },
                    { type: 'bar', label: 'Commission', data: <?= json_encode($chart['comm'] ?? []) ?>, backgroundColor: 'rgba(240,20,47,.78)', borderRadius: 7, yAxisID: 'y' },
                    { type: 'line', label: 'Orders', data: <?= json_encode($chart['orders'] ?? []) ?>, borderColor: '#10B981', tension: .35, yAxisID: 'y1', pointRadius: 0, borderWidth: 2 }
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
    const pay = document.getElementById('sellerPay');
    if (pay) {
        new Chart(pay, {
            type: 'doughnut',
            data: {
                labels: <?= json_encode($payMix['labels'] ?? []) ?>,
                datasets: [{ data: <?= json_encode($payMix['values'] ?? []) ?>, backgroundColor: ['#0B30E6','#F0142F','#F59E0B','#10B981'], borderWidth: 0 }]
            },
            options: { plugins: { legend: { position: 'bottom' } }, cutout: '64%' }
        });
    }
})();
</script>
<?= $this->endSection() ?>
