<?php
$accounts = $accounts ?? [];
$hasPayTo = ! empty($hasPayTo);
$embed = ! empty($embed);
?>
<div class="<?= $embed ? 'mt-3' : 'card border-0 shadow-sm rounded-4 p-4 mb-4' ?>">
    <h5 class="fw-bold mb-2">Send money here</h5>
    <?php if (! $hasPayTo): ?>
        <p class="text-muted mb-0">Solqam has not published JazzCash / bank details yet. Ask admin (SLA &amp; limits → collection accounts).</p>
    <?php else: ?>
        <div class="row g-3">
            <?php if (($accounts['jazzcash_number'] ?? '') !== ''): ?>
                <div class="col-md-4">
                    <div class="border rounded-4 p-3 h-100 bg-light">
                        <div class="small text-muted">JazzCash</div>
                        <div class="fw-bold"><?= esc($accounts['jazzcash_name'] ?: 'Solqam') ?></div>
                        <div class="fs-5 font-monospace js-copy"><?= esc($accounts['jazzcash_number']) ?></div>
                    </div>
                </div>
            <?php endif; ?>
            <?php if (($accounts['easypaisa_number'] ?? '') !== ''): ?>
                <div class="col-md-4">
                    <div class="border rounded-4 p-3 h-100 bg-light">
                        <div class="small text-muted">EasyPaisa</div>
                        <div class="fw-bold"><?= esc($accounts['easypaisa_name'] ?: 'Solqam') ?></div>
                        <div class="fs-5 font-monospace js-copy"><?= esc($accounts['easypaisa_number']) ?></div>
                    </div>
                </div>
            <?php endif; ?>
            <?php if (($accounts['bank_account'] ?? '') !== ''): ?>
                <div class="col-md-4">
                    <div class="border rounded-4 p-3 h-100 bg-light">
                        <div class="small text-muted"><?= esc($accounts['bank_name'] ?: 'Bank') ?></div>
                        <div class="fw-bold"><?= esc($accounts['bank_title'] ?: 'Solqam') ?></div>
                        <div class="fs-6 font-monospace js-copy"><?= esc($accounts['bank_account']) ?></div>
                    </div>
                </div>
            <?php endif; ?>
        </div>
        <p class="small text-muted mt-3 mb-0"><?= esc($collectionHint ?? 'Send the unpaid total in one transfer, or pay a partial amount. One screenshot can cover this payment.') ?></p>
    <?php endif; ?>
</div>
