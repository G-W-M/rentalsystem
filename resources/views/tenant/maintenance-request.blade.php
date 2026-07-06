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
                <div class="card-header bg-white fw-semibold">My Requests</div>
                <div id="requests-error"></div>
                <div class="card-body p-0">
                    <table class="table mb-0 align-middle">
                        <thead><tr><th>Subject</th><th>Status</th><th class="text-end">Action</th></tr></thead>
                        <tbody id="requests-body"><tr><td colspan="3" class="text-muted">Loading...</td></tr></tbody>
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

    async function apiFetch(path, options = {}) {
        const method = (options.method || 'GET').toUpperCase();
        if (method !== 'GET') await fetch('/sanctum/csrf-cookie', { credentials: 'include' });
        const res = await fetch(path, {
            credentials: 'include', ...options, method,
            headers: { 'Accept': 'application/json', 'Content-Type': 'application/json', 'X-XSRF-TOKEN': xsrf(), ...(options.headers || {}) },
        });
        const text = await res.text();
        const body = text ? JSON.parse(text) : null;
        if (!res.ok && res.status !== 202) throw { message: (body && body.message) || 'Request failed.' };
        return body;
    }

    function badge(status) {
        const map = { submitted: 'secondary', assigned: 'info', in_progress: 'primary', resolved: 'success', rejected: 'danger' };
        return '<span class="badge bg-' + (map[status] || 'secondary') + '">' + status + '</span>';
    }

    async function loadRequests() {
        try {
            const requests = await apiFetch('/api/tenant/maintenance');
            const body = document.getElementById('requests-body');
            if (!requests.length) {
                body.innerHTML = '<tr><td colspan="3" class="text-muted">No requests yet</td></tr>';
                return;
            }
            body.innerHTML = requests.map((r) => {
                let action = '<span class="text-muted small">—</span>';
                if (r.task && r.task.is_completed_by_caretaker && !r.task.tenant_confirmed) {
                    action = '<button class="btn btn-sm btn-success" data-confirm="' + r.id + '">Confirm</button>';
                }
                return '<tr><td>' + (r.subject || r.category) + '</td><td>' + badge(r.status) +
                    '</td><td class="text-end">' + action + '</td></tr>';
            }).join('');
        } catch (e) {
            document.getElementById('requests-error').innerHTML = '<div class="alert alert-danger m-2">' + e.message + '</div>';
        }
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
                out.innerHTML = '<div class="alert alert-warning">Saved offline. Will submit when you reconnect.</div>';
            } else {
                out.innerHTML = '<div class="alert alert-success">Request submitted.</div>';
            }
            document.getElementById('subject').value = '';
            document.getElementById('description').value = '';
            loadRequests();
        } catch (e) {
            out.innerHTML = '<div class="alert alert-danger">' + e.message + '</div>';
        }
    });

    document.getElementById('requests-body').addEventListener('click', async (ev) => {
        const confirmId = ev.target.getAttribute('data-confirm');
        if (!confirmId) return;
        try {
            await apiFetch('/api/tenant/maintenance/' + confirmId + '/confirm', { method: 'POST' });
            loadRequests();
        } catch (e) {
            document.getElementById('requests-error').innerHTML = '<div class="alert alert-danger m-2">' + e.message + '</div>';
        }
    });

    loadRequests();
</script>
@endpush
