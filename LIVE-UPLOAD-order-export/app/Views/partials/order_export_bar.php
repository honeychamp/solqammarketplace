<?php
$exportBase = $exportBase ?? 'admin/orders/export';
$keep = $_GET ?? [];
unset($keep['format']);
$qs = static function (array $extra) use ($keep): string {
    return http_build_query(array_filter(array_merge($keep, $extra), static fn ($v) => $v !== '' && $v !== null));
};
$from = (string) ($keep['from'] ?? '');
$to = (string) ($keep['to'] ?? '');
$status = (string) ($keep['status'] ?? '');
$city = (string) ($keep['city'] ?? '');
$pay = (string) ($keep['payment_method'] ?? '');
$hideTest = ($keep['hide_test'] ?? '1') !== '0';
?>
<div class="card-custom p-3 mb-3">
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-2">
        <div>
            <div class="fw-bold">Full Order Export</div>
            <div class="small text-muted mb-0">63 columns · one row per product · opens in Excel / WPS. CSV also available.</div>
        </div>
        <div class="d-flex gap-2">
            <a class="btn btn-success rounded-pill px-3" href="<?= site_url($exportBase . '?' . $qs(['format' => 'xlsx'])) ?>">
                <i class="bi bi-file-earmark-excel me-1"></i> Excel mein kholen
            </a>
            <a class="btn btn-outline-success rounded-pill px-3" href="<?= site_url($exportBase . '?' . $qs(['format' => 'csv'])) ?>">
                CSV
            </a>
        </div>
    </div>
    <form method="get" class="row g-2 align-items-end">
        <div class="col-6 col-md-2">
            <label class="form-label small mb-1">From</label>
            <input type="date" name="from" class="form-control form-control-sm" value="<?= esc($from) ?>">
        </div>
        <div class="col-6 col-md-2">
            <label class="form-label small mb-1">To</label>
            <input type="date" name="to" class="form-control form-control-sm" value="<?= esc($to) ?>">
        </div>
        <div class="col-6 col-md-2">
            <label class="form-label small mb-1">Status</label>
            <select name="status" class="form-select form-select-sm">
                <option value="">All</option>
                <?php foreach (['placed', 'confirmed', 'shipped', 'delivered', 'cancelled', 'returned'] as $st): ?>
                    <option value="<?= $st ?>" <?= $status === $st ? 'selected' : '' ?>><?= ucfirst($st) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-6 col-md-2">
            <label class="form-label small mb-1">City</label>
            <input type="text" name="city" class="form-control form-control-sm" value="<?= esc($city) ?>" placeholder="Karachi">
        </div>
        <div class="col-6 col-md-2">
            <label class="form-label small mb-1">Payment</label>
            <select name="payment_method" class="form-select form-select-sm">
                <option value="">All</option>
                <?php foreach (['cod' => 'COD', 'payfast' => 'Card', 'jazzcash' => 'JazzCash', 'easypaisa' => 'Easypaisa'] as $k => $lab): ?>
                    <option value="<?= $k ?>" <?= $pay === $k ? 'selected' : '' ?>><?= $lab ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-6 col-md-2">
            <div class="form-check mt-4">
                <input type="hidden" name="hide_test" value="0">
                <input class="form-check-input" type="checkbox" name="hide_test" value="1" id="hideTest" <?= $hideTest ? 'checked' : '' ?>>
                <label class="form-check-label small" for="hideTest">Hide test orders</label>
            </div>
        </div>
        <div class="col-12 d-flex flex-wrap gap-2">
            <button type="submit" class="btn btn-sm btn-outline-dark rounded-pill px-3">Apply filters</button>
            <a class="btn btn-sm btn-outline-secondary rounded-pill" href="<?= site_url($exportBase . '?preset=today&format=xlsx') ?>">Today</a>
            <a class="btn btn-sm btn-outline-secondary rounded-pill" href="<?= site_url($exportBase . '?preset=yesterday&format=xlsx') ?>">Yesterday</a>
            <a class="btn btn-sm btn-outline-secondary rounded-pill" href="<?= site_url($exportBase . '?preset=7d&format=xlsx') ?>">Last 7 days</a>
            <a class="btn btn-sm btn-outline-secondary rounded-pill" href="<?= site_url($exportBase . '?preset=month&format=xlsx') ?>">This month</a>
        </div>
    </form>
</div>
