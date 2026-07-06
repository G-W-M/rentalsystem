@extends('layouts.landlord')

@section('title', 'Dashboard')

@section('content')
    <div class="p-4">
        <h1 class="text-2xl font-bold text-primary mb-4">Landlord Dashboard</h1>
        <div id="dash-error"></div>

        <div class="row g-3">
            <div class="col-6 col-lg-3">
                <div class="card shadow-sm rounded-lg border-0">
                    <div class="card-body">
                        <div class="text-xs text-gray-600 text-uppercase">Properties</div>
                        <div class="text-3xl font-bold text-primary" data-kpi="total_properties">--</div>
                    </div>
                </div>
            </div>
            <div class="col-6 col-lg-3">
                <div class="card shadow-sm rounded-lg border-0">
                    <div class="card-body">
                        <div class="text-xs text-gray-600 text-uppercase">Total Units</div>
                        <div class="text-3xl font-bold text-primary" data-kpi="total_units">--</div>
                    </div>
                </div>
            </div>
            <div class="col-6 col-lg-3">
                <div class="card shadow-sm rounded-lg border-0">
                    <div class="card-body">
                        <div class="text-xs text-gray-600 text-uppercase">Occupied</div>
                        <div class="text-3xl font-bold text-success" data-kpi="occupied_units">--</div>
                    </div>
                </div>
            </div>
            <div class="col-6 col-lg-3">
                <div class="card shadow-sm rounded-lg border-0">
                    <div class="card-body">
                        <div class="text-xs text-gray-600 text-uppercase">Occupancy</div>
                        <div class="text-3xl font-bold text-info"><span data-kpi="occupancy_rate">--</span>%</div>
                    </div>
                </div>
            </div>
        </div>

        <div class="row g-3 mt-1">
            <div class="col-6 col-lg-3">
                <div class="card shadow-sm rounded-lg border-0">
                    <div class="card-body">
                        <div class="text-xs text-gray-600 text-uppercase">Active Tenants</div>
                        <div class="text-2xl font-bold" data-kpi="total_tenants">--</div>
                    </div>
                </div>
            </div>
            <div class="col-6 col-lg-3">
                <div class="card shadow-sm rounded-lg border-0">
                    <div class="card-body">
                        <div class="text-xs text-gray-600 text-uppercase">Pending Payments</div>
                        <div class="text-2xl font-bold text-warning" data-kpi="pending_payments">--</div>
                    </div>
                </div>
            </div>
            <div class="col-6 col-lg-3">
                <div class="card shadow-sm rounded-lg border-0">
                    <div class="card-body">
                        <div class="text-xs text-gray-600 text-uppercase">Open Maintenance</div>
                        <div class="text-2xl font-bold text-danger" data-kpi="open_maintenance">--</div>
                    </div>
                </div>
            </div>
            <div class="col-6 col-lg-3">
                <div class="card shadow-sm rounded-lg border-0">
                    <div class="card-body">
                        <div class="text-xs text-gray-600 text-uppercase">Vacant Units</div>
                        <div class="text-2xl font-bold" data-kpi="vacant_units">--</div>
                    </div>
                </div>
            </div>
        </div>

        <div class="row g-3 mt-1">
            <div class="col-12 col-lg-7">
                <div class="card shadow-sm rounded-lg border-0">
                    <div class="card-header bg-white fw-semibold">Occupancy by Property</div>
                    <div class="card-body"><canvas id="occupancyChart" height="140"></canvas></div>
                </div>
            </div>
            <div class="col-12 col-lg-5">
                <div class="card shadow-sm rounded-lg border-0">
                    <div class="card-header bg-white fw-semibold">Revenue (6 months)</div>
                    <div class="card-body"><canvas id="revenueChart" height="200"></canvas></div>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
    <script>
        function xsrf() {
            return decodeURIComponent((document.cookie.match(/XSRF-TOKEN=([^;]+)/) || [])[1] || '');
        }

        function toArraySafe(value) {
            if (Array.isArray(value)) return value;
            if (value && typeof value === 'object') return Object.values(value);
            return [];
        }

        async function load() {
            try {
                const res = await fetch('/api/landlord/dashboard', {
                    credentials: 'include',
                    headers: {
                        'Accept': 'application/json',
                        'X-XSRF-TOKEN': xsrf()
                    },
                });
                const data = await res.json();
                if (!res.ok) throw new Error(data.message || 'Failed to load dashboard.');

                document.querySelectorAll('[data-kpi]').forEach((el) => {
                    el.textContent = data[el.getAttribute('data-kpi')] ?? 0;
                });

                const charts = data.charts || {};
                const occ = toArraySafe(charts.occupancy_by_property);
                const rev = toArraySafe(charts.revenue_last_6_months);

                new Chart(document.getElementById('occupancyChart'), {
                    type: 'bar',
                    data: {
                        labels: occ.map((p) => p.name ?? ''),
                        datasets: [{
                                label: 'Occupied',
                                data: occ.map((p) => p.occupied ?? 0),
                                backgroundColor: '#055236'
                            },
                            {
                                label: 'Total',
                                data: occ.map((p) => p.total ?? 0),
                                backgroundColor: '#80B9B1'
                            },
                        ],
                    },
                    options: {
                        responsive: true,
                        scales: {
                            y: {
                                beginAtZero: true
                            }
                        }
                    },
                });

                new Chart(document.getElementById('revenueChart'), {
                    type: 'line',
                    data: {
                        labels: rev.map((r) => r.month ?? ''),
                        datasets: [{
                            label: 'Revenue',
                            data: rev.map((r) => r.total ?? 0),
                            borderColor: '#6C27DA',
                            tension: 0.3
                        }],
                    },
                    options: {
                        responsive: true,
                        scales: {
                            y: {
                                beginAtZero: true
                            }
                        }
                    },
                });
            } catch (e) {
                document.getElementById('dash-error').innerHTML = '<div class="alert alert-danger">' + e.message +
                    '</div>';
            }
        }
        load();
    </script>
@endpush
