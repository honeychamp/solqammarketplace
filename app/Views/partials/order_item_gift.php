<?php if (! empty($item['gift_wrap'])): ?>
    <div class="small text-danger mt-1"><i class="bi bi-gift-fill me-1"></i> Gift wrap<?= (float) ($item['gift_wrap_fee'] ?? 0) > 0 ? ' · Rs. ' . number_format((float) $item['gift_wrap_fee'], 0) : '' ?></div>
    <?php if (! empty($item['gift_message'])): ?>
        <div class="small fw-normal text-muted">Gift message: <?= esc($item['gift_message']) ?></div>
    <?php endif; ?>
    <?php if (! empty($item['gift_hide_price'])): ?>
        <div class="small fw-normal text-warning">Hide prices on packing slip</div>
    <?php endif; ?>
<?php endif; ?>
