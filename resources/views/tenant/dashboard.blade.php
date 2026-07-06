@extends('layouts.tenant')

@section('title', 'Dashboard')

@section('content')
    <div class="p-4">
        <h1 class="text-2xl font-bold text-primary mb-4">My Dashboard</h1>
        <div id="dash-error"></div>

        <div class="row g-3">
            <div class="col-12 col-lg-6">
                <div class="card shadow-sm rounded-lg border-0 h-100">
                    <div class="card-header bg-white fw-semibold">My Unit</div>
                    <div class="card-body" id="unit-block">
                        <div class="text-muted">Loading...</div>
                    </div>
                </div>
            </div>
            <div class="col-12 col-lg-6">
                <div class="card shadow-sm rounded-lg border-0 h-100">
                    <div class="card-header bg-white fw-semibold">Next Payment</div>
                    <div class="card-body" id="payment-block">
                        <div class="text-muted">Loading...</div>
                    </div>
                </div>
            </div>
        </div>

        <div class="card shadow-sm rounded-lg border-0 mt-4">
            <div class="card-header bg-white fw-semibold">Open Maintenance</div>
            <div class="card-body">
                <span class="text-3xl font-bold text-warning" data-kpi="open_maintenance">--</span>
                <span class="text-gray-600">open request(s)</span>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        function xsrf() {
            return decodeURIComponent((document.cookie.match(/XSRF-TOKEN=([^;]+)/) || [])[1] || '');
        }

        function money(v) {
            return Number(v || 0).toLocaleString(undefined, {
                minimumFractionDigits: 2,
                maximumFractionDigits: 2
            });
        }

        async function load() {
            try {
                const res = await fetch('/api/tenant/dashboard', {
                    credentials: 'include',
                    headers: {
                        'Accept': 'application/json',
                        'X-XSRF-TOKEN': xsrf()
                    },
                });
                const data = await res.json();
                if (!res.ok) throw new Error(data.message || 'Failed to load dashboard.');

                const unit = document.getElementById('unit-block');
                if (data.has_unit && data.unit) {
                    unit.innerHTML = '<div class="h5 mb-1">' + data.unit.property + ' — Unit ' + data.unit.unit_number +
                        '</div>' +
                        '<div class="text-gray-600">Rent: ' + money(data.unit.rent) + '</div>' +
                        '<div class="text-gray-600">Since: ' + (data.unit.since || '-') + '</div>';
                } else {
                    unit.innerHTML = '<div class="text-muted">You have no active unit yet.</div>';
                }

                const pay = document.getElementById('payment-block');
                if (data.pending_payment) {
                    pay.innerHTML = '<div class="h4 text-primary">' + money(data.pending_payment.amount) + '</div>' +
                        '<div class="text-gray-600">Due: ' + (data.pending_payment.due_date || '-') + '</div>' +
                        '<span class="badge bg-warning text-dark">' + data.pending_payment.status + '</span>';
                } else {
                    pay.innerHTML = '<div class="text-muted">No pending payment. You are all caught up.</div>';
                }

                document.querySelector('[data-kpi="open_maintenance"]').textContent = data.open_maintenance ?? 0;
            } catch (e) {
                document.getElementById('dash-error').innerHTML = '<div class="alert alert-danger">' + e.message +
                    '</div>';
            }
        }
        load();
    </script>
@endpush
