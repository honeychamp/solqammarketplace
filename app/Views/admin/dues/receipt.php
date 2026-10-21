<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title><?= esc($title ?? 'COD due receipt') ?></title>
    <?= view('partials/favicon') ?>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>@media print { .no-print { display: none !important; } }</style>
</head>
<body class="p-4">
<?php
$due = $due ?? [];
$remain = (float) ($remain ?? 0);
?>
    <div class="d-flex justify-content-between mb-3">
        <div>
            <h4 class="fw-bold mb-0">Solqam — COD due receipt</h4>
            <div class="small text-muted"><?= esc($profile['store_name'] ?? 'Seller') ?> · <?= esc($seller['name'] ?? '') ?> · <?= date('d M Y H:i') ?></div>
        </div>
        <button class="btn btn-sm btn-outline-dark no-print" onclick="window.print()">Print / PDF</button>
    </div>
    <table class="table table-sm">
        <tr><th>Order</th><td><?= esc($due['order_number'] ?? ('#' . ($due['order_id'] ?? ''))) ?></td></tr>
        <tr><th>Cash seller collected</th><td>Rs. <?= number_format((float) ($due['collected_cash'] ?? 0), 2) ?></td></tr>
        <tr><th>Commission</th><td>Rs. <?= number_format((float) ($due['commission_amount'] ?? 0), 2) ?></td></tr>
        <tr><th>Buyer cashback</th><td>Rs. <?= number_format((float) ($due['cashback_amount'] ?? 0), 2) ?></td></tr>
        <tr><th>Due to Solqam</th><td>Rs. <?= number_format((float) ($due['due_amount'] ?? 0), 2) ?></td></tr>
        <tr><th>Received so far</th><td>Rs. <?= number_format((float) ($due['paid_amount'] ?? 0), 2) ?></td></tr>
        <tr><th>Still unpaid</th><td>Rs. <?= number_format($remain, 2) ?></td></tr>
        <tr><th>Status</th><td><?= esc($due['status'] ?? '') ?></td></tr>
        <tr><th>Note</th><td><?= esc($due['paid_note'] ?? '') ?></td></tr>
    </table>
    <p class="small text-muted mb-0">Seller keeps goods cash. This receipt is only for commission + cashback owed to Solqam.</p>
</body>
</html>
