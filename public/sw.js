/* Rental System service worker */
const CACHE = 'rental-shell-v2';
const SHELL = ['/', '/offline.html'];

self.addEventListener('install', (e) => {
  e.waitUntil(
    caches.open(CACHE).then((cache) => {
      return Promise.allSettled(
        SHELL.map((url) => fetch(url).then((res) => {
          if (res.ok) return cache.put(url, res);
        }).catch(() => {}))
      );
    }).then(() => self.skipWaiting())
  );
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

  event.respondWith(
    caches.match(request).then((cached) => cached || fetch(request).catch(() => caches.match('/offline.html')))
  );
});

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

self.addEventListener('message', (event) => {
  if (event.data && event.data.type === 'REPLAY_NOW') {
    event.waitUntil(replayQueue());
  }
});

/**
 * Replays every queued offline request against the real server.
 *
 * Outcome per item:
 *   - Success (2xx)           -> remove from queue, report REPLAY_RESULT ok:true
 *   - Client error (4xx)      -> PERMANENT failure (bad data / business rule
 *                                 rejection like duplicate/validation) -> remove
 *                                 from queue (retrying won't help), report
 *                                 REPLAY_RESULT ok:false with the server's message
 *   - Server error (5xx)      -> TEMPORARY failure -> keep in queue, stop this
 *                                 run, retry on next sync/trigger
 *   - Network failure (fetch  -> TEMPORARY (still offline / connection issue)
 *     throws)                    -> keep in queue, stop this run, retry later
 */
async function replayQueue() {
  const db = await openDB();
  const clients = await self.clients.matchAll();

  // Read the current items with their keys so we can delete by key.
  const items = await new Promise((resolve, reject) => {
    const tx = db.transaction('requests', 'readonly');
    const store = tx.objectStore('requests');
    const result = [];
    const cursorReq = store.openCursor();
    cursorReq.onsuccess = (e) => {
      const cursor = e.target.result;
      if (cursor) {
        result.push({ key: cursor.key, value: cursor.value });
        cursor.continue();
      } else {
        resolve(result);
      }
    };
    cursorReq.onerror = () => reject(cursorReq.error);
  });

  for (const { key, value: item } of items) {
    try {
      const res = await fetch(item.url, {
        method: item.method,
        body: item.body,
        headers: item.headers,
        credentials: 'include',
      });
      const text = await res.text();

      if (res.ok) {
        await deleteQueueItem(db, key);
        clients.forEach((c) => c.postMessage({
          type: 'REPLAY_RESULT', ok: true, status: res.status, body: text, url: item.url,
        }));
        continue;
      }

      if (res.status >= 400 && res.status < 500) {
        // Permanent rejection (validation, business rule, auth). Retrying
        // this exact payload will never succeed, so drop it from the queue.
        await deleteQueueItem(db, key);
        clients.forEach((c) => c.postMessage({
          type: 'REPLAY_RESULT', ok: false, permanent: true, status: res.status, body: text, url: item.url,
        }));
        continue;
      }

      // 5xx: temporary server-side problem. Keep it queued, stop for now.
      clients.forEach((c) => c.postMessage({
        type: 'REPLAY_RESULT', ok: false, permanent: false, status: res.status, body: text, url: item.url,
      }));
      return;
    } catch (err) {
      // Network failure — still offline or connection dropped mid-replay.
      // Keep it queued, stop for now.
      clients.forEach((c) => c.postMessage({
        type: 'REPLAY_ERROR', message: String(err), url: item.url,
      }));
      return;
    }
  }
}

function deleteQueueItem(db, key) {
  return new Promise((resolve, reject) => {
    const tx = db.transaction('requests', 'readwrite');
    const req = tx.objectStore('requests').delete(key);
    req.onsuccess = resolve;
    req.onerror = () => reject(req.error);
  });
}
