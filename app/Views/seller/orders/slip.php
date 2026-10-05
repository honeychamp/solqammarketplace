<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title><?= esc($title ?? 'Packing slip') ?></title>
    <?= view('partials/favicon') ?>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>@media print { .no-print { display: none !important; } }</style>
</head>
<body class="p-4">
    <div class="d-flex justify-content-between align-items-start mb-4">
        <div>
            <h4 class="fw-bold mb-0">Solqam packing slip</h4>
            <div class="text-muted">Order #<?= esc($order['order_number']) ?></div>
        </div>
        <button class="btn btn-sm btn-outline-dark no-print" onclick="window.print()">Print / PDF</button>
    </div>
    <p class="small">Ship to: <?= esc($order['recipient_name'] ?? $order['customer_name'] ?? '') ?><br>
        <?= esc($order['street_address'] ?? '') ?> <?= esc($order['city'] ?? '') ?></p>
    <table class="table table-sm">
        <thead><tr><th>Item</th><th>Qty</th></tr></thead>
        <tbody>
        <?php foreach ($sellerItems as $it): ?>
            <tr>
                <td><?= esc($it['product_name']) ?></td>
                <td><?= (int) $it['quantity'] ?></td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</body>
</html>
