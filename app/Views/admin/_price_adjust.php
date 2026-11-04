<?php
$action = $adjustAction ?? site_url('admin/products/adjust-prices');
$label  = $adjustLabel ?? 'all catalog products';
?>
<div class="card border-0 shadow-sm rounded-4 p-3 mb-4" style="background:#F8FAFF;border:1px solid #E0E7FF !important;">
    <form action="<?= esc($action) ?>" method="POST" class="row g-2 align-items-end" onsubmit="return confirm('Apply Rs. ' + (this.amount.value || '0') + ' ' + (this.direction.value === 'down' ? 'decrease' : 'increase') + ' to <?= esc($label, 'js') ?>? Storefront prices update immediately.');">
        <?= csrf_field() ?>
        <div class="col-12 col-md-4">
            <div class="small fw-bold text-dark mb-1"><i class="bi bi-currency-exchange text-solqam me-1"></i> One-click price (Rs.)</div>
            <div class="text-muted" style="font-size:0.75rem;">Add or subtract rupees on every product, SKU and flash price.</div>
        </div>
        <div class="col-6 col-md-2">
            <label class="form-label small mb-1">Rupees</label>
            <input type="number" name="amount" class="form-control" min="1" step="1" required placeholder="e.g. 50">
        </div>
        <div class="col-6 col-md-3">
            <label class="form-label small mb-1">Action</label>
            <select name="direction" class="form-select">
                <option value="up">Increase all</option>
                <option value="down">Decrease all</option>
            </select>
        </div>
        <div class="col-12 col-md-3">
            <button type="submit" class="btn btn-solqam w-100">Apply to all</button>
        </div>
    </form>
</div>
