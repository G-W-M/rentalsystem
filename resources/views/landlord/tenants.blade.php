@extends('layouts.landlord')

@section('title', 'Tenants')

@section('content')
    <div class="p-4">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h1 class="text-2xl font-bold text-primary mb-0">Tenants</h1>
            <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#createTenantModal">
                <i class="fas fa-plus me-2"></i>Add Tenant
            </button>
        </div>

        <div id="tenant-error"></div>

        <div class="card shadow-sm rounded-lg border-0">
            <div class="card-body p-0">
                <table class="table mb-0 align-middle">
                    <thead>
                        <tr>
                            <th>Name</th>
                            <th>Email</th>
                            <th>Unit</th>
                            <th class="text-end">Action</th>
                        </tr>
                    </thead>
                    <tbody id="tenant-body">
                        <tr>
                            <td colspan="4" class="text-muted">Loading...</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="modal fade" id="createTenantModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Add Tenant</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div id="create-tenant-error"></div>
                    <div class="mb-2"><label class="form-label">Full Name</label><input class="form-control"
                            id="t-name"></div>
                    <div class="mb-2"><label class="form-label">Email</label><input type="email" class="form-control"
                            id="t-email"></div>
                    <div class="mb-2"><label class="form-label">Username</label><input class="form-control"
                            id="t-username"></div>
                    <div class="mb-2"><label class="form-label">Phone</label><input class="form-control" id="t-phone">
                    </div>
                    <div class="mb-3"><label class="form-label">Password</label><input type="password"
                            class="form-control" id="t-password"></div>
                </div>
                <div class="modal-footer">
                    <button class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button class="btn btn-primary" id="create-tenant-submit">Create Tenant</button>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="allocModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Allocate to Unit</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" id="alloc-tenant-id">
                    <div id="alloc-error"></div>
                    <div class="mb-3"><label class="form-label">Unit</label><select class="form-select"
                            id="alloc-unit"></select></div>
                    <div class="mb-3"><label class="form-label">Start Date</label><input type="date"
                            class="form-control" id="alloc-start"></div>
                    <div class="mb-3"><label class="form-label">Deposit Amount</label><input type="number"
                            class="form-control" id="alloc-deposit"></div>
                </div>
                <div class="modal-footer">
                    <button class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button class="btn btn-primary" id="alloc-submit">Allocate</button>
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

        const tenantBody = document.getElementById('tenant-body');

        async function loadTenants() {
            try {
                const tenants = await apiFetch('/api/landlord/tenants');
                if (!tenants.length) {
                    tenantBody.innerHTML = '<tr><td colspan="4" class="text-muted">No tenants yet</td></tr>';
                    return;
                }
                tenantBody.innerHTML = tenants.map((t) => {
                    const u = t.user || {};
                    const occ = t.active_occupancy;
                    const unitLabel = occ && occ.unit ? occ.unit.unit_number : '—';
                    const action = occ ?
                        '<span class="text-muted small">Allocated</span>' :
                        '<button class="btn btn-sm btn-primary" data-alloc="' + t.user_id +
                        '">Allocate</button>';
                    return '<tr><td>' + (u.full_name || '-') + '</td><td>' + (u.email || '-') + '</td><td>' +
                        unitLabel + '</td><td class="text-end">' + action + '</td></tr>';
                }).join('');
            } catch (e) {
                showError('tenant-error', e);
            }
        }

        document.getElementById('create-tenant-submit').addEventListener('click', async () => {
            try {
                await apiFetch('/api/landlord/tenants', {
                    method: 'POST',
                    body: JSON.stringify({
                        full_name: document.getElementById('t-name').value,
                        email: document.getElementById('t-email').value,
                        username: document.getElementById('t-username').value,
                        phone: document.getElementById('t-phone').value,
                        password: document.getElementById('t-password').value,
                    }),
                });
                bootstrap.Modal.getInstance(document.getElementById('createTenantModal')).hide();
                loadTenants();
            } catch (e) {
                showError('create-tenant-error', e);
            }
        });

        tenantBody.addEventListener('click', async (ev) => {
            const id = ev.target.getAttribute('data-alloc');
            if (!id) return;
            document.getElementById('alloc-tenant-id').value = id;
            document.getElementById('alloc-error').innerHTML = '';
            try {
                const units = await apiFetch('/api/landlord/units');
                const sel = document.getElementById('alloc-unit');
                const available = units.filter((u) => u.status === 'available');
                sel.innerHTML = available.map((u) => {
                    const propName = u.property ? u.property.name + ' — ' : '';
                    return '<option value="' + u.id + '">' + propName + u.unit_number + '</option>';
                }).join('');
                new bootstrap.Modal(document.getElementById('allocModal')).show();
            } catch (e) {
                showError('tenant-error', e);
            }
        });

        document.getElementById('alloc-submit').addEventListener('click', async () => {
            const id = document.getElementById('alloc-tenant-id').value;
            try {
                await apiFetch('/api/landlord/tenants/' + id + '/allocate', {
                    method: 'POST',
                    body: JSON.stringify({
                        unit_id: document.getElementById('alloc-unit').value,
                        start_date: document.getElementById('alloc-start').value,
                        deposit_amount: document.getElementById('alloc-deposit').value || null,
                        deposit_paid: false,
                    }),
                });
                bootstrap.Modal.getInstance(document.getElementById('allocModal')).hide();
                loadTenants();
            } catch (e) {
                showError('alloc-error', e);
            }
        });

        loadTenants();
    </script>
@endpush
