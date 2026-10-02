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
                <option value="<?= $cat['id'] ?>" <?= (string) $p('category_id') === (string) $cat['id'] ? 'selected' : '' ?>><?= esc($cat['label'] ?? $cat['name']) ?></option>
            <?php endforeach; ?>
        </select>
        <div class="form-text">Pick the deepest match (Samsung), not only Electronics.</div>
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

<div class="mb-4" id="productMediaBox">
    <?php
    $primaryImg = null;
    $galleryImgs = [];
    foreach ($images as $img) {
        if (! empty($img['is_primary']) && $primaryImg === null) {
            $primaryImg = $img;
        } else {
            $galleryImgs[] = $img;
        }
    }
    ?>
    <label class="form-label fw-bold small"><?= $product ? 'Product image' : 'Product image' ?></label>
    <div class="solqam-main-photo card border-0 bg-light p-3 mb-3">
        <div id="mainPhotoPreview" class="solqam-photo-preview mb-2 <?= empty($primaryImg['image_path'] ?? null) && empty($primaryImg) ? 'is-empty' : '' ?>">
            <?php if (! empty($primaryImg['image_path'])): ?>
                <img src="<?= esc($primaryImg['image_path']) ?>" alt="Main product">
            <?php else: ?>
                <span class="text-muted small">No image selected</span>
            <?php endif; ?>
        </div>
        <div class="d-flex flex-wrap gap-2">
            <button type="button" class="btn btn-sm btn-primary rounded-pill" id="mainPhotoPick">
                <i class="bi bi-image me-1"></i> <span id="mainPhotoPickLabel"><?= empty($primaryImg) ? 'Choose image' : 'Change image' ?></span>
            </button>
            <button type="button" class="btn btn-sm btn-outline-secondary rounded-pill <?= empty($primaryImg) ? 'd-none' : '' ?>" id="mainPhotoClear">Remove</button>
        </div>
        <input type="file" name="image_file" id="mainPhotoFile" class="d-none" accept="image/*">
        <input type="url" name="image_url" id="mainPhotoUrl" class="form-control form-control-sm mt-2" placeholder="Or paste image URL" value="<?= esc(old('image_url')) ?>">
    </div>

    <label class="form-label fw-bold small">More gallery photos</label>
    <p class="small text-muted mb-2">Select photos to preview. Drag, or use arrows, to change order (1 → 2, 2 → 5…).</p>
    <button type="button" class="btn btn-sm btn-outline-primary rounded-pill mb-2" id="galleryPick">
        <i class="bi bi-plus-lg me-1"></i> Add gallery photos
    </button>
    <input type="file" id="galleryPickInput" class="d-none" accept="image/*" multiple>
    <input type="file" name="gallery_files[]" id="galleryFilesSync" class="d-none" accept="image/*" multiple>
    <div id="galleryOrderFields"></div>
    <div id="galleryBoard" class="solqam-gallery-board d-flex flex-wrap gap-2">
        <?php foreach ($galleryImgs as $g): ?>
            <div class="solqam-gallery-tile" draggable="true" data-kind="existing" data-id="<?= (int) $g['id'] ?>" data-src="<?= esc($g['image_path'], 'attr') ?>">
                <img src="<?= esc($g['image_path']) ?>" alt="">
                <div class="solqam-gallery-pos"></div>
                <div class="solqam-gallery-actions">
                    <button type="button" class="btn btn-sm btn-light py-0 px-1" data-move="-1" title="Move left">&larr;</button>
                    <button type="button" class="btn btn-sm btn-light py-0 px-1" data-move="1" title="Move right">&rarr;</button>
                    <button type="button" class="btn btn-sm btn-light py-0 px-1" data-remove="1" title="Remove">&times;</button>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
</div>

