@extends('layouts.landlord')

@section('title', 'Properties')

@section('content')
    <div class="p-4">
        <div class="flex-between mb-4">
            <h1 class="text-2xl font-bold text-primary mb-0">Properties</h1>
            <a href="{{ route('landlord.properties.create') }}" class="btn btn-primary"><i class="fas fa-plus me-2"></i>Add
                Property</a>
        </div>
        <div id="prop-error"></div>
        <div class="card shadow-sm rounded-lg border-0">
            <div class="card-body p-0">
                <table class="table mb-0 align-middle">
                    <thead>
                        <tr>
                            <th>Name</th>
                            <th>Type</th>
                            <th>Units</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody id="prop-body">
                        <tr>
                            <td colspan="4" class="text-muted">Loading...</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script type="module">
        import {
            api,
            renderError
        } from '/resources/js/api.js';

        async function load() {
            try {
                const rows = await api('/api/landlord/properties');
                const body = document.getElementById('prop-body');
                if (!rows.length) {
                    body.innerHTML = '<tr><td colspan="4" class="text-muted">No properties yet</td></tr>';
                    return;
                }
                body.innerHTML = rows.map((p) =>
                    '<tr><td>' + p.name + '</td><td>' + p.property_type + '</td><td>' + (p.units_count ?? 0) +
                    '</td><td><span class="badge bg-secondary">' + p.status + '</span></td></tr>'
                ).join('');
            } catch (e) {
                renderError(document.getElementById('prop-error'), e);
            }
        }
        load();
    </script>
@endpush
