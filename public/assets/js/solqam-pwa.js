
(() => {
  if (!('serviceWorker' in navigator)) return;
  const KEY = 'solqam_pwa_dismiss';
  const bar = document.getElementById('solqam-pwa-install');
  const btn = document.getElementById('solqam-pwa-btn');
  const close = document.getElementById('solqam-pwa-close');
  let deferred = null;
  const standalone = window.matchMedia('(display-mode: standalone)').matches
    || window.navigator.standalone === true;

  navigator.serviceWorker.register('/sw.js', { scope: '/' }).catch(() => {});

  if (standalone) return;
  const dismissed = Number(localStorage.getItem(KEY) || 0);
  if (dismissed && Date.now() - dismissed < 14 * 24 * 60 * 60 * 1000) return;

  const show = (iosHint) => {
    if (!bar) return;
    const hint = bar.querySelector('.pwa-hint');
    if (hint) hint.textContent = iosHint
      ? 'Share → Add to Home Screen'
      : 'Home screen pe Solqam kholo';
    bar.classList.add('is-on');
  };

  const hide = () => {
    if (bar) bar.classList.remove('is-on');
    localStorage.setItem(KEY, String(Date.now()));
  };

  window.addEventListener('beforeinstallprompt', (e) => {
    e.preventDefault();
    deferred = e;
    setTimeout(() => show(false), 4000);
  });

  const ios = /iphone|ipad|ipod/i.test(navigator.userAgent);
  if (ios && !standalone) {
    setTimeout(() => show(true), 6000);
  }

  if (btn) {
    btn.addEventListener('click', async () => {
      if (deferred) {
        deferred.prompt();
        try { await deferred.userChoice; } catch (e) {}
        deferred = null;
        hide();
        return;
      }
      if (ios) {
        alert('iPhone: Share button → Add to Home Screen.');
      }
    });
  }
  if (close) close.addEventListener('click', hide);
})();
