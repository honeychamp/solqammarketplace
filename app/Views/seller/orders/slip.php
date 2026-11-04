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
    <?php
        $hidePrices = false;
        foreach ($sellerItems as $it) {
            if (! empty($it['gift_wrap']) && ! empty($it['gift_hide_price'])) {
                $hidePrices = true;
                break;
            }
        }
        if (! empty($order['gift_hide_price'])) {
            $hidePrices = true;
        }
    ?>
    <div class="d-flex justify-content-between align-items-start mb-4">
        <div>
            <h4 class="fw-bold mb-0">Solqam packing slip</h4>
            <div class="text-muted">Order #<?= esc($order['order_number']) ?></div>
            <div class="small">Received <?= esc(pk_datetime($order['created_at'])) ?> PKT</div>
        </div>
        <button class="btn btn-sm btn-outline-dark no-print" onclick="window.print()">Print / PDF</button>
    </div>
    <div class="row g-3 mb-4">
        <div class="col-md-6">
            <div class="small text-muted">Ship to</div>
            <strong><?= esc($order['recipient_name'] ?? $order['customer_name'] ?? '') ?></strong><br>
            <?= esc($order['street_address'] ?? '') ?><br>
            <?php if (! empty($order['area'])): ?>Area: <?= esc($order['area']) ?><br><?php endif; ?>
            <?php if (! empty($order['landmark'])): ?>Landmark: <?= esc($order['landmark']) ?><br><?php endif; ?>
            <?= esc($order['city'] ?? '') ?><?= ! empty($order['province']) ? ', ' . esc($order['province']) : '' ?> <?= esc($order['postal_code'] ?? '') ?><br>
            Phone: <?= esc($order['recipient_phone'] ?? $order['customer_phone'] ?? '') ?>
            <?php if (! empty($order['alternate_phone'])): ?><br>Alt: <?= esc($order['alternate_phone']) ?><?php endif; ?>
        </div>
        <div class="col-md-6">
            <div class="small text-muted">Payment</div>
            <?= esc(payment_method_label($order['payment_method'] ?? 'cod')) ?>
            · <?= esc(strtoupper((string) ($order['payment_status'] ?? 'pending'))) ?>
            <?php if (! $hidePrices): ?>
                <div>Collect / paid: Rs. <?= number_format((float) ($order['final_payable'] ?? 0), 0) ?></div>
            <?php endif; ?>
        </div>
    </div>
    <?php if (! empty($order['notes'])): ?>
        <p class="small"><strong>Buyer note:</strong> <?= esc($order['notes']) ?></p>
    <?php endif; ?>
    <table class="table table-sm">
        <thead><tr><th>Item</th><th>Qty</th><?php if (! $hidePrices): ?><th>Line</th><?php endif; ?></tr></thead>
        <tbody>
        <?php foreach ($sellerItems as $it): ?>
            <tr>
                <td>
                    <?= esc($it['product_name']) ?>
                    <?php if (! empty($it['variant_label'])): ?>
                        <div class="small"><?= esc($it['variant_label']) ?></div>
                    <?php endif; ?>
                    <?php if (! empty($it['gift_wrap'])): ?>
                        <div class="fw-bold text-danger">GIFT WRAP</div>
                        <?php if (! empty($it['gift_message'])): ?>
                            <div class="small">Card: <?= esc($it['gift_message']) ?></div>
                        <?php endif; ?>
                    <?php endif; ?>
                </td>
                <td><?= (int) $it['quantity'] ?></td>
                <?php if (! $hidePrices): ?>
                    <td>Rs. <?= number_format((float) $it['subtotal'] + (float) ($it['gift_wrap_fee'] ?? 0), 0) ?></td>
                <?php endif; ?>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</body>
</html>
