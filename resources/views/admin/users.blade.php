@extends('layouts.admin')

@section('title', 'User Management')

@section('content')
<div class="p-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="text-2xl font-bold text-primary mb-0">User Management</h1>
        <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#createModal">
            <i class="fas fa-plus me-2"></i>Add User
        </button>
    </div>

    <div id="page-error"></div>

    <div class="card shadow-sm rounded-lg border-0 mb-3">
        <div class="card-body">
            <div class="row g-2">
                <div class="col-auto">
                    <select class="form-select form-select-sm" id="filter-role">
                        <option value="">All Roles</option>
                        <option value="admin">Admin</option>
                        <option value="landlord">Landlord</option>
                        <option value="caretaker">Caretaker</option>
                        <option value="tenant">Tenant</option>
                    </select>
                </div>
                <div class="col-auto">
                    <select class="form-select form-select-sm" id="filter-status">
                        <option value="">All Statuses</option>
                        <option value="active">Active</option>
                        <option value="inactive">Inactive</option>
                    </select>
                </div>
                <div class="col-auto">
                    <button class="btn btn-sm btn-outline-secondary" id="filter-apply">Filter</button>
                </div>
            </div>
        </div>
    </div>

    <div class="card shadow-sm rounded-lg border-0">
        <div class="card-body p-0">
            <table class="table mb-0 align-middle">
                <thead>
                    <tr>
                        <th>Name</th><th>Email</th><th>Role</th><th>Status</th><th class="text-end">Actions</th>
                    </tr>
                </thead>
                <tbody id="users-body">
                    <tr><td colspan="5" class="text-muted">Loading...</td></tr>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Create Modal -->
<div class="modal fade" id="createModal" tabindex="-1">
    <div class="modal-dialog"><div class="modal-content">
        <div class="modal-header"><h5 class="modal-title">Add User</h5><button class="btn-close" data-bs-dismiss="modal"></button></div>
        <div class="modal-body">
            <div id="create-error"></div>
            <div class="mb-2"><label class="form-label">Full Name</label><input class="form-control" id="c-name"></div>
            <div class="mb-2"><label class="form-label">Email</label><input type="email" class="form-control" id="c-email"></div>
            <div class="mb-2"><label class="form-label">Username</label><input class="form-control" id="c-username"></div>
            <div class="mb-2"><label class="form-label">Phone</label><input class="form-control" id="c-phone"></div>
            <div class="mb-2">
                <label class="form-label">Role</label>
                <select class="form-select" id="c-role">
                    <option value="admin">Admin</option>
                    <option value="landlord">Landlord</option>
                    <option value="caretaker">Caretaker</option>
                    <option value="tenant">Tenant</option>
                </select>
            </div>
            <div class="mb-2" id="c-landlord-wrap" style="display:none;">
                <label class="form-label">Assigned Landlord</label>
                <select class="form-select" id="c-landlord"></select>
            </div>
        </div>
        <div class="modal-footer"><button class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button><button class="btn btn-primary" id="create-submit">Create</button></div>
    </div></div>
</div>

<!-- Edit Modal -->
<div class="modal fade" id="editModal" tabindex="-1">
    <div class="modal-dialog"><div class="modal-content">
        <div class="modal-header"><h5 class="modal-title">Edit User</h5><button class="btn-close" data-bs-dismiss="modal"></button></div>
        <div class="modal-body">
            <input type="hidden" id="e-id">
            <div id="edit-error"></div>
            <div class="mb-2"><label class="form-label">Full Name</label><input class="form-control" id="e-name"></div>
            <div class="mb-2"><label class="form-label">Phone</label><input class="form-control" id="e-phone"></div>
            <div class="form-check form-switch mb-2">
                <input class="form-check-input" type="checkbox" id="e-active">
                <label class="form-check-label" for="e-active">Active</label>
            </div>
        </div>
        <div class="modal-footer"><button class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button><button class="btn btn-primary" id="edit-submit">Save</button></div>
    </div></div>
</div>

