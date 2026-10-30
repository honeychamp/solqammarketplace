<link rel="manifest" href="<?= base_url('manifest.webmanifest') ?>">
<meta name="theme-color" content="#0B30E6">
<meta name="mobile-web-app-capable" content="yes">
<meta name="apple-mobile-web-app-capable" content="yes">
<meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
<meta name="apple-mobile-web-app-title" content="Solqam">
<link rel="apple-touch-icon" sizes="180x180" href="<?= base_url('assets/images/solqam-apple-touch.png') ?>">
<style>
#solqam-pwa-install{position:fixed;left:12px;right:12px;bottom:12px;z-index:1080;display:none;align-items:center;gap:12px;padding:12px 14px;border-radius:16px;background:#0B30E6;color:#fff;box-shadow:0 12px 32px rgba(11,48,230,.35)}
#solqam-pwa-install.is-on{display:flex}
#solqam-pwa-install img{width:40px;height:40px;border-radius:10px;background:#fff}
#solqam-pwa-install .pwa-copy{flex:1;min-width:0}
#solqam-pwa-install .pwa-copy strong{display:block;font-size:.92rem}
#solqam-pwa-install .pwa-copy span{display:block;font-size:.75rem;opacity:.85}
#solqam-pwa-install .btn-pwa{border:0;border-radius:999px;background:#fff;color:#0B30E6;font-weight:700;font-size:.8rem;padding:.4rem .9rem}
#solqam-pwa-install .btn-pwa-x{border:0;background:transparent;color:#fff;font-size:1.2rem;line-height:1;padding:0 4px}
</style>
