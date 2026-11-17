<?= $this->extend('layouts/main') ?>
<?= $this->section('content') ?>
<div class="container py-5">
    <div class="col-lg-7 mx-auto">
        <h3 class="fw-bold">Join as a Merchant</h3>
        <p class="text-secondary">Accept Solqam Card in your physical shop. Scan the customer QR, enter the bill, 5% pending credit is created, then you pay Solqam and admin releases it.</p>
        <?php if (session()->getFlashdata('error')): ?><div class="alert alert-danger rounded-4"><?= session()->getFlashdata('error') ?></div><?php endif; ?>
        <?php if (session()->getFlashdata('success')): ?><div class="alert alert-success rounded-4"><?= session()->getFlashdata('success') ?></div><?php endif; ?>
        <form method="post" enctype="multipart/form-data" class="card border-0 shadow-sm rounded-4 p-4">
            <?= csrf_field() ?>
            <div class="row g-3">
                <div class="col-md-6"><label class="form-label small fw-semibold">Owner name <span class="text-danger">*</span></label><input class="form-control" name="owner_name" value="<?= esc(old('owner_name', session()->get('user.name') ?? '')) ?>" required></div>
                <div class="col-md-6"><label class="form-label small fw-semibold">Shop name <span class="text-danger">*</span></label><input class="form-control" name="shop_name" value="<?= esc(old('shop_name')) ?>" required></div>
                <div class="col-md-6"><label class="form-label small fw-semibold">Phone</label><input class="form-control" name="phone" value="<?= esc(old('phone', session()->get('user.phone') ?? '')) ?>"></div>
                <div class="col-md-6"><label class="form-label small fw-semibold">Email</label><input class="form-control" name="email" type="email" value="<?= esc(old('email', session()->get('user.email') ?? '')) ?>"></div>
                <div class="col-12">
                    <?= view('partials/geo_division_city', [
                        'prefix'         => 'join',
                        'keepDiv'        => old('division_code'),
                        'keepCity'       => old('city_code'),
                        'divisions'      => $divisions ?? [],
                        'cardType'       => 'M',
                        'categorySelect' => 'join_category',
                    ]) ?>
                </div>
                <div class="col-md-6"><label class="form-label small fw-semibold">Area</label><input class="form-control" name="area" value="<?= esc(old('area')) ?>"></div>
                <div class="col-md-6"><label class="form-label small fw-semibold">WhatsApp (11 digits)</label><input class="form-control font-monospace" type="tel" name="whatsapp" value="<?= esc(old('whatsapp')) ?>" placeholder="03XXXXXXXXX" maxlength="11" inputmode="numeric" pattern="[0-9]{11}" oninput="this.value = this.value.replace(/[^0-9]/g, '').slice(0, 11);"></div>
                <div class="col-md-6"><label class="form-label small fw-semibold">Opening hours</label><input class="form-control" name="opening_hours" value="<?= esc(old('opening_hours', '10:00 AM – 10:00 PM')) ?>" placeholder="10:00 AM – 10:00 PM"></div>
                <div class="col-md-6">
                    <label class="form-label small fw-semibold">Type of business</label>
                    <select name="category" id="join_category" class="form-select" required>
                        <option value="">Select shop type</option>
                        <?php foreach (($categories ?? []) as $c): ?>
                            <option value="<?= esc($c['code'] ?? $c) ?>" <?= old('category') === ($c['code'] ?? $c) || old('category') === ($c['name'] ?? '') ? 'selected' : '' ?>><?= esc(($c['code'] ?? '') !== '' ? $c['code'] . ' — ' . $c['name'] : $c) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <?= view('partials/merchant_occupancy', [
                    'keepOcc'  => old('occupancy_type'),
                    'keepYear' => old('business_year'),
                    'idPrefix' => 'join',
                    'wrap'     => false,
                ]) ?>
                <div class="col-md-6"><label class="form-label small fw-semibold">Shop outside photo</label><input type="file" name="shop_image" class="form-control" accept="image/*"><div class="form-text">Front / outside. JPG, PNG or WEBP, max 4 MB.</div></div>
                <div class="col-md-6"><label class="form-label small fw-semibold">Shop inside photo</label><input type="file" name="shop_image_in" class="form-control" accept="image/*"><div class="form-text">Interior. JPG, PNG or WEBP, max 4 MB.</div></div>
                <div class="col-12"><label class="form-label small fw-semibold">Complete address <span class="text-danger">*</span></label><textarea name="address" class="form-control" rows="2" required><?= esc(old('address')) ?></textarea></div>
            </div>
            <button class="btn btn-solqam rounded-pill mt-4 px-4" type="submit">Submit merchant shop for approval</button>
        </form>
    </div>
</div>
<?= $this->endSection() ?>
<?= $this->section('scripts') ?>
<script src="<?= base_url('assets/js/solqam-geo.js') ?>?v=20261110b"></script>
<script>solqamBindGeo(<?= json_encode($geoCities ?? [], JSON_UNESCAPED_UNICODE) ?>);</script>
<?= $this->endSection() ?>
