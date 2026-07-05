/*
 * resources/js/pwa-register.js
 * Registers the service worker and requests a background sync when back online.
 * Import from resources/js/app.js:  import './pwa-register';
 */
if ('serviceWorker' in navigator) {
    window.addEventListener('load', async () => {
        try {
            const reg = await navigator.serviceWorker.register('/sw.js');
            window.addEventListener('online', () => {
                if ('sync' in reg) {
                    reg.sync.register('rental-sync').catch(() => {});
                }
            });
        } catch (e) {
            console.error('Service worker registration failed', e);
        }
    });
}
