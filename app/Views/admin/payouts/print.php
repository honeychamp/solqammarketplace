<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title><?= esc($title ?? 'Payout voucher') ?></title>
    <?= view('partials/favicon') ?>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>@media print { .no-print { display: none !important; } }</style>
</head>
<body class="p-5">
    <div class="d-flex justify-content-between mb-4">
        <div>
            <h3 class="fw-bold mb-0">Solqam payout voucher</h3>
            <div class="text-muted">#<?= (int) $payout['id'] ?></div>
        </div>
        <button class="btn btn-outline-dark no-print" onclick="window.print()">Print / Save PDF</button>
    </div>
    <p><strong>Store:</strong> <?= esc($payout['store_name'] ?? $payout['seller_name']) ?><br>
        <strong>Order:</strong> <?= esc($payout['order_number'] ?? '-') ?><br>
        <strong>Amount:</strong> Rs. <?= number_format((float) $payout['amount'], 2) ?><br>
        <strong>Status:</strong> <?= esc($payout['status']) ?></p>
    <p class="small text-muted mt-5">Official settlement record. Keep for accounts.</p>
</body>
</html>
