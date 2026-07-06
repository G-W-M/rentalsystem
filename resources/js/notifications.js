/*
 * resources/js/notifications.js
 * Reusable notification bell: polls unread count, renders a dropdown list,
 * marks read on click. Included on every authenticated layout. Depends on
 * two DOM elements existing on the page (added to each layout's header):
 *   <span id="notif-badge"></span>
 *   <ul id="notif-list"></ul>
 * Safe to include even if those elements are absent (checks for null).
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
        const body = text ? JSON.parse(text) : null;
        if (!res.ok) throw { message: (body && body.message) || 'Request failed.' };
        return body;
    }

    function escapeHtml(str) {
        return String(str || '')
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;');
    }

    function timeAgo(iso) {
        if (!iso) return '';
        const diffMs = Date.now() - new Date(iso).getTime();
        const mins = Math.floor(diffMs / 60000);
        if (mins < 1) return 'just now';
        if (mins < 60) return mins + 'm ago';
        const hrs = Math.floor(mins / 60);
        if (hrs < 24) return hrs + 'h ago';
        return Math.floor(hrs / 24) + 'd ago';
    }

    async function refreshBadge() {
        const badge = document.getElementById('notif-badge');
        if (!badge) return;
        try {
            const data = await apiFetch('/api/notifications/unread-count');
            const count = (data && typeof data.count === 'number') ? data.count : 0;
            badge.textContent = count > 0 ? String(count) : '';
            badge.style.display = count > 0 ? 'inline-block' : 'none';
        } catch (e) {
            // Silent: offline or unauthenticated; badge just won't update.
        }
    }

    async function loadList() {
        const list = document.getElementById('notif-list');
        if (!list) return;
        list.innerHTML = '<li class="dropdown-item text-muted small">Loading...</li>';
        try {
            const page = await apiFetch('/api/notifications');
            const items = (page && page.data) ? page.data : [];
            if (!items.length) {
                list.innerHTML = '<li class="dropdown-item text-muted small">No notifications</li>';
                return;
            }
            list.innerHTML = items.map((n) => {
                const unreadClass = n.is_read ? '' : ' fw-semibold bg-light';
                return '<li><a class="dropdown-item' + unreadClass + '" href="#" data-notif-id="' + n.id + '">' +
                    '<div class="small">' + escapeHtml(n.title) + '</div>' +
                    '<div class="text-muted" style="font-size:0.75rem;">' + escapeHtml(n.message) + ' &middot; ' + timeAgo(n.created_at) + '</div>' +
                    '</a></li>';
            }).join('');
        } catch (e) {
            list.innerHTML = '<li class="dropdown-item text-danger small">Failed to load</li>';
        }
    }

    document.addEventListener('click', async (ev) => {
        const link = ev.target.closest('[data-notif-id]');
        if (!link) return;
        ev.preventDefault();
        const id = link.getAttribute('data-notif-id');
        try {
            await apiFetch('/api/notifications/' + id + '/read', { method: 'POST' });
            link.classList.remove('fw-semibold', 'bg-light');
            refreshBadge();
        } catch (e) {}
    });

    document.addEventListener('DOMContentLoaded', () => {
        const bellDropdown = document.getElementById('notif-bell');
        if (bellDropdown) {
            bellDropdown.addEventListener('click', loadList);
        }
        refreshBadge();
        setInterval(refreshBadge, 30000);
    });
})();