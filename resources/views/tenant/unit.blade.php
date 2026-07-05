@extends('layouts.tenant')

@section('title', 'My Unit')

@section('content')
<div class="p-4">
    <h1 class="text-2xl font-bold text-primary mb-4">My Unit</h1>

    <div id="unit-error"></div>

    <div class="card shadow-sm rounded-lg border-0" style="max-width: 560px;">
        <div class="card-body" id="unit-detail">
            <div class="text-muted">Loading...</div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script type="module">
    import { api, renderError, money } from '/resources/js/api.js';

    async function load() {
        try {
            const data = await api('/api/tenant/dashboard');
            const el = document.getElementById('unit-detail');
            if (data.has_unit && data.unit) {
                el.innerHTML =
                    '<dl class="row mb-0">' +
                    '<dt class="col-5 text-gray-600">Property</dt><dd class="col-7">' + data.unit.property + '</dd>' +
                    '<dt class="col-5 text-gray-600">Unit Number</dt><dd class="col-7">' + data.unit.unit_number + '</dd>' +
                    '<dt class="col-5 text-gray-600">Monthly Rent</dt><dd class="col-7">' + money(data.unit.rent) + '</dd>' +
                    '<dt class="col-5 text-gray-600">Tenancy Since</dt><dd class="col-7">' + (data.unit.since || '-') + '</dd>' +
                    '</dl>';
            } else {
                el.innerHTML = '<div class="text-muted">You are not currently allocated a unit.</div>';
            }
        } catch (e) {
            renderError(document.getElementById('unit-error'), e);
        }
    }

    load();
</script>
@endpush