<!-- Reset Password Modal -->
<div class="modal fade" id="resetModal" tabindex="-1">
    <div class="modal-dialog"><div class="modal-content">
        <div class="modal-header"><h5 class="modal-title">Reset Password</h5><button class="btn-close" data-bs-dismiss="modal"></button></div>
        <div class="modal-body">
            <input type="hidden" id="r-id">
            <div id="reset-error"></div>
            <div class="mb-3">
                <label class="form-label">New Password</label>
                <div class="input-group">
                    <input type="password" class="form-control" id="r-password">
                    <button class="btn btn-outline-secondary toggle-pw" type="button" data-target="r-password"><i class="fas fa-eye"></i></button>
                </div>
            </div>
            <div class="mb-3">
                <label class="form-label">Confirm New Password</label>
                <div class="input-group">
                    <input type="password" class="form-control" id="r-password-confirm">
                    <button class="btn btn-outline-secondary toggle-pw" type="button" data-target="r-password-confirm"><i class="fas fa-eye"></i></button>
                </div>
            </div>
        </div>
        <div class="modal-footer"><button class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button><button class="btn btn-primary" id="reset-submit">Reset Password</button></div>
    </div></div>
</div>

<!-- Delete Modal -->
<div class="modal fade" id="deleteModal" tabindex="-1">
    <div class="modal-dialog"><div class="modal-content">
        <div class="modal-header"><h5 class="modal-title">Delete User</h5><button class="btn-close" data-bs-dismiss="modal"></button></div>
        <div class="modal-body">
            <input type="hidden" id="d-id">
            <div id="delete-error"></div>
            <div class="mb-2"><label class="form-label">Reason (required)</label><textarea class="form-control" id="d-reason" rows="2"></textarea></div>
        </div>
        <div class="modal-footer"><button class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button><button class="btn btn-danger" id="delete-submit">Delete</button></div>
    </div></div>
</div>
@endsection

