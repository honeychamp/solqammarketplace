<?php
try {
    $mkt = \App\Services\Marketing\MarketingService::publicConfig();
} catch (\Throwable $e) {
    $mkt = ['enabled' => false];
}
if (empty($mkt['enabled'])) {
    return;
}
if (! empty($mkt['gtm_id'])): ?>
<noscript><iframe src="https://www.googletagmanager.com/ns.html?id=<?= esc($mkt['gtm_id'], 'attr') ?>" height="0" width="0" style="display:none;visibility:hidden" title="gtm"></iframe></noscript>
<?php endif; ?>
<?php if (! empty($mkt['consent_required'])): ?>
<div id="solqam-ads-consent" class="position-fixed bottom-0 start-0 end-0 p-3 bg-dark text-white d-none" style="z-index:2500">
    <div class="container d-flex flex-wrap gap-2 align-items-center justify-content-between">
        <span class="small">Solqam uses ads cookies (Meta, TikTok, Google) to measure campaigns.</span>
        <div class="d-flex gap-2">
            <button type="button" class="btn btn-sm btn-outline-light" data-ads="0">Decline</button>
            <button type="button" class="btn btn-sm btn-warning" data-ads="1">Allow</button>
        </div>
    </div>
</div>
<?php endif; ?>
