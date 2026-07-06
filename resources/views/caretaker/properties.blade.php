@extends('layouts.caretaker')

@section('title', 'Properties')

@section('content')
    <div class="p-4">
        <h1 class="text-2xl font-bold text-primary mb-4">My Properties</h1>
        <div id="prop-error"></div>
        <div class="card shadow-sm rounded-lg border-0">
            <div class="card-body p-0">
                <table class="table mb-0 align-middle">
                    <thead>
                        <tr>
                            <th>Name</th>
                            <th>Address</th>
                            <th>Type</th>
                            <th>Units</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody id="prop-body">
                        <tr>
                            <td colspan="5" class="text-muted">Loading...</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        function xsrf() {
            return decodeURIComponent((document.cookie.match(/XSRF-TOKEN=([^;]+)/) || [])[1] || '');
        }

        async function load() {
            try {
                const res = await fetch('/api/caretaker/properties', {
                    credentials: 'include',
                    headers: {
                        'Accept': 'application/json',
                        'X-XSRF-TOKEN': xsrf()
                    },
                });
                const properties = await res.json();
                if (!res.ok) throw new Error(properties.message || 'Failed to load.');
                const body = document.getElementById('prop-body');
                body.innerHTML = properties.length ?
                    properties.map((p) => '<tr><td>' + p.name + '</td><td>' + p.address + '</td><td>' + p
                        .property_type +
                        '</td><td>' + (p.units_count ?? 0) + '</td><td><span class="badge bg-secondary">' + p.status +
                        '</span></td></tr>').join('') :
                    '<tr><td colspan="5" class="text-muted">No properties assigned</td></tr>';
            } catch (e) {
                document.getElementById('prop-error').innerHTML = '<div class="alert alert-danger">' + e.message +
                    '</div>';
            }
        }
        load();
    </script>
@endpush
