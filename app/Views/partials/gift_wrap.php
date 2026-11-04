<?php
$giftOn = ! empty($giftWrapEnabled);
if ($giftOn && function_exists('gift_wrap_enabled')) {
    $giftOn = gift_wrap_enabled();
} elseif ($giftOn && class_exists(\App\Services\Checkout\GiftWrapService::class)) {
    try {
        $giftOn = \App\Services\Checkout\GiftWrapService::enabled();
    } catch (\Throwable $e) {
        $giftOn = false;
    }
}
if (! $giftOn) {
    return;
}
$fee = isset($giftWrapFee) ? (float) $giftWrapFee : (function_exists('gift_wrap_fee') ? gift_wrap_fee() : 150.0);
$prefix = $inputPrefix ?? '';
$wrapName = $prefix !== '' ? $prefix . '[wrap]' : 'gift_wrap';
$msgName = $prefix !== '' ? $prefix . '[message]' : 'gift_message';
$hideName = $prefix !== '' ? $prefix . '[hide]' : 'gift_hide_price';
$checked = ! empty($giftChecked);
$hideChecked = ! empty($giftHideChecked);
$msgVal = (string) ($giftMessage ?? '');
$itemId = (int) ($giftItemId ?? 0);
$compact = ! empty($giftCompact);
?>
<div class="gift-box <?= $compact ? 'gift-box-compact' : '' ?> <?= $checked ? 'is-on' : '' ?>" data-gift-box>
    <?php if ($itemId > 0): ?>
        <input type="hidden" name="item_id" value="<?= $itemId ?>">
    <?php endif; ?>
    <label class="gift-box-toggle">
        <input type="checkbox" name="<?= esc($wrapName) ?>" value="1" class="form-check-input gift-wrap-check" <?= $checked ? 'checked' : '' ?> onchange="this.closest('[data-gift-box]').classList.toggle('is-on', this.checked); if (typeof window.solqamGiftRecalc === 'function') window.solqamGiftRecalc();">
        <span class="gift-box-icon"><i class="bi bi-gift-fill"></i></span>
        <span>
            <strong>Gift wrap this item</strong>
            <span class="d-block small text-muted">Premium wrap<?= $fee > 0 ? ' · Rs. ' . number_format($fee, 0) . ' per item' : ' · free' ?></span>
        </span>
    </label>
    <div class="gift-box-extra">
        <label class="small fw-semibold mb-1">Gift message (optional)</label>
        <textarea name="<?= esc($msgName) ?>" class="form-control form-control-sm" rows="2" maxlength="400" placeholder="Happy birthday — with love"><?= esc($msgVal) ?></textarea>
        <?php $hideId = 'giftHide_' . ($itemId > 0 ? (string) $itemId : ('p' . substr(md5($wrapName . $msgVal), 0, 8))); ?>
        <div class="form-check mt-2">
            <input class="form-check-input" type="checkbox" name="<?= esc($hideName) ?>" value="1" id="<?= esc($hideId) ?>" <?= $hideChecked ? 'checked' : '' ?>>
            <label class="form-check-label small" for="<?= esc($hideId) ?>">Hide prices on the packing slip</label>
        </div>
    </div>
</div>
