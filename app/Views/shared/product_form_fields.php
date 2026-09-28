<?php
$product = $product ?? [];
$variants = $variants ?? [];
$images = $images ?? [];
$showStatus = !empty($showStatus);
$p = static fn (string $key, $default = '') => old($key, $product[$key] ?? $default);
?>
<div class="mb-3">
    <label class="form-label fw-bold small">Product Title / Name</label>
    <input type="text" name="name" class="form-control" value="<?= esc($p('name')) ?>" required>
</div>
<div class="row g-3 mb-3">
    <div class="col-md-6">
        <label class="form-label fw-bold small">Category</label>
        <select name="category_id" class="form-select" required>
            <option value="">Select Category</option>
            <?php foreach ($categories as $cat): ?>
                <option value="<?= $cat['id'] ?>" <?= (string) $p('category_id') === (string) $cat['id'] ? 'selected' : '' ?>><?= esc($cat['name']) ?></option>
            <?php endforeach; ?>
        </select>
    </div>
    <div class="col-md-6">
        <label class="form-label fw-bold small">SKU</label>
        <input type="text" name="sku" class="form-control" value="<?= esc($p('sku')) ?>">
    </div>
</div>
<div class="row g-3 mb-3">
    <div class="col-md-4">
        <label class="form-label fw-bold small">Price (PKR)</label>
        <input type="number" step="0.01" name="price" class="form-control" value="<?= esc($p('price')) ?>" required>
    </div>
    <div class="col-md-4">
        <label class="form-label fw-bold small">Stock</label>
        <input type="number" name="stock" class="form-control" min="0" value="<?= esc($p('stock', 10)) ?>" required>
    </div>
    <div class="col-md-4">
        <label class="form-label fw-bold small">Compare-at price (optional)</label>
        <input type="number" step="0.01" name="compare_at_price" class="form-control" value="<?= esc($p('compare_at_price')) ?>">
    </div>
</div>
<div class="row g-3 mb-3">
    <div class="col-md-4">
        <label class="form-label fw-bold small">Brand (optional)</label>
        <input type="text" name="brand" class="form-control" value="<?= esc($p('brand')) ?>" placeholder="Leave empty if none">
    </div>
    <div class="col-md-4">
        <label class="form-label fw-bold small">Warranty (optional)</label>
        <input type="text" name="warranty_info" class="form-control" value="<?= esc($p('warranty_info')) ?>" placeholder="Shown only if filled">
    </div>
    <div class="col-md-4">
        <label class="form-label fw-bold small">Return days</label>
        <input type="number" name="return_days" class="form-control" min="0" value="<?= esc($p('return_days', 7)) ?>">
    </div>
</div>
<div class="mb-3">
    <label class="form-label fw-bold small">Customer cashback (%)</label>
    <div class="input-group">
        <input type="number" name="cashback_percent" class="form-control" min="0" max="100" step="0.1" value="<?= esc($p('cashback_percent', 0)) ?>">
        <span class="input-group-text">%</span>
    </div>
    <small class="text-muted">You choose: 0, 5, 10, 15… Customer gets this % on this product only. 0 = no cashback.</small>
</div>
<?php if ($showStatus): ?>
<div class="mb-3">
    <label class="form-label fw-bold small">Status</label>
    <select name="status" class="form-select">
        <option value="active" <?= $p('status', 'active') === 'active' ? 'selected' : '' ?>>Active</option>
        <option value="inactive" <?= $p('status') === 'inactive' ? 'selected' : '' ?>>Inactive</option>
        <option value="archived" <?= $p('status') === 'archived' ? 'selected' : '' ?>>Archived</option>
    </select>
</div>
<?php endif; ?>

