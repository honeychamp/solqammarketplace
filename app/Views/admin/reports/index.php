<?= $this->extend('layouts/admin') ?>

<?= $this->section('content') ?>
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h4 class="fw-bold mb-1">Executive Reporting &amp; Market Analytics</h4>
        <p class="text-secondary small mb-0">Live aggregated metrics computed on the fly without redundant stored counters</p>
    </div>
</div>

<!-- Aggregated KPI Row -->
<div class="row g-3 mb-4">
    <div class="col-sm-6 col-lg-3">
        <div class="card-custom p-3">
            <div class="small text-secondary fw-semibold">Total Gross Merchandise (GMV)</div>
            <h3 class="fw-bold text-dark mt-1">Rs. <?= number_format($totals['total_gmv'] ?? 0, 2) ?></h3>
            <small class="text-success"><i class="bi bi-graph-up me-1"></i> Across All Orders</small>
        </div>
    </div>
    <div class="col-sm-6 col-lg-3">
        <div class="card-custom p-3">
            <div class="small text-secondary fw-semibold">Platform Commission Earned</div>
            <h3 class="fw-bold text-success mt-1">Rs. <?= number_format($totals['total_commission'] ?? 0, 2) ?></h3>
            <small class="text-muted">Net marketplace revenue</small>
        </div>
    </div>
    <div class="col-sm-6 col-lg-3">
        <div class="card-custom p-3">
            <div class="small text-secondary fw-semibold">Wallet Balance Utilized</div>
            <h3 class="fw-bold text-warning mt-1">Rs. <?= number_format($totals['total_wallet_used'] ?? 0, 2) ?></h3>
            <small class="text-muted">Spent from ledger balances</small>
        </div>
    </div>
    <div class="col-sm-6 col-lg-3">
        <div class="card-custom p-3">
            <div class="small text-secondary fw-semibold">Total Orders Placed</div>
            <h3 class="fw-bold text-primary mt-1"><?= $totals['total_orders'] ?? 0 ?></h3>
            <small class="text-muted">All active lifecycle stages</small>
        </div>
    </div>
</div>

<!-- Charts Row -->
<div class="row g-4 mb-4">
    <!-- Chart 1: Sales Over Time (Line Chart) -->
    <div class="col-lg-8">
        <div class="card-custom p-4 h-100">
            <h5 class="fw-bold mb-3 border-bottom pb-2">Sales Revenue Over Time (PKR)</h5>
            <canvas id="salesTimelineChart" style="max-height: 280px;"></canvas>
        </div>
    </div>

    <!-- Chart 2: Orders by Status (Doughnut Chart) -->
    <div class="col-lg-4">
        <div class="card-custom p-4 h-100">
            <h5 class="fw-bold mb-3 border-bottom pb-2">Orders by Status</h5>
            <canvas id="statusDoughnutChart" style="max-height: 280px;"></canvas>
        </div>
    </div>
</div>

<!-- Top Sellers Table -->
<div class="card-custom p-4">
    <h5 class="fw-bold mb-3 border-bottom pb-2">Top Performing Merchants by Volume</h5>
    <?php if (empty($topSellers)): ?>
        <p class="text-secondary small py-4 text-center">No seller transactions recorded yet.</p>
    <?php else: ?>
        <div class="table-responsive">
            <table class="table align-middle">
                <thead class="table-light small">
                    <tr>
                        <th>Store Name</th>
                        <th>Owner</th>
                        <th>Units Sold</th>
                        <th>Gross Revenue</th>
                        <th>Platform Commission</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($topSellers as $s): ?>
                        <tr>
                            <td><strong class="text-dark"><?= esc($s['store_name'] ?? 'Vendor') ?></strong></td>
                            <td><?= esc($s['seller_name']) ?></td>
                            <td><span class="badge bg-primary rounded-pill px-3 py-1"><?= $s['units_sold'] ?> Units</span></td>
                            <td class="fw-bold text-dark fs-6">Rs. <?= number_format($s['total_revenue'], 2) ?></td>
                            <td class="fw-bold text-success">Rs. <?= number_format($s['platform_commission'], 2) ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>
<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<script>
    // Prepare Data from PHP
    const timelineData = <?= json_encode($salesTimeline) ?>;
    const statusData = <?= json_encode($statusAgg) ?>;

    // 1. Sales Line Chart
    const dates = timelineData.map(d => d.order_date);
    const gmv = timelineData.map(d => parseFloat(d.daily_gmv));
    const commission = timelineData.map(d => parseFloat(d.daily_commission));

    const ctxSales = document.getElementById('salesTimelineChart').getContext('2d');
    new Chart(ctxSales, {
        type: 'line',
        data: {
            labels: dates.length > 0 ? dates : ['Today'],
            datasets: [
                {
                    label: 'Gross GMV (Rs.)',
                    data: gmv.length > 0 ? gmv : [0],
                    borderColor: '#198754',
                    backgroundColor: 'rgba(25, 135, 84, 0.1)',
                    fill: true,
                    tension: 0.3
                },
                {
                    label: 'Platform Commission (Rs.)',
                    data: commission.length > 0 ? commission : [0],
                    borderColor: '#0d6efd',
                    backgroundColor: 'transparent',
                    borderDash: [5, 5],
                    tension: 0.3
                }
            ]
        },
        options: {
            responsive: true,
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: {
                        callback: function(value) { return 'Rs. ' + value.toLocaleString(); }
                    }
                }
            }
        }
    });

    // 2. Status Doughnut Chart
    const statusLabels = statusData.map(s => s.status.toUpperCase());
    const statusCounts = statusData.map(s => parseInt(s.count));

    const ctxStatus = document.getElementById('statusDoughnutChart').getContext('2d');
    new Chart(ctxStatus, {
        type: 'doughnut',
        data: {
            labels: statusLabels.length > 0 ? statusLabels : ['No Orders'],
            datasets: [{
                data: statusCounts.length > 0 ? statusCounts : [1],
                backgroundColor: ['#ffc107', '#0dcaf0', '#0d6efd', '#198754', '#6c757d', '#dc3545']
            }]
        },
        options: {
            responsive: true,
            plugins: {
                legend: { position: 'bottom' }
            }
        }
    });
</script>
<?= $this->endSection() ?>
