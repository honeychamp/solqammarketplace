<!DOCTYPE html>
<html><head><meta charset="utf-8"><title>Invoice <?= esc($order['order_number']) ?></title>
<?= view('partials/favicon') ?>
<style>body{font-family:sans-serif;padding:24px;} table{width:100%;border-collapse:collapse;} td,th{border:1px solid #ddd;padding:8px;text-align:left;} @media print{.no-print{display:none}}</style>
</head><body>
<button class="no-print" onclick="window.print()">Print / PDF</button>
<h2>Solqam Invoice</h2>
<p>Order <?= esc($order['order_number']) ?> · <?= esc(pk_datetime($order['created_at'] ?? '')) ?> PKT</p>
<p><?= esc($order['recipient_name'] ?? '') ?><br><?= esc($order['street_address'] ?? '') ?>, <?= esc($order['city'] ?? '') ?></p>
<table>
<tr><th>Item</th><th>Qty</th><th>Price</th><th>Subtotal</th></tr>
<?php foreach ($order['items'] ?? [] as $item): ?>
<tr>
    <td>
        <?= esc($item['product_name']) ?>
        <?php if (! empty($item['gift_wrap'])): ?>
            <div style="font-size:12px;">Gift wrap<?= (float) ($item['gift_wrap_fee'] ?? 0) > 0 ? ' (+ Rs. ' . number_format((float) $item['gift_wrap_fee'], 0) . ')' : '' ?></div>
            <?php if (! empty($item['gift_message'])): ?>
                <div style="font-size:12px;">Message: <?= esc($item['gift_message']) ?></div>
            <?php endif; ?>
        <?php endif; ?>
    </td>
    <td><?= esc($item['quantity']) ?></td>
    <td><?= number_format($item['price'], 0) ?></td>
    <td><?= number_format((float) $item['subtotal'] + (float) ($item['gift_wrap_fee'] ?? 0), 0) ?></td>
</tr>
<?php endforeach; ?>
</table>
<p>Goods: Rs. <?= number_format($order['total_amount'] ?? 0, 0) ?><?php if ((float) ($order['gift_wrap_amount'] ?? 0) > 0): ?> · Gift wrap: Rs. <?= number_format((float) $order['gift_wrap_amount'], 0) ?><?php endif; ?> · Payable: Rs. <?= number_format($order['final_payable'] ?? 0, 0) ?></p>
</body></html>
