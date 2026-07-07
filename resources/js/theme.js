/*
 * resources/js/theme.js
 * Simple light/dark mode toggle, persisted via the settings table (admin-
 * scoped, applies to the whole system, not per-user). Applies a `data-theme`
 * attribute on <html>, which app.css's dark-mode rules key off of.
 *
 * READ is shared (any authenticated role) -> GET /api/settings/theme
 * WRITE is admin-only                     -> PUT /api/admin/settings/theme
 */
(function () {
    function xsrf() {
        return decodeURIComponent((document.cookie.match(/XSRF-TOKEN=([^;]+)/) || [])[1] || '');
    }

    async function apiFetch(path, options = {}) {
        const method = (options.method || 'GET').toUpperCase();
        if (method !== 'GET') {
            await fetch('/sanctum/csrf-cookie', { credentials: 'include' });
        }
        const res = await fetch(path, {
            credentials: 'include',
            ...options,
            method,
            headers: {
                Accept: 'application/json',
                'Content-Type': 'application/json',
                'X-XSRF-TOKEN': xsrf(),
                ...(options.headers || {}),
            },
        });
        const text = await res.text();
        return text ? JSON.parse(text) : null;
    }

    function applyTheme(theme) {
        document.documentElement.setAttribute('data-theme', theme === 'dark' ? 'dark' : 'light');
        localStorage.setItem('rental-theme-cache', theme);
        
        // Update button text if it exists
        const toggle = document.getElementById('theme-toggle');
        const label = document.getElementById('theme-label');
        if (toggle && label) {
            const icon = toggle.querySelector('i');
            if (theme === 'dark') {
                if (icon) icon.className = 'fas fa-sun';
                label.textContent = 'Light Mode';
            } else {
                if (icon) icon.className = 'fas fa-moon';
                label.textContent = 'Dark Mode';
            }
        }
    }

    // DON'T apply cached theme immediately - wait for server or use localStorage only
    // Remove this line to stop auto-applying from cache:
    // if (cached) applyTheme(cached);

    async function loadThemeFromServer() {
        try {
            const data = await apiFetch('/api/settings/theme');
            if (data && data.theme) {
                applyTheme(data.theme);
            } else {
                // If no server theme, check localStorage
                const cached = localStorage.getItem('rental-theme-cache');
                if (cached) {
                    applyTheme(cached);
                }
            }
        } catch (e) {
            // If server fails, use localStorage
            const cached = localStorage.getItem('rental-theme-cache');
            if (cached) {
                applyTheme(cached);
            }
        }
    }

    document.addEventListener('DOMContentLoaded', () => {
        // Load theme from server ONCE when page loads
        loadThemeFromServer();

        const toggle = document.getElementById('theme-toggle');
        if (toggle) {
            toggle.addEventListener('click', async () => {
                const current = document.documentElement.getAttribute('data-theme') || 'light';
                const next = current === 'dark' ? 'light' : 'dark';
                applyTheme(next);
                
                // Try to save to server, but don't reload from server after
                try {
                    await apiFetch('/api/admin/settings/theme', {
                        method: 'PUT',
                        body: JSON.stringify({ theme: next }),
                    });
                } catch (e) {
                    // If saving fails, local storage still works
                    console.log('Theme saved locally only');
                }
            });
        }
    });
})();