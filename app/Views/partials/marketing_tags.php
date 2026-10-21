<?php
try {
    $mkt = \App\Services\Marketing\MarketingService::publicConfig();
    $mktQueue = \App\Services\Marketing\MarketingService::pullBrowserQueue();
} catch (\Throwable $e) {
    $mkt = ['enabled' => false];
    $mktQueue = [];
}
if (empty($mkt['enabled'])) {
    return;
}
$userEm = '';
$userPh = '';
try {
    $uid = (int) (session()->get('user.id') ?? 0);
    if ($uid) {
        $userEm = \App\Services\Marketing\MarketingService::hashPii((string) session()->get('user.email'));
        $userPh = \App\Services\Marketing\MarketingService::hashPii(
            \App\Services\Marketing\MarketingService::normalizePkPhone((string) session()->get('user.phone'))
        );
    }
} catch (\Throwable $e) {
}
$gtm   = esc($mkt['gtm_id'] ?? '', 'attr');
$ga4   = esc($mkt['ga4_id'] ?? '', 'attr');
$aw    = esc($mkt['google_ads_id'] ?? '', 'attr');
$awLbl = esc($mkt['google_ads_label'] ?? '', 'attr');
$meta  = esc($mkt['meta_pixel_id'] ?? '', 'attr');
$tt    = esc($mkt['tiktok_pixel_id'] ?? '', 'attr');
?>
<script>window.dataLayer = window.dataLayer || [];</script>
<?php if ($gtm !== ''): ?>
<script>(function(w,d,s,l,i){w[l]=w[l]||[];w[l].push({'gtm.start':new Date().getTime(),event:'gtm.js'});var f=d.getElementsByTagName(s)[0],j=d.createElement(s),dl=l!='dataLayer'?'&l='+l:'';j.async=true;j.src='https://www.googletagmanager.com/gtm.js?id='+i+dl;f.parentNode.insertBefore(j,f);})(window,document,'script','dataLayer','<?= $gtm ?>');</script>
<?php endif; ?>
<?php if ($ga4 !== '' || $aw !== ''): ?>
<script async src="https://www.googletagmanager.com/gtag/js?id=<?= $ga4 !== '' ? $ga4 : $aw ?>"></script>
<script>
window.gtag=window.gtag||function(){dataLayer.push(arguments);};
gtag('js', new Date());
<?php if ($ga4 !== ''): ?>gtag('config', '<?= $ga4 ?>');<?php endif; ?>
<?php if ($aw !== ''): ?>gtag('config', '<?= $aw ?>');<?php endif; ?>
</script>
<?php endif; ?>
<?php if ($meta !== ''): ?>
<script>
!function(f,b,e,v,n,t,s){if(f.fbq)return;n=f.fbq=function(){n.callMethod?n.callMethod.apply(n,arguments):n.queue.push(arguments)};if(!f._fbq)f._fbq=n;n.push=n;n.loaded=!0;n.version='2.0';n.queue=[];t=b.createElement(e);t.async=!0;t.src=v;s=b.getElementsByTagName(e)[0];s.parentNode.insertBefore(t,s)}(window,document,'script','https://connect.facebook.net/en_US/fbevents.js');
fbq('init', '<?= $meta ?>'<?= $userEm || $userPh ? ", {em:'" . esc($userEm, 'js') . "',ph:'" . esc($userPh, 'js') . "'}" : '' ?>);
fbq('track', 'PageView');
</script>
<noscript><img height="1" width="1" style="display:none" src="https://www.facebook.com/tr?id=<?= $meta ?>&ev=PageView&noscript=1" alt=""></noscript>
<?php endif; ?>
<?php if ($tt !== ''): ?>
<script>
!function(w,d,t){w.TiktokAnalyticsObject=t;var ttq=w[t]=w[t]||[];ttq.methods=["page","track","identify","instances","debug","on","off","once","ready","alias","group","enableCookie","disableCookie"];ttq.setAndDefer=function(obj,m){obj[m]=function(){obj.push([m].concat(Array.prototype.slice.call(arguments,0)))}};for(var i=0;i<ttq.methods.length;i++)ttq.setAndDefer(ttq,ttq.methods[i]);ttq.instance=function(id){var e=ttq._i[id]||[];ttq.methods.forEach(function(m){ttq.setAndDefer(e,m)});return e};ttq.load=function(id){ttq._i=ttq._i||{};ttq._i[id]=[];ttq._i[id]._u='https://analytics.tiktok.com/i18n/pixel/events.js';var n=document.createElement('script');n.type='text/javascript';n.async=!0;n.src=ttq._i[id]._u+'?sdkid='+id+'&lib=ttq';var a=document.getElementsByTagName('script')[0];a.parentNode.insertBefore(n,a)};ttq.load('<?= $tt ?>');ttq.page();}(window,document,'ttq');
<?php if ($userEm || $userPh): ?>ttq.identify({email:'<?= esc($userEm, 'js') ?>',phone_number:'<?= esc($userPh, 'js') ?>'});<?php endif; ?>
</script>
<?php endif; ?>
<script>
window.SOLQAM_MKT = {
  currency: <?= json_encode($mkt['currency'] ?? 'PKR') ?>,
  adsId: <?= json_encode($mkt['google_ads_id'] ?? '') ?>,
  adsLabel: <?= json_encode($mkt['google_ads_label'] ?? '') ?>,
  consentRequired: <?= !empty($mkt['consent_required']) ? 'true' : 'false' ?>,
  queue: <?= json_encode($mktQueue) ?>
};
</script>
<script src="<?= base_url('assets/js/solqam-marketing.js') ?>?v=20261019c" defer></script>
