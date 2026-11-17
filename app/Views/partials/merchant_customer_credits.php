<?php
$rows = $customers ?? [];
$showPayHint = ! empty($showPayHint);
$compact = ! empty($compact);
?>
<div class="table-responsive">
    <table class="table <?= $compact ? 'table-sm' : '' ?> align-middle mb-0">
        <thead>
            <tr>
                <th>Customer</th>
                <th>Card</th>
                <th>Visits</th>
                <th>Credits given</th>
                <th>Unpaid</th>
                <th>With Solqam</th>
                <th>Released</th>
            </tr>
        </thead>
        <tbody>
        <?php foreach ($rows as $c): ?>
            <tr>
                <td>
                    <?= esc(($c['customer_name'] ?? '') !== '' ? $c['customer_name'] : 'Customer') ?>
                    <?php if (! empty($c['customer_phone'])): ?>
                        <div class="small text-muted"><?= esc($c['customer_phone']) ?></div>
                    <?php endif; ?>
                </td>
                <td class="font-monospace small"><?= esc(merchant_scan_pan($c)) ?></td>
                <td><?= (int) ($c['visits'] ?? 0) ?></td>
                <td class="fw-bold">Rs. <?= number_format((float) ($c['credits'] ?? 0), 0) ?></td>
                <td class="<?= (float) ($c['pending'] ?? 0) > 0 ? 'text-danger fw-bold' : 'text-muted' ?>">Rs. <?= number_format((float) ($c['pending'] ?? 0), 0) ?></td>
                <td class="<?= (float) ($c['processing'] ?? 0) > 0 ? 'text-warning fw-semibold' : 'text-muted' ?>">Rs. <?= number_format((float) ($c['processing'] ?? 0), 0) ?></td>
                <td class="text-success">Rs. <?= number_format((float) ($c['released'] ?? 0), 0) ?></td>
            </tr>
        <?php endforeach; ?>
        <?php if ($rows === []): ?>
            <tr><td colspan="7" class="text-muted">No customer credits yet.</td></tr>
        <?php else: ?>
            <tr class="table-light fw-bold">
                <td colspan="3">Total to send Solqam (all customers)</td>
                <td>Rs. <?= number_format(array_sum(array_map(static fn ($r) => (float) ($r['credits'] ?? 0), $rows)), 0) ?></td>
                <td class="text-danger">Rs. <?= number_format(array_sum(array_map(static fn ($r) => (float) ($r['pending'] ?? 0), $rows)), 0) ?></td>
                <td>Rs. <?= number_format(array_sum(array_map(static fn ($r) => (float) ($r['processing'] ?? 0), $rows)), 0) ?></td>
                <td>Rs. <?= number_format(array_sum(array_map(static fn ($r) => (float) ($r['released'] ?? 0), $rows)), 0) ?></td>
            </tr>
        <?php endif; ?>
        </tbody>
    </table>
</div>
<?php if ($showPayHint && array_sum(array_map(static fn ($r) => (float) ($r['pending'] ?? 0), $rows)) > 0): ?>
    <p class="small text-muted mt-2 mb-0">Add the unpaid column once, send that total to Solqam, and upload one screenshot. Admin sees the same customer list.</p>
<?php endif; ?>
