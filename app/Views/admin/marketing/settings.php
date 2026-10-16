<?= $this->extend('layouts/admin') ?>
<?= $this->section('content') ?>
<?php $s = $settings ?? []; $masks = $masks ?? []; ?>
<div class="mb-4 d-flex justify-content-between align-items-start flex-wrap gap-2">
    <div>
        <h4 class="fw-bold mb-1">Ads tracking (Meta, TikTok, Google)</h4>
        <p class="text-secondary small mb-0">Paste pixel IDs and <strong>server tokens</strong> here. Storefront JS never sees CAPI / Events API tokens. Currency is PKR. Same <code>event_id</code> is used on browser + server so purchases are not double-counted.</p>
    </div>
    <a class="btn btn-outline-dark btn-sm rounded-pill" href="<?= site_url('admin/marketing/logs') ?>">Event logs</a>
</div>

<div class="alert alert-info rounded-4 border-0">
    Leave a token field empty to keep the saved value. Turn <strong>Enable tracking</strong> on only after IDs are filled. Use <strong>Test mode</strong> plus Meta Test Event Code while you verify Events Manager.
</div>

<form method="POST" action="<?= site_url('admin/marketing') ?>" class="card-custom p-4">
    <?= csrf_field() ?>
    <div class="row g-3 mb-4">
        <div class="col-md-3">
            <div class="form-check form-switch">
                <input class="form-check-input" type="checkbox" name="enabled" value="1" id="en" <?= !empty($s['enabled']) ? 'checked' : '' ?>>
                <label class="form-check-label fw-semibold" for="en">Enable tracking</label>
            </div>
        </div>
        <div class="col-md-3">
            <div class="form-check form-switch">
                <input class="form-check-input" type="checkbox" name="test_mode" value="1" id="tm" <?= !empty($s['test_mode']) ? 'checked' : '' ?>>
                <label class="form-check-label" for="tm">Test mode</label>
            </div>
        </div>
        <div class="col-md-3">
            <div class="form-check form-switch">
                <input class="form-check-input" type="checkbox" name="consent_required" value="1" id="cr" <?= !empty($s['consent_required']) ? 'checked' : '' ?>>
                <label class="form-check-label" for="cr">Cookie consent bar</label>
            </div>
        </div>
        <div class="col-md-3">
            <div class="form-check form-switch">
                <input class="form-check-input" type="checkbox" name="server_pageview" value="1" id="sp" <?= !empty($s['server_pageview']) ? 'checked' : '' ?>>
                <label class="form-check-label" for="sp">Server PageView</label>
            </div>
        </div>
        <div class="col-md-2">
            <label class="form-label small">Currency</label>
            <input name="currency" class="form-control" value="<?= esc($s['currency'] ?? 'PKR') ?>" maxlength="8">
        </div>
    </div>

    <h6 class="fw-bold text-uppercase small text-muted">Meta (Facebook / Instagram)</h6>
    <div class="row g-3 mb-4">
        <div class="col-md-4">
            <label class="form-label small">Pixel ID</label>
            <input name="meta_pixel_id" class="form-control" value="<?= esc($s['meta_pixel_id'] ?? '') ?>" placeholder="1234567890">
        </div>
        <div class="col-md-5">
            <label class="form-label small">Conversions API token <?= !empty($masks['meta_capi_token']) ? '<span class="text-muted">saved: ' . esc($masks['meta_capi_token']) . '</span>' : '' ?></label>
            <input type="password" name="meta_capi_token" class="form-control" autocomplete="new-password" placeholder="Paste new token to replace">
        </div>
        <div class="col-md-3">
            <label class="form-label small">Test event code</label>
            <input name="meta_test_event_code" class="form-control" value="<?= esc($s['meta_test_event_code'] ?? '') ?>" placeholder="TEST12345">
        </div>
    </div>

    <h6 class="fw-bold text-uppercase small text-muted">TikTok</h6>
    <div class="row g-3 mb-4">
        <div class="col-md-4">
            <label class="form-label small">Pixel ID</label>
            <input name="tiktok_pixel_id" class="form-control" value="<?= esc($s['tiktok_pixel_id'] ?? '') ?>">
        </div>
        <div class="col-md-5">
            <label class="form-label small">Events API access token <?= !empty($masks['tiktok_api_token']) ? '<span class="text-muted">saved: ' . esc($masks['tiktok_api_token']) . '</span>' : '' ?></label>
            <input type="password" name="tiktok_api_token" class="form-control" autocomplete="new-password" placeholder="Paste new token to replace">
        </div>
        <div class="col-md-3">
            <label class="form-label small">App ID (optional)</label>
            <input name="tiktok_app_id" class="form-control" value="<?= esc($s['tiktok_app_id'] ?? '') ?>">
        </div>
    </div>

    <h6 class="fw-bold text-uppercase small text-muted">Google (GTM → GA4 / Ads)</h6>
    <div class="row g-3 mb-4">
        <div class="col-md-3">
            <label class="form-label small">GTM container</label>
            <input name="gtm_id" class="form-control" value="<?= esc($s['gtm_id'] ?? '') ?>" placeholder="GTM-XXXX">
        </div>
        <div class="col-md-3">
            <label class="form-label small">GA4 measurement ID</label>
            <input name="ga4_id" class="form-control" value="<?= esc($s['ga4_id'] ?? '') ?>" placeholder="G-XXXX">
        </div>
        <div class="col-md-3">
            <label class="form-label small">Google Ads ID</label>
            <input name="google_ads_id" class="form-control" value="<?= esc($s['google_ads_id'] ?? '') ?>" placeholder="AW-XXXX">
        </div>
        <div class="col-md-3">
            <label class="form-label small">Ads conversion label</label>
            <input name="google_ads_label" class="form-control" value="<?= esc($s['google_ads_label'] ?? '') ?>">
        </div>
    </div>

    <div class="d-flex gap-2 flex-wrap">
        <button class="btn btn-primary px-4">Save tracking</button>
    </div>
</form>

<form method="POST" action="<?= site_url('admin/marketing/test') ?>" class="mt-3">
    <?= csrf_field() ?>
    <button class="btn btn-outline-primary btn-sm">Send test ViewContent now</button>
</form>
<?= $this->endSection() ?>
