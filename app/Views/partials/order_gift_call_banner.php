<?php
$items = $items ?? [];
$canEditGift = ! empty($canEditGift);
$fee = function_exists('gift_wrap_fee') ? gift_wrap_fee() : 150.0;
$giftItems = [];
$plainItems = [];
foreach ($items as $it) {
    if (! empty($it['gift_wrap'])) {
        $giftItems[] = $it;
    } else {
        $plainItems[] = $it;
    }
}
if ($giftItems === [] && ! $canEditGift) {
    return;
}
$feeSum = 0.0;
foreach ($giftItems as $it) {
    $feeSum += (float) ($it['gift_wrap_fee'] ?? 0);
}
?>
<?php if ($giftItems !== []): ?>
<div class="alert alert-danger-subtle border border-danger-subtle rounded-4 mb-3">
    <div class="fw-bold text-danger mb-1"><i class="bi bi-gift-fill me-1"></i>Gift wrap — customer asked at order time</div>
    <p class="small mb-1">
        <?= count($giftItems) === 1 ? '1 item' : count($giftItems) . ' items' ?>
        came with gift wrap<?= $feeSum > 0 ? ' · Rs. ' . number_format($feeSum, 0) : '' ?>.
        Pack it unless they change their mind on the call.
    </p>
    <?php if ($canEditGift): ?>
        <p class="small mb-0">Call confirm: if they say <strong>do not wrap</strong>, uncheck Keep gift wrap and tap Save. The fee is removed from the payable.</p>
    <?php else: ?>
        <p class="small mb-0">Solqam desk confirms gift wrap on the call.</p>
    <?php endif; ?>
</div>
<?php endif; ?>
<?php if ($canEditGift && $plainItems !== []): ?>
<div class="alert alert-info border-0 rounded-4 mb-4">
    <div class="fw-bold mb-1"><i class="bi bi-gift me-1"></i><?= $giftItems === [] ? 'No gift wrap at checkout' : 'Other items have no gift wrap' ?></div>
    <p class="small mb-0">
        Customer did not add wrap<?= $giftItems === [] ? ' on this order' : ' on these items' ?> when they ordered.
        If they ask on the call, tick <strong>Add gift wrap</strong> (Rs. <?= number_format((float) $fee, 0) ?> each) and tap Save. The fee is added to the payable.
    </p>
</div>
<?php endif; ?>
