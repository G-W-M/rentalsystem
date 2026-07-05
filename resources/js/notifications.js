/*
 * resources/js/notifications.js
 * Polls the unread-notifications count and updates any element with
 * [data-notification-badge]. Also exposes loadNotifications() to render the
 * dropdown list. Depends on api.js.
 */

import { api } from './api.js';

async function refreshBadge() {
    try {
        const data = await api('/api/notifications/unread-count');
        const count = data && typeof data.count === 'number' ? data.count : 0;
        document.querySelectorAll('[data-notification-badge]').forEach((el) => {
            el.textContent = count > 0 ? String(count) : '';
            el.style.display = count > 0 ? 'inline-block' : 'none';
        });
    } catch (e) {
        // Silent: offline or unauthenticated; the badge simply won't update.
    }
}

export async function loadNotifications(targetSelector) {
    const target = document.querySelector(targetSelector);
    if (!target) return;

    try {
        const page = await api('/api/notifications');
        const items = (page && page.data) ? page.data : [];

        if (items.length === 0) {
            target.innerHTML = '<li class="dropdown-item text-muted">No notifications</li>';
            return;
        }

        target.innerHTML = items
            .map((n) => {
                const unread = n.is_read ? '' : ' fw-semibold';
                return (
                    '<li class="dropdown-item' + unread + '" data-id="' + n.id + '">' +
                    '<div class="small text-primary">' + escapeHtml(n.title) + '</div>' +
                    '<div class="text-xs text-gray-600">' + escapeHtml(n.message) + '</div>' +
                    '</li>'
                );
            })
            .join('');
    } catch (e) {
        target.innerHTML = '<li class="dropdown-item text-danger">Failed to load notifications</li>';
    }
}

export async function markAllRead() {
    try {
        await api('/api/notifications/read-all', { method: 'POST' });
        await refreshBadge();
    } catch (e) {
        // no-op
    }
}

function escapeHtml(str) {
    return String(str || '')
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;');
}

document.addEventListener('DOMContentLoaded', () => {
    refreshBadge();
    setInterval(refreshBadge, 60000);
});