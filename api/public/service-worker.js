/* ===============================
   SERVICE WORKER - OFFLINE CACHE
   Railway + React + Laravel
================================ */

const PRECACHE = 'precache-v1';
const RUNTIME = 'runtime-v1';

const PRECACHE_URLS = [
  '/',
  '/index.html',
  '/offline.html'
];

// Evitar cachear endpoints sensibles
const isAuthEndpoint = (url) =>
  /\/(login|register|auth|logout|oauth)/i.test(url);

// INSTALL
self.addEventListener('install', (event) => {
  console.log('[SW] Instalando...');
  self.skipWaiting();
  event.waitUntil(
    caches.open(PRECACHE).then(cache => cache.addAll(PRECACHE_URLS))
  );
});

// ACTIVATE
self.addEventListener('activate', (event) => {
  console.log('[SW] Activado');
  event.waitUntil(
    caches.keys().then(keys =>
      Promise.all(
        keys
          .filter(k => ![PRECACHE, RUNTIME].includes(k))
          .map(k => caches.delete(k))
      )
    )
  );
  self.clients.claim();
});

// FETCH
self.addEventListener('fetch', (event) => {
  const { request } = event;

  // Solo GET
  if (request.method !== 'GET') return;

  const url = new URL(request.url);

  // 🧭 Navegación SPA
  if (request.mode === 'navigate') {
    event.respondWith(
      fetch(request)
        .then(res => res)
        .catch(() =>
          caches.match('/index.html').then(r => r || caches.match('/offline.html'))
        )
    );
    return;
  }

  // 🖼️ Imágenes (portadas)
  if (request.destination === 'image') {
    event.respondWith(cacheFirst(request));
    return;
  }

  // 🌐 API (GET)
  if (url.pathname.startsWith('/api') || url.pathname.includes('/public/tomos')) {
    if (isAuthEndpoint(url.pathname)) {
      event.respondWith(fetch(request));
      return;
    }
    event.respondWith(networkFirst(request));
    return;
  }

  // 📦 Assets estáticos
  event.respondWith(staleWhileRevalidate(request));
});

// ==================
// Estrategias
// ==================

async function cacheFirst(request) {
  const cache = await caches.open(RUNTIME);
  const cached = await cache.match(request);
  if (cached) return cached;

  try {
    const fresh = await fetch(request);
    if (fresh.status === 200) {
      cache.put(request, fresh.clone());
    }
    return fresh;
  } catch {
    return caches.match('/offline.html');
  }
}

async function networkFirst(request) {
  const cache = await caches.open(RUNTIME);
  try {
    const fresh = await fetch(request);
    if (fresh.status === 200) {
      cache.put(request, fresh.clone());
    }
    return fresh;
  } catch {
    return cache.match(request) || caches.match('/offline.html');
  }
}

async function staleWhileRevalidate(request) {
  const cache = await caches.open(RUNTIME);
  const cached = await cache.match(request);

  const networkFetch = fetch(request).then(response => {
    if (response.status === 200) {
      cache.put(request, response.clone());
    }
    return response;
  });

  return cached || networkFetch;
}