@push('scripts')
<script>
    function xsrf() { return decodeURIComponent((document.cookie.match(/XSRF-TOKEN=([^;]+)/) || [])[1] || ''); }

    document.addEventListener('click', (ev) => {
        const btn = ev.target.closest('.toggle-pw');
        if (!btn) return;
        const input = document.getElementById(btn.getAttribute('data-target'));
        const icon = btn.querySelector('i');
        if (input.type === 'password') {
            input.type = 'text';
            icon.classList.remove('fa-eye');
            icon.classList.add('fa-eye-slash');
        } else {
            input.type = 'password';
            icon.classList.remove('fa-eye-slash');
            icon.classList.add('fa-eye');
        }
    });

    async function apiFetch(path, options = {}) {
        const method = (options.method || 'GET').toUpperCase();
        if (method !== 'GET') await fetch('/sanctum/csrf-cookie', { credentials: 'include' });
        const res = await fetch(path, {
            credentials: 'include', ...options, method,
            headers: { 'Accept': 'application/json', 'Content-Type': 'application/json', 'X-XSRF-TOKEN': xsrf(), ...(options.headers || {}) },
        });
        const text = await res.text();
        const body = text ? JSON.parse(text) : null;
        if (!res.ok) throw { message: (body && body.message) || 'Request failed.' };
        return body;
    }

    function badge(active) {
        return active ? '<span class="badge bg-success">Active</span>' : '<span class="badge bg-secondary">Inactive</span>';
    }

    async function loadUsers() {
        try {
            const role = document.getElementById('filter-role').value;
            const status = document.getElementById('filter-status').value;
            const params = new URLSearchParams();
            if (role) params.set('role', role);
            if (status) params.set('status', status);

            const page = await apiFetch('/api/admin/users?' + params.toString());
            const items = page.data || [];
            const body = document.getElementById('users-body');

            if (!items.length) {
                body.innerHTML = '<tr><td colspan="5" class="text-muted">No users found</td></tr>';
                return;
            }

            body.innerHTML = items.map((u) =>
                '<tr><td>' + u.full_name + '</td><td>' + u.email + '</td><td><span class="badge bg-secondary">' + u.role + '</span></td><td>' +
                badge(u.is_active) + '</td><td class="text-end">' +
                '<button class="btn btn-sm btn-outline-primary me-1" data-edit=\'' + JSON.stringify(u) + '\'>Edit</button>' +
                '<button class="btn btn-sm btn-outline-warning me-1" data-reset="' + u.id + '">Reset PW</button>' +
                '<button class="btn btn-sm btn-outline-danger" data-delete="' + u.id + '">Delete</button>' +
                '</td></tr>'
            ).join('');
        } catch (e) {
            document.getElementById('page-error').innerHTML = '<div class="alert alert-danger">' + e.message + '</div>';
        }
    }

    document.getElementById('filter-apply').addEventListener('click', loadUsers);

    document.getElementById('c-role').addEventListener('change', async (ev) => {
        const wrap = document.getElementById('c-landlord-wrap');
        if (ev.target.value === 'caretaker' || ev.target.value === 'tenant') {
            wrap.style.display = 'block';
            try {
                const page = await apiFetch('/api/admin/users?role=landlord');
                const landlords = page.data || [];
                document.getElementById('c-landlord').innerHTML = landlords.map((l) =>
                    '<option value="' + l.id + '">' + l.full_name + '</option>'
                ).join('');
            } catch (e) {}
        } else {
            wrap.style.display = 'none';
        }
    });

    document.getElementById('create-submit').addEventListener('click', async () => {
        const out = document.getElementById('create-error');
        out.innerHTML = '';
        try {
            const role = document.getElementById('c-role').value;
            const payload = {
                full_name: document.getElementById('c-name').value,
                email: document.getElementById('c-email').value,
                username: document.getElementById('c-username').value,
                phone: document.getElementById('c-phone').value,
                role: role,
            };
            if (role === 'caretaker' || role === 'tenant') {
                payload.landlord_id = document.getElementById('c-landlord').value;
            }
            const res = await apiFetch('/api/admin/users', { method: 'POST', body: JSON.stringify(payload) });
            bootstrap.Modal.getInstance(document.getElementById('createModal')).hide();
            alert('User created. Temporary password: ' + res.password);
            loadUsers();
        } catch (e) {
            out.innerHTML = '<div class="alert alert-danger">' + e.message + '</div>';
        }
    });

    document.getElementById('users-body').addEventListener('click', async (ev) => {
        const editData = ev.target.getAttribute('data-edit');
        const resetId = ev.target.getAttribute('data-reset');
        const deleteId = ev.target.getAttribute('data-delete');

        if (editData) {
            const u = JSON.parse(editData);
            document.getElementById('e-id').value = u.id;
            document.getElementById('e-name').value = u.full_name;
            document.getElementById('e-phone').value = u.phone || '';
            document.getElementById('e-active').checked = !!u.is_active;
            document.getElementById('edit-error').innerHTML = '';
            new bootstrap.Modal(document.getElementById('editModal')).show();
        }

        if (resetId) {
            document.getElementById('r-id').value = resetId;
            document.getElementById('r-password').value = '';
            document.getElementById('r-password-confirm').value = '';
            document.getElementById('reset-error').innerHTML = '';
            new bootstrap.Modal(document.getElementById('resetModal')).show();
        }

        if (deleteId) {
            document.getElementById('d-id').value = deleteId;
            document.getElementById('d-reason').value = '';
            document.getElementById('delete-error').innerHTML = '';
            new bootstrap.Modal(document.getElementById('deleteModal')).show();
        }
    });

    document.getElementById('edit-submit').addEventListener('click', async () => {
        const id = document.getElementById('e-id').value;
        const out = document.getElementById('edit-error');
        try {
            await apiFetch('/api/admin/users/' + id, {
                method: 'PUT',
                body: JSON.stringify({
                    full_name: document.getElementById('e-name').value,
                    phone: document.getElementById('e-phone').value,
                    is_active: document.getElementById('e-active').checked,
                }),
            });
            bootstrap.Modal.getInstance(document.getElementById('editModal')).hide();
            loadUsers();
        } catch (e) {
            out.innerHTML = '<div class="alert alert-danger">' + e.message + '</div>';
        }
    });

    document.getElementById('reset-submit').addEventListener('click', async () => {
        const id = document.getElementById('r-id').value;
        const out = document.getElementById('reset-error');
        const pw = document.getElementById('r-password').value;
        const confirmPw = document.getElementById('r-password-confirm').value;

        if (pw !== confirmPw) {
            out.innerHTML = '<div class="alert alert-danger">Passwords do not match.</div>';
            return;
        }

        try {
            await apiFetch('/api/admin/users/' + id + '/reset-password', {
                method: 'POST',
                body: JSON.stringify({ password: pw, password_confirmation: confirmPw }),
            });
            bootstrap.Modal.getInstance(document.getElementById('resetModal')).hide();
            alert('Password reset successfully.');
        } catch (e) {
            out.innerHTML = '<div class="alert alert-danger">' + e.message + '</div>';
        }
    });

    document.getElementById('delete-submit').addEventListener('click', async () => {
        const id = document.getElementById('d-id').value;
        const out = document.getElementById('delete-error');
        try {
            await apiFetch('/api/admin/users/' + id, {
                method: 'DELETE',
                body: JSON.stringify({ reason: document.getElementById('d-reason').value }),
            });
            bootstrap.Modal.getInstance(document.getElementById('deleteModal')).hide();
            loadUsers();
        } catch (e) {
            out.innerHTML = '<div class="alert alert-danger">' + e.message + '</div>';
        }
    });

    loadUsers();
</script>
@endpush
