/*
 * resources/js/pwa-register.js
 * Registers the service worker and ensures the offline queue gets replayed
 * reliably on page load and whenever the browser regains connectivity.
 * Uses a direct postMessage trigger (confirmed reliable) rather than relying
 * solely on the Background Sync API.
 */
if ('serviceWorker' in navigator) {
    var swRegistration = null;

    function triggerReplay() {
        if (!navigator.onLine) return;
        var readyPromise = swRegistration ? Promise.resolve(swRegistration) : navigator.serviceWorker.ready;
        readyPromise.then(function (reg) {
            if (reg && reg.active) {
                reg.active.postMessage({ type: 'REPLAY_NOW' });
            }
        });
    }

    window.addEventListener('load', function () {
        navigator.serviceWorker.register('/sw.js').then(function (reg) {
            swRegistration = reg;

            if (reg.sync) {
                reg.sync.register('rental-sync').catch(function () {
                    // Background Sync not supported/permitted; the direct
                    // trigger above is the real mechanism anyway.
                });
            }

            triggerReplay();
        }).catch(function (e) {
            console.error('Service worker registration failed', e);
        });
    });

    window.addEventListener('online', function () {
        triggerReplay();
    });

    navigator.serviceWorker.addEventListener('message', function (event) {
        var data = event.data;
        if (data && data.type === 'REPLAY_RESULT') {
            console.log('Offline queue replay result: ok=' + data.ok + ' status=' + data.status);
        } else if (data && data.type === 'REPLAY_ERROR') {
            console.log('Offline queue replay error: ' + data.message);
        }
    });
}
