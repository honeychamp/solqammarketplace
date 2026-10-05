<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title><?= esc($title ?? 'Payout statement') ?></title>
    <?= view('partials/favicon') ?>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>@media print { .no-print { display: none !important; } }</style>
</head>
<body class="p-4">
    <div class="d-flex justify-content-between mb-3">
        <div>
            <h4 class="fw-bold mb-0">Seller payout statement</h4>
            <div class="small text-muted"><?= esc($seller['name'] ?? '') ?> · <?= date('d M Y') ?></div>
        </div>
        <button class="btn btn-sm btn-outline-dark no-print" onclick="window.print()">Print / PDF</button>
    </div>
    <table class="table table-sm">
        <thead><tr><th>Order</th><th>Amount</th><th>Status</th><th>Date</th></tr></thead>
        <tbody>
        <?php foreach ($payouts as $p): ?>
            <tr>
                <td><?= esc($p['order_number'] ?? '-') ?></td>
                <td>Rs. <?= number_format((float) $p['amount'], 2) ?></td>
                <td><?= esc($p['status']) ?></td>
                <td><?= esc($p['paid_at'] ?? $p['created_at'] ?? '') ?></td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</body>
</html>
