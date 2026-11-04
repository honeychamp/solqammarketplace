<?php
$summary     = $summary ?? [];
$rows        = $rows ?? [];
$filter      = $filter ?? 'all';
$q           = $q ?? '';
$showStore   = ! empty($showStore);
$mine        = ! empty($mine);
$listUrl     = $listUrl ?? '#';
$editBase    = rtrim((string) ($editBase ?? ''), '/');
$stockUrl    = rtrim((string) ($stockUrl ?? ''), '/');
$oosUrl      = $oosUrl ?? '#';
$productsUrl = $productsUrl ?? '#';
$qs = static function (array $extra) use ($filter, $q, $mine, $listUrl): string {
    $p = array_filter([
        'filter' => $extra['filter'] ?? $filter,
        'q'      => array_key_exists('q', $extra) ? $extra['q'] : $q,
        'mine'   => ! empty($extra['mine'] ?? ($mine ? '1' : '')),
    ], static fn ($v) => $v !== '' && $v !== false && $v !== 'all');
    if (($p['filter'] ?? '') === 'all') {
        unset($p['filter']);
    }
    if (empty($p['mine'])) {
        unset($p['mine']);
    } else {
        $p['mine'] = '1';
    }
    return $listUrl . ($p ? ('?' . http_build_query($p)) : '');
};
$backPath = '/' . ltrim((string) uri_string(), '/');
$qsNow    = http_build_query(array_filter([
    'filter' => $filter !== 'all' ? $filter : null,
    'q'      => $q !== '' ? $q : null,
    'mine'   => $mine ? '1' : null,
]));
if ($qsNow !== '') {
    $backPath .= '?' . $qsNow;
}
$chip = static function (string $key, string $label, int $n) use ($filter, $qs): string {
    $on = $filter === $key ? 'btn-dark' : 'btn-outline-secondary';
    return '<a class="btn btn-sm rounded-pill ' . $on . '" href="' . esc($qs(['filter' => $key]), 'attr') . '">' . esc($label) . ' <span class="badge text-bg-light">' . (int) $n . '</span></a>';
};
?>
<div class="d-flex flex-wrap justify-content-between align-items-start gap-3 mb-4">
    <div>
        <h4 class="fw-bold mb-1">Inventory</h4>
        <p class="text-secondary small mb-0">Every SKU, stock, variant, cost, sold units and open orders — in one place.</p>
    </div>
    <div class="d-flex gap-2 flex-wrap">
        <a href="<?= esc($productsUrl) ?>" class="btn btn-outline-secondary rounded-pill px-3">Products</a>
        <a href="<?= esc($oosUrl) ?>" class="btn btn-outline-danger rounded-pill px-3">Out of stock</a>
    </div>
</div>

<div class="row g-3 mb-4">
    <div class="col-6 col-xl">
        <div class="kpi-tile h-100"><span>SKUs</span><strong><?= (int) ($summary['skus'] ?? 0) ?></strong><em><?= (int) ($summary['variant_skus'] ?? 0) ?> variants</em></div>
    </div>
    <div class="col-6 col-xl">
        <div class="kpi-tile emerald h-100"><span>Units on hand</span><strong><?= number_format((int) ($summary['units'] ?? 0)) ?></strong><em><?= (int) ($summary['in_stock'] ?? 0) ?> in stock</em></div>
    </div>
    <div class="col-6 col-xl">
        <div class="kpi-tile gold h-100"><span>Low stock</span><strong><?= (int) ($summary['low'] ?? 0) ?></strong><em>1–<?= (int) \App\Services\Catalog\InventoryService::LOW_STOCK ?> units</em></div>
    </div>
    <div class="col-6 col-xl">
        <div class="kpi-tile accent h-100"><span>Out of stock</span><strong><?= (int) ($summary['oos'] ?? 0) ?></strong><em><?= (int) ($summary['inactive'] ?? 0) ?> inactive</em></div>
    </div>
    <div class="col-6 col-xl">
        <div class="kpi-tile h-100"><span>Retail value</span><strong>Rs. <?= number_format((float) ($summary['retail_value'] ?? 0), 0) ?></strong><em>stock × price</em></div>
    </div>
    <div class="col-6 col-xl">
        <div class="kpi-tile h-100"><span>Cost value</span><strong>Rs. <?= number_format((float) ($summary['cost_value'] ?? 0), 0) ?></strong><em>stock × cost</em></div>
    </div>
    <div class="col-6 col-xl">
        <div class="kpi-tile h-100"><span>Sold</span><strong><?= number_format((int) ($summary['sold'] ?? 0)) ?></strong><em><?= (int) ($summary['open_qty'] ?? 0) ?> open order pcs</em></div>
    </div>
