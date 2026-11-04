<?php
$exportBase = $exportBase ?? 'admin/orders/export';
$exportUrl  = site_url($exportBase);
$from       = preg_match('/^\d{4}-\d{2}-\d{2}$/', (string) ($_GET['from'] ?? '')) ? (string) $_GET['from'] : '';
$to         = preg_match('/^\d{4}-\d{2}-\d{2}$/', (string) ($_GET['to'] ?? '')) ? (string) $_GET['to'] : '';
$nowY       = (int) date('Y');
$fromYear   = (int) ($_GET['from_year'] ?? 0);
$fromMonth  = (int) ($_GET['from_month'] ?? 0);
$toYear     = (int) ($_GET['to_year'] ?? 0);
$toMonth    = (int) ($_GET['to_month'] ?? 0);
$months     = [1 => 'Jan', 2 => 'Feb', 3 => 'Mar', 4 => 'Apr', 5 => 'May', 6 => 'Jun', 7 => 'Jul', 8 => 'Aug', 9 => 'Sep', 10 => 'Oct', 11 => 'Nov', 12 => 'Dec'];
$years      = range(2020, $nowY);
$presetUrl  = static function (string $preset) use ($exportUrl): string {
    return $exportUrl . '?' . http_build_query(['preset' => $preset, 'format' => 'xlsx']);
};
?>
<div class="card-custom p-3 mb-3">
    <div class="mb-2">
        <div class="fw-bold">Order Excel</div>
        <div class="small text-muted mb-0">Leave dates empty for <strong>all orders, all years</strong>. Or pick From–To / month–year. Phone, order # and dates stay as text so nothing is cut.</div>
    </div>
    <form method="get" action="<?= esc($exportUrl) ?>" class="row g-2 align-items-end">
        <input type="hidden" name="format" value="xlsx">
        <div class="col-6 col-md-2">
            <label class="form-label small mb-1">From date</label>
            <input type="date" name="from" min="2020-01-01" class="form-control form-control-sm" value="<?= esc($from) ?>">
        </div>
        <div class="col-6 col-md-2">
            <label class="form-label small mb-1">To date</label>
            <input type="date" name="to" min="2020-01-01" class="form-control form-control-sm" value="<?= esc($to) ?>">
        </div>
        <div class="col-6 col-md-2">
            <label class="form-label small mb-1">From month / year</label>
            <div class="d-flex gap-1">
                <select name="from_month" class="form-select form-select-sm">
                    <option value="">Month</option>
                    <?php foreach ($months as $n => $lab): ?>
                        <option value="<?= $n ?>" <?= $fromMonth === $n ? 'selected' : '' ?>><?= $lab ?></option>
                    <?php endforeach; ?>
                </select>
                <select name="from_year" class="form-select form-select-sm">
                    <option value="">Year</option>
                    <?php foreach ($years as $y): ?>
                        <option value="<?= $y ?>" <?= $fromYear === $y ? 'selected' : '' ?>><?= $y ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>
        <div class="col-6 col-md-2">
            <label class="form-label small mb-1">To month / year</label>
            <div class="d-flex gap-1">
                <select name="to_month" class="form-select form-select-sm">
                    <option value="">Month</option>
                    <?php foreach ($months as $n => $lab): ?>
                        <option value="<?= $n ?>" <?= $toMonth === $n ? 'selected' : '' ?>><?= $lab ?></option>
                    <?php endforeach; ?>
                </select>
                <select name="to_year" class="form-select form-select-sm">
                    <option value="">Year</option>
                    <?php foreach ($years as $y): ?>
                        <option value="<?= $y ?>" <?= $toYear === $y ? 'selected' : '' ?>><?= $y ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>
        <div class="col-12 d-flex flex-wrap gap-2">
            <button type="submit" class="btn btn-success rounded-pill px-3">
                <i class="bi bi-file-earmark-excel me-1"></i> Excel download
            </button>
            <button type="submit" class="btn btn-outline-success rounded-pill px-3" name="format" value="csv">CSV</button>
            <a class="btn btn-sm btn-dark rounded-pill" href="<?= esc($presetUrl('all')) ?>">All time</a>
            <a class="btn btn-sm btn-outline-secondary rounded-pill" href="<?= esc($presetUrl('today')) ?>">Today</a>
            <a class="btn btn-sm btn-outline-secondary rounded-pill" href="<?= esc($presetUrl('7d')) ?>">Last 7 days</a>
            <a class="btn btn-sm btn-outline-secondary rounded-pill" href="<?= esc($presetUrl('month')) ?>">This month</a>
            <a class="btn btn-sm btn-outline-secondary rounded-pill" href="<?= esc($presetUrl('last_month')) ?>">Last month</a>
            <a class="btn btn-sm btn-outline-secondary rounded-pill" href="<?= esc($presetUrl('3m')) ?>">Last 3 months</a>
            <a class="btn btn-sm btn-outline-secondary rounded-pill" href="<?= esc($presetUrl('6m')) ?>">Last 6 months</a>
            <a class="btn btn-sm btn-outline-secondary rounded-pill" href="<?= esc($presetUrl('year')) ?>">This year</a>
            <a class="btn btn-sm btn-outline-secondary rounded-pill" href="<?= esc($presetUrl('last_year')) ?>">Last year</a>
        </div>
    </form>
</div>
