<?php
$prefix   = $prefix ?? 'geo';
$keepDiv  = (string) ($keepDiv ?? '');
$keepCity = (string) ($keepCity ?? '');
$divisions = $divisions ?? \App\Services\Card\SolqamGeoCatalog::divisions();
$invalid   = $invalid ?? static fn (string $key): string => '';
?>
<div class="row g-3 mb-3">
    <div class="col-md-6">
        <label class="form-label fw-semibold small text-muted">Division <span class="text-danger">*</span></label>
        <select name="division_code" id="<?= esc($prefix) ?>_division" class="form-select js-division<?= $invalid('division_code') ?>" data-city="<?= esc($prefix) ?>_city" required>
            <option value="">Select division</option>
            <?php foreach ($divisions as $d): ?>
                <option value="<?= esc($d['code']) ?>" <?= $keepDiv === $d['code'] ? 'selected' : '' ?>><?= esc($d['code'] . ' — ' . $d['name'] . ' (' . $d['province_name'] . ')') ?></option>
            <?php endforeach; ?>
        </select>
        <div class="form-text">First two digits of your Solqam Card.</div>
    </div>
    <div class="col-md-6">
        <label class="form-label fw-semibold small text-muted">City of division <span class="text-danger">*</span></label>
        <select name="city_code" id="<?= esc($prefix) ?>_city" class="form-select js-city<?= $invalid('city_code') ?>" data-keep="<?= esc($keepCity) ?>" required>
            <option value="">Select city</option>
        </select>
        <div class="form-text">Next two digits of your Solqam Card.</div>
    </div>
    <?php
    $cardType = strtoupper((string) ($cardType ?? 'C'));
    $categorySelect = (string) ($categorySelect ?? '');
    ?>
    <div class="col-12">
        <div class="rounded-3 border bg-light px-3 py-2">
            <div class="small text-muted mb-0">Your Solqam Card will look like</div>
            <div class="font-monospace fw-bold" id="<?= esc($prefix) ?>_card_preview" data-card-preview="1" data-type="<?= esc($cardType) ?>" data-category="<?= esc($categorySelect) ?>">••-••-<?= esc($cardType) ?>-••••••••</div>
        </div>
    </div>
</div>