</div>

<div class="card border-0 shadow-sm rounded-4 p-3 p-md-4 bg-white">
    <div class="d-flex flex-wrap gap-2 align-items-center mb-3">
        <?= $chip('all', 'All', (int) ($summary['skus'] ?? 0)) ?>
        <?= $chip('in', 'In stock', (int) ($summary['in_stock'] ?? 0)) ?>
        <?= $chip('low', 'Low', (int) ($summary['low'] ?? 0)) ?>
        <?= $chip('oos', 'Out of stock', (int) ($summary['oos'] ?? 0)) ?>
        <?php if ($showStore): ?>
            <a class="btn btn-sm rounded-pill <?= $mine ? 'btn-dark' : 'btn-outline-secondary' ?>" href="<?= esc($qs(['mine' => ! $mine, 'filter' => $filter])) ?>">My store only</a>
        <?php endif; ?>
        <form method="get" action="<?= esc($listUrl) ?>" class="ms-md-auto d-flex gap-2">
            <?php if ($filter !== 'all'): ?><input type="hidden" name="filter" value="<?= esc($filter) ?>"><?php endif; ?>
            <?php if ($mine): ?><input type="hidden" name="mine" value="1"><?php endif; ?>
            <input type="search" name="q" value="<?= esc($q) ?>" class="form-control form-control-sm" placeholder="Search name, SKU, brand, store" style="min-width:220px">
            <button class="btn btn-sm btn-primary rounded-pill px-3">Search</button>
        </form>
    </div>

    <div class="table-responsive">
        <table class="table table-sm align-middle">
            <thead class="table-light small">
                <tr>
                    <th>Product</th>
                    <?php if ($showStore): ?><th>Store</th><?php endif; ?>
                    <th>SKU</th>
                    <th>Brand</th>
                    <th>Category</th>
                    <th>Status</th>
                    <th>Stock</th>
                    <th>Variants</th>
                    <th>Price</th>
                    <th>Cost</th>
                    <th>Retail value</th>
                    <th>Cost value</th>
                    <th>Weight</th>
                    <th>Supplier</th>
                    <th>Packing</th>
                    <th>Sold</th>
                    <th>Open pcs</th>
                    <th>Updated</th>
                    <th class="text-end">Update stock</th>
                </tr>
            </thead>
            <tbody>
            <?php if ($rows === []): ?>
                <tr><td colspan="<?= $showStore ? 19 : 18 ?>" class="text-center py-5 text-muted">No inventory rows for this filter.</td></tr>
            <?php endif; ?>
            <?php foreach ($rows as $p): ?>
                <?php
                    $band = $p['stock_band'] ?? 'in';
                    $trClass = $band === 'oos' ? 'table-danger' : ($band === 'low' ? 'table-warning' : '');
                    $vars = $p['variants'] ?? [];
                    $pack = trim((string) ($p['packing_type'] ?? ''));
                    $packCost = (float) ($p['packing_cost'] ?? 0);
                    $packLabel = $pack !== '' ? $pack : '—';
                    if ($packCost > 0) {
                        $packLabel .= ' / Rs. ' . number_format($packCost, 0);
                    }
                    $varBits = [];
                    foreach ($vars as $v) {
                        $lab = trim(implode(' / ', array_filter([$v['color'] ?? '', $v['size'] ?? ''])));
                        if ($lab === '') {
                            $lab = 'Standard';
                        }
                        $vsku = trim((string) ($v['sku'] ?? ''));
                        $varBits[] = $lab . ($vsku !== '' ? ' [' . $vsku . ']' : '') . ' × ' . (int) ($v['stock'] ?? 0);
                    }
                ?>
                <tr class="<?= $trClass ?>">
                    <td>
                        <div class="d-flex align-items-center gap-2">
                            <img src="<?= esc($p['primary_image'] ?: base_url('assets/images/product-placeholder.svg')) ?>" alt="" class="rounded" style="width:40px;height:40px;object-fit:cover">
                            <div>
                                <a href="<?= esc($editBase . '/' . (int) $p['id']) ?>" class="fw-semibold text-dark text-decoration-none"><?= esc($p['name'] ?? '') ?></a>
                                <div class="small"><a href="<?= site_url('product/' . (int) $p['id']) ?>" target="_blank" class="text-secondary">View <i class="bi bi-box-arrow-up-right"></i></a></div>
                            </div>
                        </div>
                    </td>
                    <?php if ($showStore): ?>
                        <td class="small"><?= esc($p['store_name'] ?? '—') ?></td>
                    <?php endif; ?>
                    <td><code class="small"><?= esc($p['sku'] ?? '—') ?></code></td>
                    <td class="small"><?= esc($p['brand'] ?: '—') ?></td>
                    <td class="small"><?= esc($p['category_name'] ?? '—') ?></td>
                    <td><span class="badge bg-<?= ($p['status'] ?? '') === 'active' ? 'success' : 'secondary' ?>"><?= esc(ucfirst((string) ($p['status'] ?? ''))) ?></span></td>
                    <td>
                        <?php if ($band === 'oos'): ?>
                            <span class="badge bg-danger">Out of stock</span>
                        <?php elseif ($band === 'low'): ?>
                            <span class="badge bg-warning text-dark"><?= (int) $p['stock'] ?> low</span>
                        <?php else: ?>
                            <span class="fw-bold"><?= (int) $p['stock'] ?></span>
                        <?php endif; ?>
                    </td>
                    <td class="small" style="max-width:220px">
                        <?php if ($varBits === []): ?>
                            <span class="text-muted">—</span>
                        <?php else: ?>
                            <?= esc(implode('; ', $varBits)) ?>
                            <div class="text-muted">sum <?= (int) ($p['variant_stock'] ?? 0) ?></div>
                        <?php endif; ?>
                    </td>
                    <td class="small text-nowrap">Rs. <?= number_format((float) $p['price'], 0) ?><?php if ((float) ($p['compare_at_price'] ?? 0) > 0): ?><div class="text-muted"><s><?= number_format((float) $p['compare_at_price'], 0) ?></s></div><?php endif; ?></td>
                    <td class="small text-nowrap"><?= (float) ($p['product_cost'] ?? 0) > 0 ? 'Rs. ' . number_format((float) $p['product_cost'], 0) : '—' ?></td>
                    <td class="small text-nowrap">Rs. <?= number_format((float) ($p['retail_value'] ?? 0), 0) ?></td>
                    <td class="small text-nowrap"><?= (float) ($p['cost_value'] ?? 0) > 0 ? 'Rs. ' . number_format((float) $p['cost_value'], 0) : '—' ?></td>
                    <td class="small"><?= (float) ($p['weight'] ?? 0) > 0 ? number_format((float) $p['weight'], 2) . ' kg' : '—' ?></td>
                    <td class="small"><?= esc(($p['supplier'] ?? '') !== '' ? $p['supplier'] : '—') ?></td>
                    <td class="small"><?= esc($packLabel) ?></td>
                    <td><?= number_format((int) ($p['sold_count'] ?? 0)) ?></td>
                    <td><?= (int) ($p['open_qty'] ?? 0) ?></td>
                    <td class="small text-nowrap"><?= esc($p['updated_at'] ?? '—') ?></td>
                    <td class="text-end">
                        <form method="post" action="<?= esc($stockUrl . '/' . (int) $p['id']) ?>" class="d-inline-flex gap-1 justify-content-end">
                            <?= csrf_field() ?>
                            <input type="hidden" name="back" value="<?= esc($backPath) ?>">
                            <input type="number" name="stock" min="0" value="<?= (int) $p['stock'] ?>" class="form-control form-control-sm" style="width:72px">
                            <button class="btn btn-sm btn-outline-primary">Save</button>
                            <a class="btn btn-sm btn-outline-secondary" href="<?= esc($editBase . '/' . (int) $p['id']) ?>">Edit</a>
                        </form>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <p class="small text-muted mb-0 mt-2"><?= count($rows) ?> rows on this view · KPIs count the full catalog<?= $q !== '' ? ' matching “' . esc($q) . '”' : '' ?>.</p>
</div>
