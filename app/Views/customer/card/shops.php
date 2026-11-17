<?= $this->extend('layouts/main') ?>
<?= $this->section('content') ?>
<div class="container py-4">
    <h3 class="fw-bold mb-1">Solqam Card shops</h3>
    <p class="text-secondary small">Filter by name, city or category. These merchants scan your card and add 5% pending credits.</p>
    <form class="row g-2 mb-4" method="get" id="shopFilterForm">
        <input type="hidden" name="lat" id="nearLat" value="<?= esc($filters['lat'] ?? '') ?>">
        <input type="hidden" name="lng" id="nearLng" value="<?= esc($filters['lng'] ?? '') ?>">
        <div class="col-md-4"><input class="form-control" name="q" value="<?= esc($filters['q'] ?? '') ?>" placeholder="Shop name or area"></div>
        <div class="col-md-3">
            <select class="form-select" name="city">
                <option value="">All cities</option>
                <?php foreach (($cities ?? []) as $city): ?>
                    <option value="<?= esc($city) ?>" <?= ($filters['city'] ?? '') === $city ? 'selected' : '' ?>><?= esc($city) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-md-3">
            <select class="form-select" name="category">
                <option value="">All categories</option>
                <?php foreach (($categories ?? []) as $cat): ?>
                    <?php
                    $catName = is_array($cat) ? (string) ($cat['name'] ?? '') : (string) $cat;
                    $catCode = is_array($cat) ? (string) ($cat['code'] ?? '') : '';
                    ?>
                    <option value="<?= esc($catName) ?>" <?= ($filters['category'] ?? '') === $catName ? 'selected' : '' ?>><?= esc($catCode !== '' ? $catCode . ' — ' . $catName : $catName) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-md-2 d-flex gap-1">
            <button class="btn btn-solqam flex-grow-1" type="submit">Filter</button>
            <button class="btn btn-outline-dark" type="button" id="nearMeBtn" title="Shops nearest to me"><i class="bi bi-geo-alt"></i></button>
        </div>
    </form>
    <div class="row g-3">
        <?php foreach (($shops ?? []) as $shop): ?>
            <div class="col-md-4">
                <div class="card border-0 shadow-sm rounded-4 overflow-hidden h-100">
                    <?php if (! empty($shop['shop_image']) || ! empty($shop['shop_image_in'])): ?>
                        <div class="d-flex">
                            <?php if (! empty($shop['shop_image'])): ?>
                                <img src="<?= base_url($shop['shop_image']) ?>" alt="Outside" style="height:140px;object-fit:cover;width:<?= ! empty($shop['shop_image_in']) ? '50%' : '100%' ?>;">
                            <?php endif; ?>
                            <?php if (! empty($shop['shop_image_in'])): ?>
                                <img src="<?= base_url($shop['shop_image_in']) ?>" alt="Inside" style="height:140px;object-fit:cover;width:<?= ! empty($shop['shop_image']) ? '50%' : '100%' ?>;">
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>
                    <div class="p-3">
                        <div class="fw-bold"><?= esc($shop['shop_name']) ?></div>
                        <div class="badge bg-light text-dark border mb-1"><?= esc(trim(($shop['category_code'] ?? '') . ' ' . ($shop['category'] ?? ''))) ?></div>
                        <div class="small text-muted"><?= esc($shop['city']) ?><?= ! empty($shop['area']) ? ' · ' . esc($shop['area']) : '' ?><?php if (isset($shop['distance_km']) && $shop['distance_km'] !== null): ?> · <?= esc((string) $shop['distance_km']) ?> km<?php endif; ?></div>
                        <div class="small"><?= esc($shop['address'] ?? '') ?></div>
                        <?php if (! empty($shop['opening_hours'])): ?><div class="small text-muted mt-1"><?= esc($shop['opening_hours']) ?></div><?php endif; ?>
                        <?php if (! empty($shop['whatsapp'])): ?><div class="small mt-1">WhatsApp <?= esc($shop['whatsapp']) ?></div><?php endif; ?>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
        <?php if (empty($shops)): ?>
            <p class="text-muted">No matching shops yet.</p>
        <?php endif; ?>
    </div>
</div>
<?= $this->endSection() ?>
<?= $this->section('scripts') ?>
<script>
document.getElementById('nearMeBtn')?.addEventListener('click', function () {
  if (!navigator.geolocation) return;
  navigator.geolocation.getCurrentPosition(function (pos) {
    document.getElementById('nearLat').value = pos.coords.latitude.toFixed(6);
    document.getElementById('nearLng').value = pos.coords.longitude.toFixed(6);
    document.getElementById('shopFilterForm').submit();
  });
});
</script>
<?= $this->endSection() ?>
