<?php
$hubQ = function_exists('hub_search_q') ? hub_search_q() : trim((string) ($_GET['q'] ?? ''));
$hubKeep = $_GET;
unset($hubKeep['q'], $hubKeep['page']);
?>
<div class="hub-page-tools d-flex align-items-center gap-2 flex-grow-1 mx-lg-3 my-2 my-lg-0">
    <span id="hubTotalBadge" class="badge rounded-pill px-3 py-2 text-nowrap" style="background:#EEF2FF;color:#0B30E6;font-weight:700;">Total: 0</span>
    <form method="get" action="<?= site_url(uri_string()) ?>" id="hubPageSearchForm" class="flex-grow-1" role="search">
        <?php foreach ($hubKeep as $hubKey => $hubVal): ?>
            <?php if (is_array($hubVal)): ?>
                <?php foreach ($hubVal as $hubOne): ?>
                    <?php if (is_scalar($hubOne)): ?>
                        <input type="hidden" name="<?= esc($hubKey, 'attr') ?>[]" value="<?= esc((string) $hubOne) ?>">
                    <?php endif; ?>
                <?php endforeach; ?>
            <?php elseif ($hubVal !== null && $hubVal !== ''): ?>
                <input type="hidden" name="<?= esc((string) $hubKey, 'attr') ?>" value="<?= esc((string) $hubVal) ?>">
            <?php endif; ?>
        <?php endforeach; ?>
        <div class="input-group">
            <span class="input-group-text bg-white border-end-0 text-muted"><i class="bi bi-search"></i></span>
            <input type="search" id="hubPageSearch" name="q" value="<?= esc($hubQ) ?>" class="form-control border-start-0" placeholder="Search all pages..." autocomplete="off">
        </div>
    </form>
    <button type="button" id="hubExcelExport" class="btn btn-success text-nowrap rounded-pill px-3" title="Download this page data as Excel">
        <i class="bi bi-file-earmark-excel me-1"></i> Download Excel
    </button>
</div>
