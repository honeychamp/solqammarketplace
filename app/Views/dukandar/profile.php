<?= $this->extend('layouts/dukandar') ?>
<?= $this->section('content') ?>
<?php $p = $shop ?? []; ?>
<form method="post" enctype="multipart/form-data" class="card border-0 shadow-sm rounded-4 p-4">
    <?= csrf_field() ?>
    <h5 class="fw-bold mb-3">Shop profile</h5>
    <div class="row g-3">
        <div class="col-md-6"><label class="form-label small">Owner name</label><input class="form-control" name="owner_name" value="<?= esc($p['owner_name'] ?? '') ?>" required></div>
        <div class="col-md-6"><label class="form-label small">Shop name</label><input class="form-control" name="shop_name" value="<?= esc($p['shop_name'] ?? '') ?>" required></div>
        <div class="col-md-6"><label class="form-label small">Phone</label><input class="form-control" name="phone" value="<?= esc($p['phone'] ?? '') ?>"></div>
        <div class="col-md-6"><label class="form-label small">WhatsApp (11 digits)</label><input class="form-control font-monospace" type="tel" name="whatsapp" value="<?= esc($p['whatsapp'] ?? '') ?>" placeholder="03XXXXXXXXX" maxlength="11" inputmode="numeric" pattern="[0-9]{11}" oninput="this.value = this.value.replace(/[^0-9]/g, '').slice(0, 11);"></div>
        <div class="col-md-6"><label class="form-label small">Email</label><input class="form-control" name="email" type="email" value="<?= esc($p['email'] ?? '') ?>"></div>
        <div class="col-md-6"><label class="form-label small">Opening hours</label><input class="form-control" name="opening_hours" value="<?= esc($p['opening_hours'] ?? '') ?>" placeholder="10:00 AM – 10:00 PM"></div>
        <div class="col-12">
            <?= view('partials/geo_division_city', [
                'prefix'         => 'profile',
                'keepDiv'        => (string) ($userGeo['division_code'] ?? ''),
                'keepCity'       => (string) ($userGeo['city_code'] ?? ''),
                'divisions'      => $divisions ?? [],
                'cardType'       => 'M',
                'categorySelect' => 'profile_category',
            ]) ?>
        </div>
        <?= view('partials/merchant_occupancy', [
            'keepOcc'  => old('occupancy_type', $p['occupancy_type'] ?? ''),
            'keepYear' => old('business_year', (string) ($p['business_year'] ?? '')),
            'idPrefix' => 'profile',
            'wrap'     => false,
        ]) ?>
        <div class="col-md-4"><label class="form-label small">Area / landmark</label><input class="form-control" name="area" value="<?= esc($p['area'] ?? '') ?>"></div>
        <div class="col-md-4">
            <label class="form-label small">Type of business</label>
            <div class="form-text mb-1">Last 3 digits of your merchant card. Every shop of this type in Pakistan shares this code.</div>
            <select class="form-select" name="category" id="profile_category">
                <?php foreach (($categories ?? []) as $c): ?>
                    <?php $code = $c['code'] ?? $c; $name = $c['name'] ?? $c; ?>
                    <option value="<?= esc($code) ?>" <?= ($p['category_code'] ?? '') === $code || ($p['category'] ?? '') === $name ? 'selected' : '' ?>><?= esc(is_array($c) ? $c['code'] . ' — ' . $c['name'] : $c) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-12"><label class="form-label small">Complete address</label><textarea class="form-control" name="address" rows="2"><?= esc($p['address'] ?? '') ?></textarea></div>
        <div class="col-md-4"><label class="form-label small">Latitude</label><input class="form-control" name="lat" id="shopLat" value="<?= esc($p['lat'] ?? '') ?>" placeholder="optional"></div>
        <div class="col-md-4"><label class="form-label small">Longitude</label><input class="form-control" name="lng" id="shopLng" value="<?= esc($p['lng'] ?? '') ?>" placeholder="optional"></div>
        <div class="col-md-4 d-flex align-items-end"><button class="btn btn-outline-secondary w-100" type="button" id="useMyLocation">Use my location</button></div>
        <div class="col-md-6">
            <label class="form-label small">Shop outside photo</label>
            <input type="file" name="shop_image" class="form-control" accept="image/*">
            <?php if (! empty($p['shop_image'])): ?>
                <img src="<?= base_url($p['shop_image']) ?>" alt="Outside" class="rounded-3 mt-2" style="height:80px;object-fit:cover;">
            <?php endif; ?>
        </div>
        <div class="col-md-6">
            <label class="form-label small">Shop inside photo</label>
            <input type="file" name="shop_image_in" class="form-control" accept="image/*">
            <?php if (! empty($p['shop_image_in'])): ?>
                <img src="<?= base_url($p['shop_image_in']) ?>" alt="Inside" class="rounded-3 mt-2" style="height:80px;object-fit:cover;">
            <?php endif; ?>
        </div>
    </div>
    <button class="btn btn-solqam rounded-pill mt-4 px-4" type="submit">Save profile</button>
</form>
<?= $this->endSection() ?>
<?= $this->section('scripts') ?>
<script src="<?= base_url('assets/js/solqam-geo.js') ?>?v=20261110b"></script>
<script>solqamBindGeo(<?= json_encode($geoCities ?? [], JSON_UNESCAPED_UNICODE) ?>);</script>
<script>
document.getElementById('useMyLocation')?.addEventListener('click', function () {
  if (!navigator.geolocation) return;
  navigator.geolocation.getCurrentPosition(function (pos) {
    document.getElementById('shopLat').value = pos.coords.latitude.toFixed(6);
    document.getElementById('shopLng').value = pos.coords.longitude.toFixed(6);
  });
});
</script>
<?= $this->endSection() ?>
