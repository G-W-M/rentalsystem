/* Rental System service worker — OWNED BY DEVELOPER A (single owner). */
const CACHE = 'rental-shell-v1';
const SHELL = ['/', '/offline.html'];

self.addEventListener('install', (e) => {
  e.waitUntil(caches.open(CACHE).then((c) => c.addAll(SHELL)).then(() => self.skipWaiting()));
});

self.addEventListener('activate', (e) => {
  e.waitUntil(
    caches.keys().then((keys) =>
      Promise.all(keys.filter((k) => k !== CACHE).map((k) => caches.delete(k)))
    ).then(() => self.clients.claim())
  );
});

self.addEventListener('fetch', (event) => {
  const { request } = event;

  // Network-first for API GETs, fall back to cache.
  if (request.url.includes('/api/') && request.method === 'GET') {
    event.respondWith(
      fetch(request)
        .then((res) => {
          const copy = res.clone();
          caches.open(CACHE).then((c) => c.put(request, copy));
          return res;
        })
        .catch(() => caches.match(request))
    );
    return;
  }

  // Queue offline write requests (POST) for background sync.
  if (request.method === 'POST' && request.url.includes('/api/')) {
    event.respondWith(
      fetch(request.clone()).catch(async () => {
        await queueRequest(request.clone());
        return new Response(
          JSON.stringify({ queued: true, message: 'Saved offline. Will sync when online.' }),
          { status: 202, headers: { 'Content-Type': 'application/json' } }
        );
      })
    );
    return;
  }

  // Cache-first for the app shell / static assets.
  event.respondWith(
    caches.match(request).then((cached) => cached || fetch(request).catch(() => caches.match('/offline.html')))
  );
});

// ---- Minimal IndexedDB offline queue ----
function openDB() {
  return new Promise((resolve, reject) => {
    const req = indexedDB.open('rental-queue', 1);
    req.onupgradeneeded = () => req.result.createObjectStore('requests', { autoIncrement: true });
    req.onsuccess = () => resolve(req.result);
    req.onerror = () => reject(req.error);
  });
}

async function queueRequest(request) {
  const body = await request.text();
  const db = await openDB();
  const tx = db.transaction('requests', 'readwrite');
  tx.objectStore('requests').add({
    url: request.url,
    method: request.method,
    body,
    headers: { 'Content-Type': 'application/json', Accept: 'application/json', 'X-Offline-Replay': '1' },
  });
  return tx.complete;
}

self.addEventListener('sync', (event) => {
  if (event.tag === 'rental-sync') event.waitUntil(replayQueue());
});

async function replayQueue() {
  const db = await openDB();
  const tx = db.transaction('requests', 'readwrite');
  const store = tx.objectStore('requests');
  const all = await new Promise((res) => {
    const r = store.getAll();
    r.onsuccess = () => res(r.result);
  });
  for (const item of all) {
    try {
      await fetch(item.url, { method: item.method, body: item.body, headers: item.headers, credentials: 'include' });
    } catch (_) {
      return; // still offline; retry later
    }
  }
  store.clear();
}
