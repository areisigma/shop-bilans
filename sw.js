const CACHE_NAME = 'shop-bilans-v1';
const urlsToCache = [
  '/shop-bilans/',
  '/shop-bilans/index.php',
  '/shop-bilans/login.php',
  '/shop-bilans/style.css',
  '/shop-bilans/manifest.json'
];

// Instalacja Service Workera
self.addEventListener('install', (event) => {
  event.waitUntil(
    caches.open(CACHE_NAME)
      .then((cache) => {
        console.log('Cache opened');
        return cache.addAll(urlsToCache);
      })
  );
  self.skipWaiting();
});

// Aktywacja Service Workera
self.addEventListener('activate', (event) => {
  event.waitUntil(
    caches.keys().then((cacheNames) => {
      return Promise.all(
        cacheNames.map((cacheName) => {
          if (cacheName !== CACHE_NAME) {
            console.log('Deleting old cache:', cacheName);
            return caches.delete(cacheName);
          }
        })
      );
    })
  );
  self.clients.claim();
});

// Strategia: Network First, fallback to Cache
self.addEventListener('fetch', (event) => {
  // Tylko dla GET requests
  if (event.request.method !== 'GET') {
    return;
  }

  event.respondWith(
    fetch(event.request)
      .then((response) => {
        // Jeśli odpowiedź jest OK, zaktualizuj cache
        if (response && response.status === 200) {
          const responseToCache = response.clone();
          caches.open(CACHE_NAME)
            .then((cache) => {
              cache.put(event.request, responseToCache);
            });
        }
        return response;
      })
      .catch(() => {
        // Jeśli fetch się nie udał, użyj cache
        return caches.match(event.request)
          .then((response) => {
            if (response) {
              return response;
            }
            // Zwróć podstawową odpowiedź offline dla nawigacji
            if (event.request.mode === 'navigate') {
              return caches.match('/shop-bilans/index.php');
            }
          });
      })
  );
});
