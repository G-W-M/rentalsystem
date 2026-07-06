@extends('layouts.landlord')

@section('title', 'Payments')

@section('content')
<div class="p-4">
    <h1 class="text-2xl font-bold text-primary mb-4">Payments</h1>
    <div id="pay-error"></div>
<div class="mb-3">
    <a href="/api/landlord/payments/export/csv" class="btn btn-sm btn-outline-secondary" target="_blank">
        <i class="fas fa-file-csv me-1"></i> Export CSV
    </a>
    <a href="/api/landlord/payments/export/pdf" class="btn btn-sm btn-outline-secondary" target="_blank">
        <i class="fas fa-file-pdf me-1"></i> Export PDF
    </a>
</div>
    <div class="card shadow-sm rounded-lg border-0 mb-3">
        <div class="card-body">
            <div class="row g-2">
                <div class="col-auto">
                    <select class="form-select form-select-sm" id="filter-status">
                        <option value="">All Statuses</option>
                        <option value="pending">Pending</option>
                        <option value="completed">Completed</option>
                        <option value="failed">Failed</option>
                        <option value="refunded">Refunded</option>
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
                        <th>Unit</th>
                        <th>Amount</th>
                        <th>Due Date</th>
                        <th>Status</th>
                        <th>Receipt</th>
                    </tr>
                </thead>
                <tbody id="pay-body">
                    <tr><td colspan="7" class="text-muted">Loading...</td></tr>
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    function xsrf() { return decodeURIComponent((document.cookie.match(/XSRF-TOKEN=([^;]+)/) || [])[1] || ''); }

    function formatDate(value) {
        if (!value) return '-';
        const d = new Date(value);
        if (isNaN(d.getTime())) return value;
        return d.toLocaleDateString(undefined, { year: 'numeric', month: 'short', day: 'numeric' });
    }

    async function apiFetch(path) {
        const res = await fetch(path, {
            credentials: 'include',
            headers: { 'Accept': 'application/json', 'X-XSRF-TOKEN': xsrf() },
        });
        const body = await res.json();
        if (!res.ok) throw { message: body.message || 'Request failed.' };
        return body;
    }

    function badge(status) {
        const map = { pending: 'warning', completed: 'success', failed: 'danger', refunded: 'info' };
        const cls = map[status] || 'secondary';
        return '<span class="badge bg-' + cls + (cls === 'warning' ? ' text-dark' : '') + '">' + status + '</span>';
    }

    async function load() {
        try {
            const status = document.getElementById('filter-status').value;
            const params = new URLSearchParams();
            if (status) params.set('status', status);

            const page = await apiFetch('/api/landlord/payments?' + params.toString());
            const items = page.data || [];
            const body = document.getElementById('pay-body');

            if (!items.length) {
                body.innerHTML = '<tr><td colspan="7" class="text-muted">No payments found</td></tr>';
                return;
            }

            body.innerHTML = items.map((p) => {
                const tenantName = p.tenant && p.tenant.user ? p.tenant.user.full_name : '-';
                const unitNumber = p.unit ? p.unit.unit_number : '-';
                return '<tr><td>' + p.id + '</td><td>' + tenantName + '</td><td>' + unitNumber + '</td><td>' +
                    Number(p.amount).toLocaleString() + '</td><td>' + formatDate(p.due_date) + '</td><td>' +
                    badge(p.status) + '</td><td>' + (p.receipt_url || '-') + '</td></tr>';
            }).join('');
        } catch (e) {
            document.getElementById('pay-error').innerHTML =
                '<div class="alert alert-danger">' + (e.message || 'Failed to load payments.') + '</div>';
        }
    }

    document.getElementById('filter-apply').addEventListener('click', load);
    load();
</script>
@endpush
