// PharmaLink - Servis Çalışanı (Service Worker) - PASİF MOD
// Bu dosya ağ isteklerine müdahale etmeyecek şekilde sıfırlanmıştır.

self.addEventListener('install', (event) => {
  self.skipWaiting();
});

self.addEventListener('activate', (event) => {
  event.waitUntil(
    caches.keys().then((cacheNames) => {
      return Promise.all(
        cacheNames.map((cacheName) => caches.delete(cacheName))
      );
    }).then(() => self.clients.claim())
  );
});

// Fetch listener'ı tamamen kaldırılarak tarayıcının standart ağ yönetimine dönmesi sağlanmıştır.
