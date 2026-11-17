<?php
$keepOcc  = (string) ($keepOcc ?? '');
$keepYear = (string) ($keepYear ?? '');
$idPrefix = (string) ($idPrefix ?? 'shop');
$invalid  = $invalid ?? static fn (string $f): string => '';
$wrap     = $wrap ?? true;
$occId    = $idPrefix . '_occupancy';
$yearId   = $idPrefix . '_business_year';
?>
<?php if ($wrap): ?>
<div class="row g-3 mb-3">
<?php endif; ?>
    <div class="col-md-6">
        <label class="form-label fw-semibold small text-muted" for="<?= esc($occId) ?>">Shop owner type <span class="text-danger">*</span></label>
        <select name="occupancy_type" id="<?= esc($occId) ?>" class="form-select<?= $invalid('occupancy_type') ?>" required>
            <option value="">Select owner type</option>
            <option value="owner" <?= $keepOcc === 'owner' ? 'selected' : '' ?>>Owner</option>
            <option value="rent" <?= $keepOcc === 'rent' ? 'selected' : '' ?>>Rent</option>
        </select>
    </div>
    <div class="col-md-6">
        <label class="form-label fw-semibold small text-muted" for="<?= esc($yearId) ?>">Business year <span class="text-danger">*</span></label>
        <select name="business_year" id="<?= esc($yearId) ?>" class="form-select<?= $invalid('business_year') ?>" required>
            <option value="">Year business started</option>
            <?php foreach (merchant_business_years() as $y): ?>
                <option value="<?= $y ?>" <?= $keepYear === (string) $y ? 'selected' : '' ?>><?= $y ?></option>
            <?php endforeach; ?>
        </select>
        <div class="form-text text-muted" style="font-size: 0.75rem;">Year this shop started operating.</div>
    </div>
<?php if ($wrap): ?>
</div>
<?php endif; ?>
