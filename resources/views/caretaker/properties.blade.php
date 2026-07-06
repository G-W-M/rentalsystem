@extends('layouts.caretaker')

@section('title', 'My Assigned Units')

@section('content')
    <div class="p-4">
        <h1 class="text-2xl font-bold text-primary mb-4">Properties & Units</h1>
        <div id="page-error"></div>

        <ul class="nav nav-tabs mb-3">
            <li class="nav-item"><button class="nav-link active" data-tab="properties">Properties</button></li>
            <li class="nav-item"><button class="nav-link" data-tab="units">Units & Tenants</button></li>
        </ul>

        <div id="properties-tab">
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

        <div id="units-tab" style="display:none;">
            <div class="card shadow-sm rounded-lg border-0">
                <div class="card-body p-0">
                    <table class="table mb-0 align-middle">
                        <thead>
                            <tr>
                                <th>Property</th>
                                <th>Unit</th>
                                <th>Status</th>
                                <th>Tenant</th>
                                <th>Tenant Phone</th>
                            </tr>
                        </thead>
                        <tbody id="unit-body">
                            <tr>
                                <td colspan="5" class="text-muted">Loading...</td>
                            </tr>
                        </tbody>
                    </table>
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

        async function apiFetch(path) {
            const res = await fetch(path, {
                credentials: 'include',
                headers: {
                    Accept: 'application/json',
                    'X-XSRF-TOKEN': xsrf()
                }
            });
            const body = await res.json();
            if (!res.ok) throw {
                message: body.message || 'Request failed.'
            };
            return body;
        }

        async function loadProperties() {
            try {
                const properties = await apiFetch('/api/caretaker/properties');
                const body = document.getElementById('prop-body');
                body.innerHTML = properties.length ?
                    properties.map((p) => '<tr><td>' + p.name + '</td><td>' + p.address + '</td><td>' + p
                        .property_type +
                        '</td><td>' + (p.units_count ?? 0) + '</td><td><span class="badge bg-secondary">' + p.status +
                        '</span></td></tr>').join('') :
                    '<tr><td colspan="5" class="text-muted">No properties assigned</td></tr>';
            } catch (e) {
                document.getElementById('page-error').innerHTML = '<div class="alert alert-danger">' + e.message +
                    '</div>';
            }
        }

        async function loadUnits() {
            try {
                const units = await apiFetch('/api/caretaker/units');
                const body = document.getElementById('unit-body');
                body.innerHTML = units.length ?
                    units.map((u) => {
                        const occ = u.active_occupancy;
                        const tenantName = occ && occ.tenant && occ.tenant.user ? occ.tenant.user.full_name : '-';
                        const tenantPhone = occ && occ.tenant && occ.tenant.user ? (occ.tenant.user.phone || '-') :
                            '-';
                        return '<tr><td>' + (u.property ? u.property.name : '-') + '</td><td>' + u.unit_number +
                            '</td><td><span class="badge bg-secondary">' + u.status + '</span></td><td>' +
                            tenantName +
                            '</td><td>' + tenantPhone + '</td></tr>';
                    }).join('') :
                    '<tr><td colspan="5" class="text-muted">No units found</td></tr>';
            } catch (e) {
                document.getElementById('page-error').innerHTML = '<div class="alert alert-danger">' + e.message +
                    '</div>';
            }
        }

        document.querySelectorAll('[data-tab]').forEach((btn) => {
            btn.addEventListener('click', () => {
                document.querySelectorAll('[data-tab]').forEach((b) => b.classList.remove('active'));
                btn.classList.add('active');
                const tab = btn.getAttribute('data-tab');
                document.getElementById('properties-tab').style.display = tab === 'properties' ? 'block' :
                    'none';
                document.getElementById('units-tab').style.display = tab === 'units' ? 'block' : 'none';
                if (tab === 'units') loadUnits();
            });
        });

        loadProperties();
    </script>
@endpush