<div class="mb-3 p-3 bg-light rounded-3" id="variantBox">
    <div class="d-flex justify-content-between align-items-center mb-2">
        <label class="form-label fw-bold small mb-0">Color family &amp; size (optional)</label>
        <button type="button" class="btn btn-sm btn-outline-primary" id="addVariantRow">+ Row</button>
    </div>
    <p class="small text-muted mb-2">Empty rows are ignored. Color/size only appear on product page if you fill them.</p>
    <?php
    $rows = $variants;
    if ($rows === []) {
        $rows = [[], []];
    }
    foreach ($rows as $v): ?>
        <div class="row g-2 mb-2 variant-row">
            <div class="col-3"><input type="text" name="variant_color[]" class="form-control form-control-sm" placeholder="Color" value="<?= esc($v['color'] ?? '') ?>"></div>
            <div class="col-3"><input type="text" name="variant_size[]" class="form-control form-control-sm" placeholder="Size" value="<?= esc($v['size'] ?? '') ?>"></div>
            <div class="col-3"><input type="number" name="variant_stock[]" class="form-control form-control-sm" placeholder="Stock" value="<?= esc($v['stock'] ?? '') ?>"></div>
            <div class="col-3"><input type="number" step="0.01" name="variant_price[]" class="form-control form-control-sm" placeholder="Price" value="<?= esc($v['price'] ?? '') ?>"></div>
        </div>
    <?php endforeach; ?>
    <div class="row g-2 mb-2 variant-row">
        <div class="col-3"><input type="text" name="variant_color[]" class="form-control form-control-sm" placeholder="Color"></div>
        <div class="col-3"><input type="text" name="variant_size[]" class="form-control form-control-sm" placeholder="Size"></div>
        <div class="col-3"><input type="number" name="variant_stock[]" class="form-control form-control-sm" placeholder="Stock"></div>
        <div class="col-3"><input type="number" step="0.01" name="variant_price[]" class="form-control form-control-sm" placeholder="Price"></div>
    </div>
</div>

<div class="mb-3">
    <label class="form-label fw-bold small"><?= $product ? 'Replace main image (optional)' : 'Product image' ?></label>
    <input type="file" name="image_file" class="form-control" accept="image/*">
    <input type="url" name="image_url" class="form-control form-control-sm mt-2" placeholder="Or image URL" value="<?= esc(old('image_url')) ?>">
    <?php if ($images): ?>
        <div class="d-flex gap-2 mt-2 flex-wrap">
            <?php foreach ($images as $img): ?>
                <img src="<?= esc($img['image_path']) ?>" class="img-thumbnail rounded" style="width:64px;height:64px;object-fit:cover;" alt="">
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
    <label class="form-label fw-bold small mt-3">More gallery photos</label>
    <input type="file" name="gallery_files[]" class="form-control" accept="image/*" multiple>
</div>

<div class="mb-3">
    <label class="form-label fw-bold small">Description</label>
    <textarea name="description" class="form-control" rows="5" required><?= esc($p('description')) ?></textarea>
</div>
<div class="mb-3">
    <label class="form-label fw-bold small">Highlights (optional, one per line)</label>
    <textarea name="highlights" class="form-control" rows="3" placeholder="Only shown if filled"><?= esc($p('highlights')) ?></textarea>
</div>
<div class="mb-3">
    <label class="form-label fw-bold small">Specifications / Details (optional)</label>
    <textarea name="specifications" class="form-control" rows="4" placeholder="Label: Value — one per line"><?= esc($p('specifications')) ?></textarea>
</div>
<div class="mb-3">
    <label class="form-label fw-bold small">Size guide (optional)</label>
    <textarea name="size_guide" class="form-control" rows="3" placeholder="Shown on PDP only if filled"><?= esc($p('size_guide')) ?></textarea>
</div>
<script>
document.getElementById('addVariantRow')?.addEventListener('click', function () {
    const box = document.getElementById('variantBox');
    const row = box.querySelector('.variant-row');
    if (!row) return;
    const clone = row.cloneNode(true);
    clone.querySelectorAll('input').forEach(function (i) { i.value = ''; });
    box.appendChild(clone);
});
</script>
