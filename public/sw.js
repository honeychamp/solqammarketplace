/* Solqam storefront PWA — cache static files only, never checkout/admin HTML */
const CACHE = 'solqam-pwa-v1';
const PRECACHE = [
  '/manifest.webmanifest',
  '/assets/images/solqam-pwa-192.png',
  '/assets/images/solqam-pwa-512.png',
  '/assets/images/solqam-logo.svg',
  '/assets/css/solqam-premium.css',
];

self.addEventListener('install', (event) => {
  event.waitUntil(
    caches.open(CACHE).then((cache) => cache.addAll(PRECACHE).catch(() => undefined))
  );
  self.skipWaiting();
});

self.addEventListener('activate', (event) => {
  event.waitUntil(
    caches.keys().then((keys) =>
      Promise.all(keys.filter((k) => k !== CACHE).map((k) => caches.delete(k)))
    )
  );
  self.clients.claim();
});

function isBypass(url) {
  const p = url.pathname;
  if (url.method && url.method !== 'GET') return true;
  return (
    p.startsWith('/admin') ||
    p.startsWith('/seller') ||
    p.startsWith('/api') ||
    p.startsWith('/checkout') ||
    p.startsWith('/login') ||
    p.startsWith('/register') ||
    p.startsWith('/auth') ||
    p.startsWith('/payments') ||
    p.startsWith('/feed')
  );
}

self.addEventListener('fetch', (event) => {
  const req = event.request;
  if (req.method !== 'GET') return;

  let url;
  try {
    url = new URL(req.url);
  } catch (e) {
    return;
  }
  if (url.origin !== self.location.origin) return;
  if (isBypass(url)) return;

  if (req.mode === 'navigate') {
    event.respondWith(
      fetch(req).catch(() =>
        caches.match('/') ||
        new Response('Solqam is offline. Check your connection.', {
          headers: { 'Content-Type': 'text/plain; charset=utf-8' },
        })
      )
    );
    return;
  }

  const dest = req.destination;
  const staticAsset = dest === 'style' || dest === 'script' || dest === 'image' || dest === 'font' || url.pathname.startsWith('/assets/');
  if (!staticAsset) return;

  event.respondWith(
    caches.match(req).then((hit) => {
      const fresh = fetch(req)
        .then((res) => {
          if (res && res.ok) {
            const copy = res.clone();
            caches.open(CACHE).then((cache) => cache.put(req, copy));
          }
          return res;
        })
        .catch(() => hit);
      return hit || fresh;
    })
  );
});
