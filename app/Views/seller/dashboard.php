<?= $this->extend('layouts/seller') ?>
<?= $this->section('content') ?>
<?php
$openOrders = $openOrders ?? [];
$questions = $questions ?? [];
$products = $products ?? [];
$payouts = $payouts ?? [];
$chats = $chats ?? [];
$chart = $chart ?? ['labels' => [], 'gmv' => [], 'comm' => []];
$hour = (int) date('G');
$greet = $hour < 12 ? 'Good morning' : ($hour < 17 ? 'Good afternoon' : 'Good evening');
?>
<div class="command-hero seller mb-4">
    <div class="d-flex flex-wrap justify-content-between align-items-end gap-3">
        <div>
            <div class="command-kicker">Seller Hub · <?= esc(session()->get('user.store_name') ?? 'Your store') ?></div>
            <h2 class="fw-black mb-1 text-white"><?= $greet ?></h2>
            <p class="mb-0 text-white-50">Fulfill, restock, and see every buyer’s money trail in one place.</p>
        </div>
        <div class="d-flex flex-wrap gap-2">
            <a href="<?= site_url('seller/products/create') ?>" class="btn btn-light rounded-pill">Add product</a>
            <a href="<?= site_url('seller/customers') ?>" class="btn btn-outline-light rounded-pill">Buyer 360</a>
            <a href="#hub-orders" class="btn btn-outline-light rounded-pill">Fulfill</a>
        </div>
    </div>
</div>

<div class="row g-3 mb-4">
    <div class="col-6 col-xl-2">
        <div class="kpi-tile h-100"><span>Gross</span><strong>Rs. <?= number_format($totalSales, 0) ?></strong></div>
    </div>
    <div class="col-6 col-xl-2">
        <div class="kpi-tile accent h-100"><span>Commission</span><strong>Rs. <?= number_format($totalCommission ?? 0, 0) ?></strong></div>
    </div>
    <div class="col-6 col-xl-2">
        <div class="kpi-tile emerald h-100"><span>Cashback</span><strong>Rs. <?= number_format($totalCashback ?? 0, 0) ?></strong></div>
    </div>
    <div class="col-6 col-xl-2">
        <div class="kpi-tile gold h-100"><span>Net to you</span><strong>Rs. <?= number_format($netEarnings ?? 0, 0) ?></strong></div>
    </div>
    <div class="col-6 col-xl-2">
        <div class="kpi-tile h-100"><span>Payout wallet</span><strong>Rs. <?= number_format($sellerWallet ?? 0, 0) ?></strong></div>
    </div>
    <div class="col-6 col-xl-2">
        <div class="kpi-tile h-100"><span>Orders</span><strong><?= (int) $totalOrders ?></strong><em><?= count($openOrders) ?> open · <?= (int) ($buyerCount ?? 0) ?> buyers</em></div>
    </div>
</div>

<div class="row g-4 mb-4">
    <div class="col-lg-8">
        <div class="command-panel p-4 h-100">
            <h5 class="fw-bold mb-1">7-day sales</h5>
            <small class="text-muted">Your goods vs commission cut</small>
            <div class="chart-shell mt-3"><canvas id="sellerTrend" height="120"></canvas></div>
        </div>
    </div>
    <div class="col-lg-4">
        <div class="command-panel p-4 h-100">
            <h5 class="fw-bold mb-3">Money</h5>
            <div class="d-flex justify-content-between py-2 border-bottom"><span class="text-muted">Pending payout</span><strong>Rs. <?= number_format($pendingPayout ?? 0, 0) ?></strong></div>
            <div class="d-flex justify-content-between py-2 border-bottom"><span class="text-muted">Paid out</span><strong>Rs. <?= number_format($paidPayout ?? 0, 0) ?></strong></div>
            <div class="d-flex justify-content-between py-2"><span class="text-muted">Catalog</span><strong><?= (int) $totalProducts ?></strong></div>
            <a href="<?= site_url('seller/payouts') ?>" class="btn btn-sm btn-outline-primary w-100 mt-3 rounded-pill">Payout history</a>
        </div>
    </div>
</div>

<div class="row g-4">
    <div class="col-lg-7" id="hub-orders">
        <div class="command-panel p-4 mb-4">
            <h5 class="fw-bold mb-1">Handle orders</h5>
            <p class="small text-muted">Confirm, ship, deliver. Open 360 for full buyer money detail.</p>
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
            <h5 class="fw-bold mb-3">Catalog &amp; stock</h5>
            <?php if (empty($products)): ?>
                <p class="text-muted mb-0">No products yet. <a href="<?= site_url('seller/products/create') ?>">List one</a>.</p>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table solqam-table align-middle mb-0">
                        <thead><tr><th>Product</th><th>Stock / status</th></tr></thead>
                        <tbody>
                        <?php foreach ($products as $p): ?>
                            <tr>
                                <td>
                                    <div class="fw-semibold"><?= esc($p['name']) ?></div>
                                    <small class="text-muted">Rs. <?= number_format((float) $p['price'], 0) ?></small>
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
    const el = document.getElementById('sellerTrend');
    if (!el || typeof Chart === 'undefined') return;
    new Chart(el, {
        type: 'bar',
        data: {
            labels: <?= json_encode($chart['labels'] ?? []) ?>,
            datasets: [
                { label: 'Goods', data: <?= json_encode($chart['gmv'] ?? []) ?>, backgroundColor: 'rgba(11,48,230,.75)', borderRadius: 8 },
                { label: 'Commission', data: <?= json_encode($chart['comm'] ?? []) ?>, backgroundColor: 'rgba(240,20,47,.75)', borderRadius: 8 }
            ]
        },
        options: { plugins: { legend: { display: true } }, scales: { y: { beginAtZero: true } } }
    });
})();
</script>
<?= $this->endSection() ?>
