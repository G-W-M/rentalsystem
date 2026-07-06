@extends('layouts.admin')

@section('title', 'All Maintenance Requests')

@section('content')
<div class="p-4">
    <h1 class="text-2xl font-bold text-primary mb-4">All Maintenance Requests</h1>
    <div id="page-error"></div>

    <div class="mb-3">
        <a href="/api/admin/maintenance/export/csv" class="btn btn-sm btn-outline-secondary" target="_blank">
            <i class="fas fa-file-csv me-1"></i> Export CSV
        </a>
        <a href="/api/admin/maintenance/export/pdf" class="btn btn-sm btn-outline-secondary" target="_blank">
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
                <div class="col-auto"><button class="btn btn-sm btn-outline-secondary" id="filter-apply">Filter</button></div>
            </div>
        </div>
    </div>

    <div class="card shadow-sm rounded-lg border-0">
        <div class="card-body p-0">
            <table class="table mb-0 align-middle">
                <thead>
                    <tr><th>ID</th><th>Tenant</th><th>Property / Unit</th><th>Subject</th><th>Priority</th><th>Status</th></tr>
                </thead>
                <tbody id="maint-body"><tr><td colspan="6" class="text-muted">Loading...</td></tr></tbody>
            </table>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    function xsrf() { return decodeURIComponent((document.cookie.match(/XSRF-TOKEN=([^;]+)/) || [])[1] || ''); }

    async function apiFetch(path) {
        const res = await fetch(path, { credentials: 'include', headers: { Accept: 'application/json', 'X-XSRF-TOKEN': xsrf() } });
        const body = await res.json();
        if (!res.ok) throw { message: body.message || 'Request failed.' };
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

            const page = await apiFetch('/api/admin/maintenance?' + params.toString());
            const items = page.data || [];
            const body = document.getElementById('maint-body');

            if (!items.length) {
                body.innerHTML = '<tr><td colspan="6" class="text-muted">No maintenance requests found</td></tr>';
                return;
            }

            body.innerHTML = items.map((m) => {
                const tenantName = m.tenant && m.tenant.user ? m.tenant.user.full_name : '-';
                const propUnit = (m.property ? m.property.name : '-') + (m.unit ? ' / ' + m.unit.unit_number : '');
                return '<tr><td>' + m.id + '</td><td>' + tenantName + '</td><td>' + propUnit + '</td><td>' +
                    (m.subject || m.category) + '</td><td>' + m.priority + '</td><td>' + badge(m.status) + '</td></tr>';
            }).join('');
        } catch (e) {
            document.getElementById('page-error').innerHTML = '<div class="alert alert-danger">' + e.message + '</div>';
        }
    }

    document.getElementById('filter-apply').addEventListener('click', load);
    load();
</script>
@endpush