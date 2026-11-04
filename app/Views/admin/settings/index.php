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
    <p class="small text-secondary">Login uses a popup (not a redirect), so hosting ModSecurity cannot block it. In Google Cloud add <strong>Authorized JavaScript origins</strong> only:</p>
    <ul class="small mb-3">
        <li><code>https://solqam.com</code></li>
        <li>Facebook App domains: <code>solqam.com</code> — Website: <code>https://solqam.com/</code></li>
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
    <hr class="my-4">
    <h5 class="fw-bold mb-1">Gift wrap</h5>
    <p class="small text-secondary">Optional wrapping on product, cart and checkout. Fee is added to the item and paid with the order. Commission and cashback stay on product goods only.</p>
    <div class="form-check mb-3">
        <input class="form-check-input" type="checkbox" name="gift_wrap_enabled" value="1" id="giftWrapEnabled" <?= ! empty($gift_wrap_enabled) ? 'checked' : '' ?>>
        <label class="form-check-label" for="giftWrapEnabled">Offer gift wrap</label>
    </div>
    <div class="mb-3">
        <label class="form-label">Gift wrap fee (Rs. per item)</label>
        <input type="number" name="gift_wrap_fee" class="form-control" min="0" step="1" value="<?= esc($gift_wrap_fee ?? 150) ?>">
    </div>
    <hr class="my-4">
    <h5 class="fw-bold mb-1">Where sellers pay COD dues</h5>
    <p class="small text-secondary">Shown in Seller Hub → Pay Solqam. Sellers send commission + cashback here, then upload a screenshot.</p>
    <div class="mb-3">
        <label class="form-label">JazzCash account title</label>
        <input type="text" name="collect_jazzcash_name" class="form-control" value="<?= esc($collect_jazzcash_name ?? '') ?>">
    </div>
    <div class="mb-3">
        <label class="form-label">JazzCash number</label>
        <input type="text" name="collect_jazzcash_number" class="form-control" value="<?= esc($collect_jazzcash_number ?? '') ?>" placeholder="03xxxxxxxxx">
    </div>
    <div class="mb-3">
        <label class="form-label">EasyPaisa account title</label>
        <input type="text" name="collect_easypaisa_name" class="form-control" value="<?= esc($collect_easypaisa_name ?? '') ?>">
    </div>
    <div class="mb-3">
        <label class="form-label">EasyPaisa number</label>
        <input type="text" name="collect_easypaisa_number" class="form-control" value="<?= esc($collect_easypaisa_number ?? '') ?>">
    </div>
    <div class="mb-3">
        <label class="form-label">Bank name</label>
        <input type="text" name="collect_bank_name" class="form-control" value="<?= esc($collect_bank_name ?? '') ?>" placeholder="HBL / Meezan / …">
    </div>
    <div class="mb-3">
        <label class="form-label">Bank account title</label>
        <input type="text" name="collect_bank_title" class="form-control" value="<?= esc($collect_bank_title ?? '') ?>">
    </div>
    <div class="mb-3">
        <label class="form-label">Bank account / IBAN</label>
        <input type="text" name="collect_bank_account" class="form-control" value="<?= esc($collect_bank_account ?? '') ?>">
    </div>
    <button class="btn btn-primary rounded-pill">Save</button>
</form>
<?= $this->endSection() ?>
