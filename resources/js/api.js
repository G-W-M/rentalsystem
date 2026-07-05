/*
 * resources/js/api.js
 * Same-origin authenticated fetch helper for the Rental System PWA.
 * Relies on Sanctum cookie auth: it primes the CSRF cookie, then sends
 * credentials with every request. Import { api } wherever you call the API.
 */

let csrfPrimed = false;

async function primeCsrf() {
    if (csrfPrimed) return;
    await fetch('/sanctum/csrf-cookie', { credentials: 'include' });
    csrfPrimed = true;
}

function xsrfToken() {
    const match = document.cookie.match(/XSRF-TOKEN=([^;]+)/);
    return match ? decodeURIComponent(match[1]) : '';
}

export async function api(path, options = {}) {
    const method = (options.method || 'GET').toUpperCase();

    if (method !== 'GET') {
        await primeCsrf();
    }

    const headers = {
        Accept: 'application/json',
        'Content-Type': 'application/json',
        'X-XSRF-TOKEN': xsrfToken(),
        ...(options.headers || {}),
    };

    const response = await fetch(path, {
        credentials: 'include',
        ...options,
        method,
        headers,
    });

    let body = null;
    const text = await response.text();
    if (text) {
        try {
            body = JSON.parse(text);
        } catch (e) {
            body = text;
        }
    }

    if (!response.ok) {
        const message = (body && body.message) ? body.message : 'Request failed (' + response.status + ').';
        throw { status: response.status, message, body };
    }

    return body;
}

export function renderError(container, error) {
    if (!container) return;
    const msg = (error && error.message) ? error.message : 'Something went wrong.';
    container.innerHTML =
        '<div class="alert alert-danger" role="alert">' + msg + '</div>';
}

export function money(value) {
    const n = Number(value || 0);
    return n.toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 });
}