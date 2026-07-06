@extends('layouts.landlord')

@section('title', 'Units')

@section('content')
    <div class="p-4">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h1 class="text-2xl font-bold text-primary mb-0">Units</h1>
            <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#createUnitModal">
                <i class="fas fa-plus me-2"></i>Add Unit
            </button>
        </div>

        <div id="unit-error"></div>

        <div class="card shadow-sm rounded-lg border-0">
            <div class="card-body p-0">
                <table class="table mb-0 align-middle">
                    <thead>
                        <tr>
                            <th>Property</th>
                            <th>Unit #</th>
                            <th>Rent</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody id="unit-body">
                        <tr>
                            <td colspan="4" class="text-muted">Loading...</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="modal fade" id="createUnitModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Add Unit</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div id="create-unit-error"></div>
                    <div class="mb-3">
                        <label class="form-label">Property</label>
                        <select class="form-select" id="unit-property"></select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Unit Number</label>
                        <input type="text" class="form-control" id="unit-number" placeholder="e.g. A1">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Rent Amount</label>
                        <input type="number" class="form-control" id="unit-rent">
                    </div>
                </div>
                <div class="modal-footer">
                    <button class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button class="btn btn-primary" id="create-unit-submit">Create Unit</button>
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

        async function apiFetch(path, options = {}) {
            const method = (options.method || 'GET').toUpperCase();
            if (method !== 'GET') {
                await fetch('/sanctum/csrf-cookie', {
                    credentials: 'include'
                });
            }
            const res = await fetch(path, {
                credentials: 'include',
                ...options,
                method,
                headers: {
                    'Accept': 'application/json',
                    'Content-Type': 'application/json',
                    'X-XSRF-TOKEN': xsrf(),
                    ...(options.headers || {}),
                },
            });
            const text = await res.text();
            const body = text ? JSON.parse(text) : null;
            if (!res.ok) {
                throw {
                    status: res.status,
                    message: (body && body.message) || 'Request failed.'
                };
            }
            return body;
        }

        function showError(elId, e) {
            document.getElementById(elId).innerHTML =
                '<div class="alert alert-danger">' + (e.message || 'Something went wrong.') + '</div>';
        }

        async function loadUnits() {
            try {
                const units = await apiFetch('/api/landlord/units');
                const body = document.getElementById('unit-body');
                if (!units.length) {
                    body.innerHTML = '<tr><td colspan="4" class="text-muted">No units yet</td></tr>';
                    return;
                }
                body.innerHTML = units.map((u) => {
                    const propName = u.property ? u.property.name : '-';
                    return '<tr><td>' + propName + '</td><td>' + u.unit_number + '</td><td>' +
                        Number(u.rent_amount).toLocaleString() + '</td><td><span class="badge bg-secondary">' +
                        u.status + '</span></td></tr>';
                }).join('');
            } catch (e) {
                showError('unit-error', e);
            }
        }

        async function loadPropertiesIntoSelect() {
            const properties = await apiFetch('/api/landlord/properties');
            const sel = document.getElementById('unit-property');
            sel.innerHTML = properties.map((p) => '<option value="' + p.id + '">' + p.name + '</option>').join('');
        }

        document.getElementById('createUnitModal').addEventListener('show.bs.modal', async () => {
            document.getElementById('create-unit-error').innerHTML = '';
            try {
                await loadPropertiesIntoSelect();
            } catch (e) {
                showError('create-unit-error', e);
            }
        });

        document.getElementById('create-unit-submit').addEventListener('click', async () => {
            try {
                await apiFetch('/api/landlord/units', {
                    method: 'POST',
                    body: JSON.stringify({
                        property_id: document.getElementById('unit-property').value,
                        unit_number: document.getElementById('unit-number').value,
                        rent_amount: document.getElementById('unit-rent').value,
                    }),
                });
                bootstrap.Modal.getInstance(document.getElementById('createUnitModal')).hide();
                document.getElementById('unit-number').value = '';
                document.getElementById('unit-rent').value = '';
                loadUnits();
            } catch (e) {
                showError('create-unit-error', e);
            }
        });

        loadUnits();
    </script>
@endpush
