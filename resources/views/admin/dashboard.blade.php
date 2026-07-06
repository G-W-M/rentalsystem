@extends('layouts.admin')

@section('title', 'Admin Dashboard')

@section('content')
<div class="p-4">
    <h1 class="text-2xl font-bold text-primary mb-4">Platform Overview</h1>
    <div id="dash-error"></div>

    <div class="row g-3">
        <div class="col-6 col-lg-3"><div class="card shadow-sm rounded-lg border-0"><div class="card-body"><div class="text-xs text-gray-600 text-uppercase">Landlords</div><div class="text-3xl font-bold text-primary" data-kpi="total_landlords">--</div></div></div></div>
        <div class="col-6 col-lg-3"><div class="card shadow-sm rounded-lg border-0"><div class="card-body"><div class="text-xs text-gray-600 text-uppercase">Active Tenants</div><div class="text-3xl font-bold text-primary" data-kpi="total_tenants">--</div></div></div></div>
        <div class="col-6 col-lg-3"><div class="card shadow-sm rounded-lg border-0"><div class="card-body"><div class="text-xs text-gray-600 text-uppercase">Properties</div><div class="text-3xl font-bold text-info" data-kpi="total_properties">--</div></div></div></div>
        <div class="col-6 col-lg-3"><div class="card shadow-sm rounded-lg border-0"><div class="card-body"><div class="text-xs text-gray-600 text-uppercase">Units</div><div class="text-3xl font-bold text-info" data-kpi="total_units">--</div></div></div></div>
    </div>

    <div class="row g-3 mt-1">
        <div class="col-6 col-lg-4"><div class="card shadow-sm rounded-lg border-0"><div class="card-body"><div class="text-xs text-gray-600 text-uppercase">Total Revenue</div><div class="text-2xl font-bold text-success" data-kpi="total_revenue">--</div></div></div></div>
        <div class="col-6 col-lg-4"><div class="card shadow-sm rounded-lg border-0"><div class="card-body"><div class="text-xs text-gray-600 text-uppercase">Pending Maintenance</div><div class="text-2xl font-bold text-warning" data-kpi="pending_maintenance">--</div></div></div></div>
        <div class="col-6 col-lg-4"><div class="card shadow-sm rounded-lg border-0"><div class="card-body"><div class="text-xs text-gray-600 text-uppercase">Active Users</div><div class="text-2xl font-bold" data-kpi="active_users">--</div></div></div></div>
    </div>

    <div class="row g-3 mt-1">
        <div class="col-12 col-lg-4"><div class="card shadow-sm rounded-lg border-0"><div class="card-header bg-white fw-semibold">Property Types</div><div class="card-body"><canvas id="propChart" height="180"></canvas></div></div></div>
        <div class="col-12 col-lg-4"><div class="card shadow-sm rounded-lg border-0"><div class="card-header bg-white fw-semibold">Top Landlords by Revenue</div><div class="card-body"><canvas id="landlordChart" height="180"></canvas></div></div></div>
        <div class="col-12 col-lg-4"><div class="card shadow-sm rounded-lg border-0"><div class="card-header bg-white fw-semibold">Revenue Trend</div><div class="card-body"><canvas id="revenueChart" height="180"></canvas></div></div></div>
    </div>

    <div class="row g-3 mt-1">
        <div class="col-12 col-lg-6">
            <div class="card shadow-sm rounded-lg border-0">
                <div class="card-header bg-white fw-semibold">Recent Registrations</div>
                <div class="card-body p-0">
                    <table class="table mb-0"><thead><tr><th>Name</th><th>Role</th></tr></thead><tbody id="recent-users"></tbody></table>
                </div>
            </div>
        </div>
        <div class="col-12 col-lg-6">
            <div class="card shadow-sm rounded-lg border-0">
                <div class="card-header bg-white fw-semibold">Recent Payments</div>
                <div class="card-body p-0">
                    <table class="table mb-0"><thead><tr><th>Tenant</th><th>Amount</th><th>Status</th></tr></thead><tbody id="recent-payments"></tbody></table>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
<script>
    function xsrf() { return decodeURIComponent((document.cookie.match(/XSRF-TOKEN=([^;]+)/) || [])[1] || ''); }
    function toArraySafe(v) { if (Array.isArray(v)) return v; if (v && typeof v === 'object') return Object.values(v); return []; }
    function money(v) { return Number(v || 0).toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 }); }

    async function load() {
        try {
            const res = await fetch('/api/admin/dashboard', {
                credentials: 'include',
                headers: { 'Accept': 'application/json', 'X-XSRF-TOKEN': xsrf() },
            });
            const data = await res.json();
            if (!res.ok) throw new Error(data.message || 'Failed to load dashboard.');

            document.querySelectorAll('[data-kpi]').forEach((el) => {
                const key = el.getAttribute('data-kpi');
                el.textContent = key === 'total_revenue' ? money(data[key]) : (data[key] ?? 0);
            });

            const charts = data.charts || {};
            const props = toArraySafe(charts.property_distribution);
            new Chart(document.getElementById('propChart'), {
                type: 'pie',
                data: { labels: props.map(p => p.type), datasets: [{ data: props.map(p => p.total), backgroundColor: ['#055236','#80B9B1','#6C27DA','#F59E0B'] }] },
            });

            const landlords = toArraySafe(charts.top_landlords_by_revenue);
            new Chart(document.getElementById('landlordChart'), {
                type: 'bar',
                data: { labels: landlords.map(l => l.name), datasets: [{ label: 'Revenue', data: landlords.map(l => l.revenue), backgroundColor: '#055236' }] },
                options: { indexAxis: 'y' },
            });

            const rev = toArraySafe(charts.revenue_trend);
            new Chart(document.getElementById('revenueChart'), {
                type: 'line',
                data: { labels: rev.map(r => r.month), datasets: [{ label: 'Revenue', data: rev.map(r => r.total), borderColor: '#6C27DA', tension: 0.3 }] },
            });

            const recentUsers = toArraySafe(data.recent_registrations);
            document.getElementById('recent-users').innerHTML = recentUsers.length
                ? recentUsers.map(u => '<tr><td>' + u.full_name + '</td><td><span class="badge bg-secondary">' + u.role + '</span></td></tr>').join('')
                : '<tr><td colspan="2" class="text-muted">No recent registrations</td></tr>';

            const recentPayments = toArraySafe(data.recent_payments);
            document.getElementById('recent-payments').innerHTML = recentPayments.length
                ? recentPayments.map(p => {
                    const name = p.tenant && p.tenant.user ? p.tenant.user.full_name : '-';
                    return '<tr><td>' + name + '</td><td>' + money(p.amount) + '</td><td>' + p.status + '</td></tr>';
                }).join('')
                : '<tr><td colspan="3" class="text-muted">No recent payments</td></tr>';
        } catch (e) {
            document.getElementById('dash-error').innerHTML = '<div class="alert alert-danger">' + e.message + '</div>';
        }
    }
    load();
</script>
@endpush