<style>
.solqam-photo-preview { min-height: 160px; border: 1px dashed #cbd5e1; border-radius: 12px; display: flex; align-items: center; justify-content: center; overflow: hidden; background: #fff; }
.solqam-photo-preview img { max-height: 220px; max-width: 100%; object-fit: contain; }
.solqam-gallery-tile { width: 110px; position: relative; border: 1px solid #e2e8f0; border-radius: 10px; overflow: hidden; background: #fff; cursor: grab; }
.solqam-gallery-tile.is-drag { opacity: .45; }
.solqam-gallery-tile img { width: 110px; height: 90px; object-fit: cover; display: block; }
.solqam-gallery-pos { position: absolute; top: 4px; left: 6px; background: #0B30E6; color: #fff; font-size: 11px; font-weight: 700; width: 20px; height: 20px; border-radius: 50%; display: flex; align-items: center; justify-content: center; }
.solqam-gallery-actions { display: flex; justify-content: center; gap: 2px; padding: 4px; background: #f8fafc; }
</style>

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

(function () {
    const mainFile = document.getElementById('mainPhotoFile');
    const mainUrl = document.getElementById('mainPhotoUrl');
    const mainPrev = document.getElementById('mainPhotoPreview');
    const mainPick = document.getElementById('mainPhotoPick');
    const mainClear = document.getElementById('mainPhotoClear');
    const pickLabel = document.getElementById('mainPhotoPickLabel');
    const galleryPick = document.getElementById('galleryPick');
    const galleryPickInput = document.getElementById('galleryPickInput');
    const gallerySync = document.getElementById('galleryFilesSync');
    const galleryBoard = document.getElementById('galleryBoard');
    const galleryOrder = document.getElementById('galleryOrderFields');
    if (!mainFile || !galleryBoard) return;

    function setMainPreview(src) {
        if (src) {
            mainPrev.classList.remove('is-empty');
            mainPrev.innerHTML = '<img src="' + src.replace(/"/g, '') + '" alt="Main product">';
            mainClear.classList.remove('d-none');
            if (pickLabel) pickLabel.textContent = 'Change image';
        } else {
            mainPrev.classList.add('is-empty');
            mainPrev.innerHTML = '<span class="text-muted small">No image selected</span>';
            mainClear.classList.add('d-none');
            if (pickLabel) pickLabel.textContent = 'Choose image';
        }
    }

    mainPick?.addEventListener('click', function () { mainFile.click(); });
    mainFile.addEventListener('change', function () {
        const f = mainFile.files && mainFile.files[0];
        if (!f) return;
        setMainPreview(URL.createObjectURL(f));
        if (mainUrl) mainUrl.value = '';
    });
    mainUrl?.addEventListener('input', function () {
        const v = mainUrl.value.trim();
        if (v) {
            mainFile.value = '';
            setMainPreview(v);
        }
    });
    mainClear?.addEventListener('click', function () {
        mainFile.value = '';
        if (mainUrl) mainUrl.value = '';
        setMainPreview('');
    });

    let dragIndex = null;

    function tiles() {
        return Array.from(galleryBoard.querySelectorAll('.solqam-gallery-tile'));
    }

    function syncGallery() {
        const dt = new DataTransfer();
        galleryOrder.innerHTML = '';
        let n = 0;
        tiles().forEach(function (tile, i) {
            const badge = tile.querySelector('.solqam-gallery-pos');
            if (badge) badge.textContent = String(i + 1);
            const hidden = document.createElement('input');
            hidden.type = 'hidden';
            hidden.name = 'gallery_order[]';
            if (tile.dataset.kind === 'existing') {
                hidden.value = 'id:' + tile.dataset.id;
            } else if (tile._file) {
                hidden.value = 'new:' + n;
                dt.items.add(tile._file);
                n += 1;
            }
            galleryOrder.appendChild(hidden);
        });
        try { gallerySync.files = dt.files; } catch (e) {}
    }

    function moveTile(from, to) {
        const list = tiles();
        if (from < 0 || to < 0 || from >= list.length || to >= list.length) return;
        const el = list[from];
        const target = list[to];
        if (from < to) {
            target.after(el);
        } else {
            target.before(el);
        }
        syncGallery();
    }

    galleryBoard.addEventListener('click', function (e) {
        const tile = e.target.closest('.solqam-gallery-tile');
        if (!tile) return;
        const list = tiles();
        const idx = list.indexOf(tile);
        if (e.target.closest('[data-remove]')) {
            tile.remove();
            syncGallery();
            return;
        }
        const mv = e.target.closest('[data-move]');
        if (mv) {
            moveTile(idx, idx + parseInt(mv.getAttribute('data-move'), 10));
        }
    });

    galleryBoard.addEventListener('dragstart', function (e) {
        const tile = e.target.closest('.solqam-gallery-tile');
        if (!tile) return;
        dragIndex = tiles().indexOf(tile);
        tile.classList.add('is-drag');
    });
    galleryBoard.addEventListener('dragend', function (e) {
        const tile = e.target.closest('.solqam-gallery-tile');
        tile?.classList.remove('is-drag');
        dragIndex = null;
    });
    galleryBoard.addEventListener('dragover', function (e) {
        e.preventDefault();
        const over = e.target.closest('.solqam-gallery-tile');
        if (!over || dragIndex === null) return;
        const to = tiles().indexOf(over);
        if (to !== dragIndex) moveTile(dragIndex, to);
        dragIndex = tiles().indexOf(over);
    });

    function addFiles(fileList) {
        Array.from(fileList || []).forEach(function (file) {
            if (!file.type || file.type.indexOf('image') !== 0) return;
            const tile = document.createElement('div');
            tile.className = 'solqam-gallery-tile';
            tile.draggable = true;
            tile.dataset.kind = 'new';
            tile._file = file;
            tile.innerHTML = '<img alt=""><div class="solqam-gallery-pos"></div><div class="solqam-gallery-actions">'
                + '<button type="button" class="btn btn-sm btn-light py-0 px-1" data-move="-1" title="Move left">&larr;</button>'
                + '<button type="button" class="btn btn-sm btn-light py-0 px-1" data-move="1" title="Move right">&rarr;</button>'
                + '<button type="button" class="btn btn-sm btn-light py-0 px-1" data-remove="1" title="Remove">&times;</button></div>';
            tile.querySelector('img').src = URL.createObjectURL(file);
            galleryBoard.appendChild(tile);
        });
        syncGallery();
    }

    galleryPick?.addEventListener('click', function () { galleryPickInput.click(); });
    galleryPickInput?.addEventListener('change', function () {
        addFiles(galleryPickInput.files);
        galleryPickInput.value = '';
    });

    syncGallery();
})();
</script>
