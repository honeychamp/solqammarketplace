<?= $this->extend('layouts/admin') ?>
<?= $this->section('content') ?>
<h4 class="fw-bold mb-3">Seller SLA &amp; fraud limits</h4>
<form method="POST" class="card-custom p-4" style="max-width:640px;">
    <?= csrf_field() ?>
    <div class="mb-3"><label class="form-label">Confirm within (hours)</label><input type="number" name="sla_confirm_hours" class="form-control" value="<?= esc($sla_confirm_hours) ?>"></div>
    <div class="mb-3"><label class="form-label">Ship within (hours)</label><input type="number" name="sla_ship_hours" class="form-control" value="<?= esc($sla_ship_hours) ?>"></div>
    <div class="mb-3"><label class="form-label">Max open COD orders per customer</label><input type="number" name="cod_max_open" class="form-control" value="<?= esc($cod_max_open) ?>"></div>
    <div class="mb-3"><label class="form-label">Login attempts / 15 min / IP</label><input type="number" name="auth_max_hits" class="form-control" value="<?= esc($auth_max_hits) ?>"></div>

    <hr class="my-4">
    <h5 class="fw-bold mb-1">Google &amp; Facebook login</h5>
    <p class="small text-secondary">Same as Daraz: customer and seller can continue with Google or Facebook. Paste keys here — not in code. Add these callback URLs in Google Cloud and Meta Developer:</p>
    <ul class="small mb-3">
        <li><code><?= esc($google_callback ?? '') ?></code></li>
        <li><code><?= esc($facebook_callback ?? '') ?></code></li>
    </ul>
    <div class="mb-3">
        <label class="form-label">Google Client ID</label>
        <input type="text" name="google_oauth_client_id" class="form-control" value="<?= esc($google_oauth_client_id ?? '') ?>" autocomplete="off">
    </div>
    <div class="mb-3">
        <label class="form-label">Google Client Secret</label>
        <input type="password" name="google_oauth_client_secret" class="form-control" placeholder="<?= ! empty($google_secret_set) ? 'Saved — paste a new secret only to replace' : 'Paste Google client secret' ?>" autocomplete="new-password">
    </div>
    <div class="mb-3">
        <label class="form-label">Facebook App ID</label>
        <input type="text" name="facebook_app_id" class="form-control" value="<?= esc($facebook_app_id ?? '') ?>" autocomplete="off">
    </div>
    <div class="mb-3">
        <label class="form-label">Facebook App Secret</label>
        <input type="password" name="facebook_app_secret" class="form-control" placeholder="<?= ! empty($facebook_secret_set) ? 'Saved — paste a new secret only to replace' : 'Paste Facebook app secret' ?>" autocomplete="new-password">
    </div>
    <button class="btn btn-primary rounded-pill">Save</button>
</form>
<?= $this->endSection() ?>
