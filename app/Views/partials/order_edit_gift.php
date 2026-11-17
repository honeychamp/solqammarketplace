<?php
$gid     = (int) ($item['id'] ?? 0);
$on      = ! empty($item['gift_wrap']);
$fee     = function_exists('gift_wrap_fee') ? gift_wrap_fee() : 150.0;
$hideId  = 'giftHide' . $gid;
$wrapId  = 'giftWrap' . $gid;
$boxCls  = $on ? 'border-danger bg-danger-subtle' : 'bg-light';
?>
<div class="border rounded-3 p-2 mt-2 <?= $boxCls ?>" data-gift-edit>
    <?php if ($on): ?>
        <div class="small fw-bold text-danger mb-1"><i class="bi bi-gift-fill me-1"></i>Customer asked for gift wrap</div>
    <?php endif; ?>
    <div class="form-check">
        <input class="form-check-input" type="checkbox" name="gift_wrap[<?= $gid ?>]" value="1" id="<?= esc($wrapId) ?>" <?= $on ? 'checked' : '' ?> onchange="var b=this.closest('[data-gift-edit]'); if(b){ var x=b.querySelector('[data-gift-extra]'); if(x) x.hidden=!this.checked; b.classList.toggle('border-danger', this.checked); b.classList.toggle('bg-danger-subtle', this.checked); b.classList.toggle('bg-light', !this.checked); }">
        <label class="form-check-label small" for="<?= esc($wrapId) ?>">
            <?php if ($on): ?>
                Keep gift wrap
                <span class="text-muted">· Rs. <?= number_format((float) $fee, 0) ?> each — uncheck if they said no on the call</span>
            <?php else: ?>
                Add gift wrap
                <span class="text-muted">· Rs. <?= number_format((float) $fee, 0) ?> each — only if they asked on the call</span>
            <?php endif; ?>
        </label>
    </div>
    <div class="ps-4 mt-1" data-gift-extra <?= $on ? '' : 'hidden' ?>>
        <input class="form-control form-control-sm mb-1" name="gift_message[<?= $gid ?>]" maxlength="400" placeholder="Gift message (optional)" value="<?= esc($item['gift_message'] ?? '') ?>">
        <div class="form-check">
            <input class="form-check-input" type="checkbox" name="gift_hide[<?= $gid ?>]" value="1" id="<?= esc($hideId) ?>" <?= ! empty($item['gift_hide_price']) ? 'checked' : '' ?>>
            <label class="form-check-label small" for="<?= esc($hideId) ?>">Hide prices on packing slip</label>
        </div>
    </div>
</div>
