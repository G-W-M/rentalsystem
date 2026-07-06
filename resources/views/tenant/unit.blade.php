@extends('layouts.tenant')

@section('title', 'My Unit')

@section('content')
    <div class="p-4">
        <h1 class="text-2xl font-bold text-primary mb-4">My Unit</h1>
        <div id="unit-error"></div>

        <div class="row g-4">
            <div class="col-12 col-lg-6">
                <div class="card shadow-sm rounded-lg border-0">
                    <div class="card-header bg-white fw-semibold">Unit Details</div>
                    <div class="card-body" id="unit-detail">
                        <div class="text-muted">Loading...</div>
                    </div>
                </div>
            </div>
            <div class="col-12 col-lg-6">
                <div class="card shadow-sm rounded-lg border-0">
                    <div class="card-header bg-white fw-semibold">Your Caretaker</div>
                    <div class="card-body" id="caretaker-detail">
                        <div class="text-muted">Loading...</div>
                    </div>
                </div>
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
                        Accept: 'application/json',
                        'X-XSRF-TOKEN': xsrf()
                    },
                });
                const data = await res.json();
                if (!res.ok) throw new Error(data.message || 'Failed to load.');

                const unitEl = document.getElementById('unit-detail');
                if (data.has_unit && data.unit) {
                    unitEl.innerHTML = '<dl class="row mb-0">' +
                        '<dt class="col-5 text-gray-600">Property</dt><dd class="col-7">' + data.unit.property +
                        '</dd>' +
                        '<dt class="col-5 text-gray-600">Unit Number</dt><dd class="col-7">' + data.unit.unit_number +
                        '</dd>' +
                        '<dt class="col-5 text-gray-600">Monthly Rent</dt><dd class="col-7">' + money(data.unit.rent) +
                        '</dd>' +
                        '<dt class="col-5 text-gray-600">Tenancy Since</dt><dd class="col-7">' + (data.unit.since ||
                            '-') + '</dd>' +
                        '</dl>';
                } else {
                    unitEl.innerHTML = '<div class="text-muted">You are not currently allocated a unit.</div>';
                }

                const caretakerEl = document.getElementById('caretaker-detail');
                if (data.caretaker) {
                    caretakerEl.innerHTML = '<dl class="row mb-0">' +
                        '<dt class="col-5 text-gray-600">Name</dt><dd class="col-7">' + data.caretaker.full_name +
                        '</dd>' +
                        '<dt class="col-5 text-gray-600">Phone</dt><dd class="col-7">' +
                        (data.caretaker.phone ? '<a href="tel:' + data.caretaker.phone + '">' + data.caretaker.phone +
                            '</a>' : '-') + '</dd>' +
                        '<dt class="col-5 text-gray-600">Email</dt><dd class="col-7">' + (data.caretaker.email || '-') +
                        '</dd>' +
                        '</dl>';
                } else {
                    caretakerEl.innerHTML = '<div class="text-muted">No caretaker assigned to your property yet.</div>';
                }
            } catch (e) {
                document.getElementById('unit-error').innerHTML = '<div class="alert alert-danger">' + e.message +
                    '</div>';
            }
        }
        load();
    </script>
@endpush
