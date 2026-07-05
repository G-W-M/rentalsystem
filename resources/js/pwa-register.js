// Import this from resources/js/app.js:  import './pwa-register';
// Registers the service worker and requests a background sync when back online.
if ('serviceWorker' in navigator) {
  window.addEventListener('load', async () => {
    try {
      const reg = await navigator.serviceWorker.register('/sw.js');
      // Trigger replay of any queued offline writes when connectivity returns.
      window.addEventListener('online', () => {
        if ('sync' in reg) reg.sync.register('rental-sync').catch(() => {});
      });
    } catch (e) {
      console.error('SW registration failed', e);
    }
  });
}

// Same-origin API helper: gets CSRF cookie then sends credentials.
export async function api(path, options = {}) {
  await fetch('/sanctum/csrf-cookie', { credentials: 'include' });
  return fetch(path, {
    credentials: 'include',
    headers: { 'Accept': 'application/json', 'Content-Type': 'application/json',
      'X-XSRF-TOKEN': decodeURIComponent((document.cookie.match(/XSRF-TOKEN=([^;]+)/) || [])[1] || '') },
    ...options,
  });
}
