@extends('layouts.landlord')

@section('title', 'Maintenance')

@section('content')
<div class="p-4">
    <h1 class="text-2xl font-bold text-primary mb-4">Maintenance Requests</h1>
    <div id="maint-error"></div>
<div class="mb-3">
    <a href="/api/landlord/maintenance/export/csv" class="btn btn-sm btn-outline-secondary" target="_blank">
        <i class="fas fa-file-csv me-1"></i> Export CSV
    </a>
    <a href="/api/landlord/maintenance/export/pdf" class="btn btn-sm btn-outline-secondary" target="_blank">
        <i class="fas fa-file-pdf me-1"></i> Export PDF
    </a>
</div>
    <div class="card shadow-sm rounded-lg border-0 mb-3">
        <div class="card-body">
            <div class="row g-2">
                <div class="col-auto">
                    <select class="form-select form-select-sm" id="filter-status">
                        <option value="">All Statuses</option>
                        <option value="submitted">Submitted</option>
                        <option value="assigned">Assigned</option>
                        <option value="in_progress">In Progress</option>
                        <option value="resolved">Resolved</option>
                        <option value="rejected">Rejected</option>
                    </select>
                </div>
                <div class="col-auto">
                    <select class="form-select form-select-sm" id="filter-priority">
                        <option value="">All Priorities</option>
                        <option value="low">Low</option>
                        <option value="medium">Medium</option>
                        <option value="high">High</option>
                        <option value="emergency">Emergency</option>
                    </select>
                </div>
                <div class="col-auto">
                    <button class="btn btn-sm btn-outline-secondary" id="filter-apply">Filter</button>
                </div>
            </div>
        </div>
    </div>

    <div class="card shadow-sm rounded-lg border-0">
        <div class="card-body p-0">
            <table class="table mb-0 align-middle">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Tenant</th>
                        <th>Property / Unit</th>
                        <th>Subject</th>
                        <th>Priority</th>
                        <th>Status</th>
                        <th class="text-end">Action</th>
                    </tr>
                </thead>
                <tbody id="maint-body">
                    <tr><td colspan="7" class="text-muted">Loading...</td></tr>
                </tbody>
            </table>
        </div>
    </div>
</div>

<div class="modal fade" id="rejectModal" tabindex="-1">
    <div class="modal-dialog"><div class="modal-content">
        <div class="modal-header"><h5 class="modal-title">Reject Request</h5><button class="btn-close" data-bs-dismiss="modal"></button></div>
        <div class="modal-body">
            <input type="hidden" id="reject-id">
            <div id="reject-error"></div>
            <div class="mb-3"><label class="form-label">Reason</label><textarea class="form-control" id="reject-notes" rows="3"></textarea></div>
        </div>
        <div class="modal-footer"><button class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button><button class="btn btn-danger" id="reject-submit">Reject</button></div>
    </div></div>
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
        if (!res.ok) throw { message: (body && body.message) || 'Request failed.' };
        return body;
    }

    function badge(status) {
        const map = { submitted: 'secondary', assigned: 'info', in_progress: 'primary', resolved: 'success', rejected: 'danger' };
        return '<span class="badge bg-' + (map[status] || 'secondary') + '">' + status + '</span>';
    }

    async function load() {
        try {
            const status = document.getElementById('filter-status').value;
            const priority = document.getElementById('filter-priority').value;
            const params = new URLSearchParams();
            if (status) params.set('status', status);
            if (priority) params.set('priority', priority);

            const page = await apiFetch('/api/landlord/maintenance?' + params.toString());
            const items = page.data || [];
            const body = document.getElementById('maint-body');

            if (!items.length) {
                body.innerHTML = '<tr><td colspan="7" class="text-muted">No maintenance requests found</td></tr>';
                return;
            }

            body.innerHTML = items.map((m) => {
                const tenantName = m.tenant && m.tenant.user ? m.tenant.user.full_name : '-';
                const propUnit = (m.property ? m.property.name : '-') + (m.unit ? ' / ' + m.unit.unit_number : '');
                let action = '<span class="text-muted small">—</span>';
                if (m.status === 'submitted') {
                    action = '<button class="btn btn-sm btn-success me-1" data-approve="' + m.id + '">Approve</button>' +
                        '<button class="btn btn-sm btn-danger" data-reject="' + m.id + '">Reject</button>';
                }
                return '<tr><td>' + m.id + '</td><td>' + tenantName + '</td><td>' + propUnit + '</td><td>' +
                    (m.subject || m.category) + '</td><td>' + m.priority + '</td><td>' + badge(m.status) +
                    '</td><td class="text-end">' + action + '</td></tr>';
            }).join('');
        } catch (e) {
            document.getElementById('maint-error').innerHTML = '<div class="alert alert-danger">' + e.message + '</div>';
        }
    }

    document.getElementById('filter-apply').addEventListener('click', load);

    document.getElementById('maint-body').addEventListener('click', async (ev) => {
        const approveId = ev.target.getAttribute('data-approve');
        const rejectId = ev.target.getAttribute('data-reject');

        if (approveId) {
            try {
                await apiFetch('/api/landlord/maintenance/' + approveId + '/approve', { method: 'POST' });
                load();
            } catch (e) {
                document.getElementById('maint-error').innerHTML = '<div class="alert alert-danger">' + e.message + '</div>';
            }
        }

        if (rejectId) {
            document.getElementById('reject-id').value = rejectId;
            document.getElementById('reject-notes').value = '';
            document.getElementById('reject-error').innerHTML = '';
            new bootstrap.Modal(document.getElementById('rejectModal')).show();
        }
    });

    document.getElementById('reject-submit').addEventListener('click', async () => {
        const id = document.getElementById('reject-id').value;
        try {
            await apiFetch('/api/landlord/maintenance/' + id + '/reject', {
                method: 'POST',
                body: JSON.stringify({ resolution_notes: document.getElementById('reject-notes').value }),
            });
            bootstrap.Modal.getInstance(document.getElementById('rejectModal')).hide();
            load();
        } catch (e) {
            document.getElementById('reject-error').innerHTML = '<div class="alert alert-danger">' + e.message + '</div>';
        }
    });

    load();
</script>
@endpush
