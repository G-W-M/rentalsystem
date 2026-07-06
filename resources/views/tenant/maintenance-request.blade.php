@extends('layouts.tenant')

@section('title', 'Maintenance')

@section('content')
<div class="p-4">
    <h1 class="text-2xl font-bold text-primary mb-4">Maintenance</h1>

    <div class="row g-4">
        <div class="col-12 col-lg-5">
            <div class="card shadow-sm rounded-lg border-0">
                <div class="card-header bg-white fw-semibold">Raise a Request</div>
                <div class="card-body">
                    <div id="submit-result"></div>
                    <div class="mb-3">
                        <label class="form-label">Category</label>
                        <select class="form-select" id="category">
                            <option value="plumbing">Plumbing</option>
                            <option value="electrical">Electrical</option>
                            <option value="structural">Structural</option>
                            <option value="appliance">Appliance</option>
                            <option value="pest">Pest</option>
                            <option value="security">Security</option>
                            <option value="other">Other</option>
                        </select>
                    </div>
                    <div class="mb-3"><label class="form-label">Subject</label><input type="text" class="form-control" id="subject"></div>
                    <div class="mb-3">
                        <label class="form-label">Priority</label>
                        <select class="form-select" id="priority">
                            <option value="low">Low</option>
                            <option value="medium" selected>Medium</option>
                            <option value="high">High</option>
                            <option value="emergency">Emergency</option>
                        </select>
                    </div>
                    <div class="mb-3"><label class="form-label">Description</label><textarea class="form-control" id="description" rows="3"></textarea></div>
                    <button class="btn btn-primary w-100" id="submit-request">Submit Request</button>
                </div>
            </div>
        </div>

        <div class="col-12 col-lg-7">
            <div class="card shadow-sm rounded-lg border-0">
                <div class="card-header bg-white fw-semibold">My Requests (this session)</div>
                <div class="card-body p-0">
                    <table class="table mb-0 align-middle">
                        <thead><tr><th>Subject</th><th>Status</th><th class="text-end">Action</th></tr></thead>
                        <tbody id="requests-body"><tr><td colspan="3" class="text-muted">No requests yet</td></tr></tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    function xsrf() { return decodeURIComponent((document.cookie.match(/XSRF-TOKEN=([^;]+)/) || [])[1] || ''); }

    // Prime the CSRF cookie ONCE per page load, and never let a failure here
    // (e.g. offline) block the actual submission — we fall back to whatever
    // XSRF-TOKEN cookie already exists.
    let csrfPrimed = false;
    async function primeCsrfOnce() {
        if (csrfPrimed) return;
        try {
            await fetch('/sanctum/csrf-cookie', { credentials: 'include' });
        } catch (e) {
            // offline or network error — proceed with existing cookie, if any
        }
        csrfPrimed = true;
    }

    async function apiFetch(path, options = {}) {
        const method = (options.method || 'GET').toUpperCase();
        if (method !== 'GET') {
            await primeCsrfOnce();
        }

        let res;
        try {
            res = await fetch(path, {
                credentials: 'include',
                ...options,
                method,
                headers: {
                    'Accept': 'application/json',
                    'Content-Type': 'application/json',
                    'X-XSRF-TOKEN': xsrf(),
                    ...(options.headers || {}),
                },
            });
        } catch (networkError) {
            // The request never reached the network at all (fully offline,
            // no service worker interception possible). Surface a clear,
            // app-level message instead of letting this bubble as a generic error.
            throw { message: 'You appear to be offline. This will be saved and sent when you reconnect.', offline: true };
        }

        const text = await res.text();
        const body = text ? JSON.parse(text) : null;

        if (!res.ok && res.status !== 202) {
            throw { message: (body && body.message) || 'Request failed.' };
        }

        return body;
    }

    const submitted = [];

    function renderRequests() {
        const bodyEl = document.getElementById('requests-body');
        if (!submitted.length) { bodyEl.innerHTML = '<tr><td colspan="3" class="text-muted">No requests yet</td></tr>'; return; }
        bodyEl.innerHTML = submitted.map((r) => {
            let action = '<span class="text-muted small">—</span>';
            if (r.status === 'awaiting_confirm') action = '<button class="btn btn-sm btn-success" data-confirm="' + r.id + '">Confirm</button>';
            return '<tr><td>' + (r.subject || '-') + '</td><td><span class="badge bg-secondary">' + r.status +
                '</span></td><td class="text-end">' + action + '</td></tr>';
        }).join('');
    }

    document.getElementById('submit-request').addEventListener('click', async () => {
        const out = document.getElementById('submit-result');
        out.innerHTML = '';
        try {
            const res = await apiFetch('/api/tenant/maintenance', {
                method: 'POST',
                body: JSON.stringify({
                    category: document.getElementById('category').value,
                    subject: document.getElementById('subject').value,
                    priority: document.getElementById('priority').value,
                    description: document.getElementById('description').value,
                }),
            });

            if (res && res.queued) {
                out.innerHTML = '<div class="alert alert-warning">Saved offline. Will submit automatically when you reconnect.</div>';
            } else {
                out.innerHTML = '<div class="alert alert-success">Request submitted.</div>';
                submitted.unshift({ id: res.id, subject: res.subject, status: res.status });
                renderRequests();
            }
            document.getElementById('subject').value = '';
            document.getElementById('description').value = '';
        } catch (e) {
            const cls = e.offline ? 'alert-warning' : 'alert-danger';
            out.innerHTML = '<div class="alert ' + cls + '">' + e.message + '</div>';
        }
    });

    document.getElementById('requests-body').addEventListener('click', async (ev) => {
        const confirmId = ev.target.getAttribute('data-confirm');
        if (!confirmId) return;
        try {
            await apiFetch('/api/tenant/maintenance/' + confirmId + '/confirm', { method: 'POST' });
            const item = submitted.find((r) => String(r.id) === String(confirmId));
            if (item) item.status = 'resolved';
            renderRequests();
        } catch (e) {
            document.getElementById('submit-result').innerHTML = '<div class="alert alert-danger">' + e.message + '</div>';
        }
    });

    renderRequests();
</script>
@endpush
