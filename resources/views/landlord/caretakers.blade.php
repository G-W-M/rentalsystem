@extends('layouts.landlord')

@section('title', 'Caretakers')

@section('content')
<div class="p-4">
    <h1 class="text-2xl font-bold text-primary mb-4">Caretakers</h1>
    <div class="row g-4">
        <div class="col-12 col-lg-7">
            <div id="list-error"></div>
            <div class="card shadow-sm rounded-lg border-0">
                <div class="card-body p-0">
                    <table class="table mb-0 align-middle">
                        <thead><tr><th>Name</th><th>Email</th><th>Phone</th><th>Assigned Property</th><th class="text-end">Action</th></tr></thead>
                        <tbody id="ct-body"><tr><td colspan="5" class="text-muted">Loading...</td></tr></tbody>
                    </table>
                </div>
            </div>
        </div>
        <div class="col-12 col-lg-5">
            <div class="card shadow-sm rounded-lg border-0">
                <div class="card-header bg-white fw-semibold">Add Caretaker</div>
                <div class="card-body">
                    <div id="ct-result"></div>
                    <div class="mb-2"><label class="form-label">Full Name</label><input class="form-control" id="ct-name"></div>
                    <div class="mb-2"><label class="form-label">Email</label><input type="email" class="form-control" id="ct-email"></div>
                    <div class="mb-2"><label class="form-label">Username</label><input class="form-control" id="ct-username"></div>
                    <div class="mb-2"><label class="form-label">Phone</label><input class="form-control" id="ct-phone"></div>
                    <div class="mb-3">
                        <label class="form-label">Assign to Property (optional)</label>
                        <select class="form-select" id="ct-property"><option value="">Unassigned for now</option></select>
                        <div class="form-text">One caretaker per property. Only unassigned properties are listed below.</div>
                    </div>
                    <button class="btn btn-primary w-100" id="ct-btn">Create Caretaker</button>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="reassignModal" tabindex="-1">
    <div class="modal-dialog"><div class="modal-content">
        <div class="modal-header"><h5 class="modal-title">Assign Property</h5><button class="btn-close" data-bs-dismiss="modal"></button></div>
        <div class="modal-body">
            <input type="hidden" id="ra-caretaker-id">
            <div id="ra-error"></div>
            <div class="mb-3">
                <label class="form-label">Property</label>
                <select class="form-select" id="ra-property"><option value="">Unassign</option></select>
            </div>
        </div>
        <div class="modal-footer"><button class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button><button class="btn btn-primary" id="ra-submit">Save</button></div>
    </div></div>
</div>
@endsection

@push('scripts')
<script>
    function xsrf() { return decodeURIComponent((document.cookie.match(/XSRF-TOKEN=([^;]+)/) || [])[1] || ''); }

    async function apiFetch(path, options = {}) {
        const method = (options.method || 'GET').toUpperCase();
        if (method !== 'GET') await fetch('/sanctum/csrf-cookie', { credentials: 'include' });
        const res = await fetch(path, {
            credentials: 'include', ...options, method,
            headers: { Accept: 'application/json', 'Content-Type': 'application/json', 'X-XSRF-TOKEN': xsrf(), ...(options.headers || {}) },
        });
        const text = await res.text();
        const body = text ? JSON.parse(text) : null;
        if (!res.ok) throw { message: (body && body.message) || 'Request failed.' };
        return body;
    }

    async function load() {
        try {
            const rows = await apiFetch('/api/landlord/caretakers');
            const body = document.getElementById('ct-body');
            body.innerHTML = rows.length
                ? rows.map((c) => {
                    const u = c.user || {};
                    const propName = c.property ? c.property.name : '<span class="text-muted">Unassigned</span>';
                    return '<tr><td>' + (u.full_name || '-') + '</td><td>' + (u.email || '-') + '</td><td>' + (u.phone || '-') +
                        '</td><td>' + propName + '</td><td class="text-end"><button class="btn btn-sm btn-outline-secondary" data-reassign="' + c.user_id + '">Assign</button></td></tr>';
                }).join('')
                : '<tr><td colspan="5" class="text-muted">No caretakers yet</td></tr>';
        } catch (e) {
            document.getElementById('list-error').innerHTML = '<div class="alert alert-danger">' + e.message + '</div>';
        }
    }

    async function loadUnassignedProperties(selectId, excludeCaretakerId) {
        const properties = await apiFetch('/api/landlord/properties');
        const caretakers = await apiFetch('/api/landlord/caretakers');

        const assignedPropertyIds = caretakers
            .filter((c) => excludeCaretakerId ? c.user_id !== excludeCaretakerId : true)
            .map((c) => c.property_id)
            .filter((id) => id !== null);

        const sel = document.getElementById(selectId);
        const placeholder = sel.querySelector('option');
        sel.innerHTML = '';
        sel.appendChild(placeholder);

        properties
            .filter((p) => !assignedPropertyIds.includes(p.id))
            .forEach((p) => {
                const opt = document.createElement('option');
                opt.value = p.id;
                opt.textContent = p.name;
                sel.appendChild(opt);
            });
    }

    document.getElementById('ct-btn').addEventListener('click', async () => {
        const out = document.getElementById('ct-result');
        out.innerHTML = '';
        try {
            const res = await apiFetch('/api/landlord/caretakers', {
                method: 'POST',
                body: JSON.stringify({
                    full_name: document.getElementById('ct-name').value,
                    email: document.getElementById('ct-email').value,
                    username: document.getElementById('ct-username').value,
                    phone: document.getElementById('ct-phone').value,
                    property_id: document.getElementById('ct-property').value || null,
                }),
            });
            out.innerHTML = '<div class="alert alert-success">Caretaker created.<br>' +
                '<strong>Temporary password:</strong> <code>' + res.password + '</code><br>' +
                '<span class="small text-muted">Share this with the caretaker directly.</span></div>';
            document.getElementById('ct-name').value = '';
            document.getElementById('ct-email').value = '';
            document.getElementById('ct-username').value = '';
            document.getElementById('ct-phone').value = '';
            load();
            loadUnassignedProperties('ct-property', null);
        } catch (e) {
            out.innerHTML = '<div class="alert alert-danger">' + e.message + '</div>';
        }
    });

    document.getElementById('ct-body').addEventListener('click', async (ev) => {
        const id = ev.target.getAttribute('data-reassign');
        if (!id) return;
        document.getElementById('ra-caretaker-id').value = id;
        document.getElementById('ra-error').innerHTML = '';
        await loadUnassignedProperties('ra-property', parseInt(id));
        new bootstrap.Modal(document.getElementById('reassignModal')).show();
    });

    document.getElementById('ra-submit').addEventListener('click', async () => {
        const id = document.getElementById('ra-caretaker-id').value;
        const out = document.getElementById('ra-error');
        try {
            await apiFetch('/api/landlord/caretakers/' + id + '/assign-property', {
                method: 'PUT',
                body: JSON.stringify({ property_id: document.getElementById('ra-property').value || null }),
            });
            bootstrap.Modal.getInstance(document.getElementById('reassignModal')).hide();
            load();
        } catch (e) {
            out.innerHTML = '<div class="alert alert-danger">' + e.message + '</div>';
        }
    });

    load();
    loadUnassignedProperties('ct-property', null);
</script>
@endpush