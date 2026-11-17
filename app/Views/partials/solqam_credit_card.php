<?php
$card = $card ?? [];
$interactive = ! empty($interactive);
$showBreak = $showBreak ?? true;
$tier = (string) ($card['tier'] ?? 'blue');
$tierClass = $tier === 'platinum' ? 'is-platinum' : ($tier === 'gold' ? 'is-gold' : '');
$holder = strtoupper((string) ($card['holder_name'] ?? $holder ?? 'SOLQAM MEMBER'));
$parts = $card['id_parts'] ?? \App\Services\Card\SolqamCardNumber::decode((string) ($card['pan'] ?? $card['pan_grouped'] ?? ''));
$panShow = (string) ($parts['pan'] ?? $card['pan_grouped'] ?? $card['pan'] ?? '01-01-C-00000002');
$exp = (string) ($card['expires_fmt'] ?? '—');
$hideCvv = ! empty($hideCvv);
$cvv = $hideCvv ? '•••' : (string) ($card['cvv'] ?? '•••');
$frozen = ! empty($card['is_frozen']);
$qrData = (string) ($card['qr'] ?? $card['pan'] ?? $qrPayload ?? $panShow);
$qrSrc = 'https://api.qrserver.com/v1/create-qr-code/?size=160x160&margin=0&data=' . rawurlencode($qrData);
?>
<div class="sq-card-scene">
    <div class="sq-card-flip" <?= $interactive ? 'id="sqCardFlip" role="button" tabindex="0" aria-label="Flip Solqam Card"' : '' ?>>
        <div class="sq-plastic sq-plastic-front <?= $tierClass ?>">
            <?php if ($frozen): ?><div class="sq-frozen-stamp">FROZEN</div><?php endif; ?>
            <div class="sq-plastic-inner">
                <div class="sq-card-top">
                    <div>
                        <div class="sq-card-brand" aria-label="Solqam Marketplace">
                            <div class="sq-word">SOL<span class="q">Q</span>AM</div>
                            <div class="sq-tag">MARKETPLACE</div>
                        </div>
                        <div class="sq-card-chip-row">
                            <div class="sq-chip" aria-hidden="true"></div>
                            <svg class="sq-contactless" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                                <path d="M8 8c2.5 2 2.5 6 0 8M11.5 5.5c3.8 3 3.8 10 0 13M15 3c5 4 5 14 0 18" stroke="#fff" stroke-width="1.8" stroke-linecap="round"/>
                            </svg>
                        </div>
                    </div>
                    <div class="sq-card-qr" title="Scan at partner shop">
                        <img src="<?= $qrSrc ?>" alt="Solqam Card QR">
                    </div>
                </div>
                <div class="sq-pan" id="sqPanFace"><?= esc($panShow) ?></div>
                <div class="sq-card-meta">
                    <div class="sq-holder">
                        <div class="sq-meta-label">Card holder</div>
                        <?= esc($holder) ?>
                    </div>
                    <div>
                        <div class="sq-meta-label">Valid thru</div>
                        <div class="sq-meta-value"><?= esc($exp) ?></div>
                    </div>
                </div>
            </div>
        </div>
        <div class="sq-plastic sq-plastic-back">
            <div class="sq-magstripe"></div>
            <div class="sq-sign-strip">CVV <?= esc($cvv) ?></div>
            <div class="sq-back-copy">
                Solqam identity number <?= esc($panShow) ?>. Division, city of division, member type and unique serial. Not a bank Visa/Mastercard.
                <?php if (! empty($card['card_code'])): ?>
                    <div class="mt-2 font-monospace">Legacy scan <?= esc($card['card_code']) ?></div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>
<?php if ($showBreak && $parts): ?>
<div class="sq-id-wrap">
    <div class="sq-id-full"><?= esc($panShow) ?></div>
    <div class="sq-id-break">
        <div class="sq-id-tile sq-id-p">
            <div class="sym"><?= esc($parts['division_code'] ?: $parts['province']) ?></div>
            <div class="lab">Division<br><b><?= esc($parts['division_name'] ?: $parts['province_name']) ?></b></div>
        </div>
        <div class="sq-id-tile sq-id-c">
            <div class="sym"><?= esc($parts['city_code']) ?></div>
            <div class="lab">City of division<br><b><?= esc($parts['city_name']) ?></b></div>
        </div>
        <div class="sq-id-tile sq-id-t">
            <div class="sym"><?= esc($parts['type']) ?></div>
            <div class="lab">User Type<br><b><?= esc($parts['type_name']) ?></b></div>
        </div>
        <div class="sq-id-tile sq-id-n">
            <div class="sym"><?= esc($parts['unique']) ?></div>
            <div class="lab">ID Number<br><b><?= ($parts['type'] ?? '') === 'M' ? '7 Digits' : '8 Digits' ?></b></div>
        </div>
        <?php if (! empty($parts['category_code'])): ?>
        <div class="sq-id-tile sq-id-n">
            <div class="sym"><?= esc($parts['category_code']) ?></div>
            <div class="lab">Business category<br><b><?= esc($parts['category_name']) ?></b></div>
        </div>
        <?php endif; ?>
    </div>
</div>
<?php endif; ?>
