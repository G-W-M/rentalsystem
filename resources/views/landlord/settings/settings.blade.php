@extends('layouts.landlord')

@section('title', 'Settings')

@section('content')
<div class="p-4">
    <h1 class="text-2xl font-bold text-primary mb-4">Settings</h1>

    <div class="row g-4">
        <div class="col-12 col-lg-6">
            <div class="card shadow-sm rounded-lg border-0">
                <div class="card-header bg-white fw-semibold">Profile</div>
                <div class="card-body">
                    <div id="profile-result"></div>
                    <div class="mb-3">
                        <label class="form-label">Full Name</label>
                        <input type="text" class="form-control" id="full_name">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Email</label>
                        <input type="email" class="form-control" id="email" disabled>
                        <div class="form-text">Email cannot be changed here. Contact support if needed.</div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Phone</label>
                        <input type="text" class="form-control" id="phone">
                    </div>
                    <button class="btn btn-primary" id="profile-save">Save Changes</button>
                </div>
            </div>
        </div>

        <div class="col-12 col-lg-6">
            <div class="card shadow-sm rounded-lg border-0">
                <div class="card-header bg-white fw-semibold">Change Password</div>
                <div class="card-body">
                    <div id="password-result"></div>

                    <div class="mb-3">
                        <label class="form-label">Current Password</label>
                        <div class="input-group">
                            <input type="password" class="form-control" id="current_password">
                            <button class="btn btn-outline-secondary toggle-pw" type="button" data-target="current_password">
                                <i class="fas fa-eye"></i>
                            </button>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">New Password</label>
                        <div class="input-group">
                            <input type="password" class="form-control" id="new_password">
                            <button class="btn btn-outline-secondary toggle-pw" type="button" data-target="new_password">
                                <i class="fas fa-eye"></i>
                            </button>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Confirm New Password</label>
                        <div class="input-group">
                            <input type="password" class="form-control" id="new_password_confirmation">
                            <button class="btn btn-outline-secondary toggle-pw" type="button" data-target="new_password_confirmation">
                                <i class="fas fa-eye"></i>
                            </button>
                        </div>
                    </div>

                    <button class="btn btn-primary" id="password-save">Update Password</button>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    function xsrf() { return decodeURIComponent((document.cookie.match(/XSRF-TOKEN=([^;]+)/) || [])[1] || ''); }

    document.querySelectorAll('.toggle-pw').forEach((btn) => {
        btn.addEventListener('click', () => {
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

    async function loadProfile() {
        try {
            const u = await apiFetch('/api/me');
            document.getElementById('full_name').value = u.full_name || '';
            document.getElementById('email').value = u.email || '';
            document.getElementById('phone').value = u.phone || '';
        } catch (e) {
            document.getElementById('profile-result').innerHTML = '<div class="alert alert-danger">' + e.message + '</div>';
        }
    }

    document.getElementById('profile-save').addEventListener('click', async () => {
        const out = document.getElementById('profile-result');
        out.innerHTML = '';
        try {
            await apiFetch('/api/me', {
                method: 'PUT',
                body: JSON.stringify({
                    full_name: document.getElementById('full_name').value,
                    phone: document.getElementById('phone').value,
                }),
            });
            out.innerHTML = '<div class="alert alert-success">Profile updated.</div>';
        } catch (e) {
            out.innerHTML = '<div class="alert alert-danger">' + e.message + '</div>';
        }
    });

    document.getElementById('password-save').addEventListener('click', async () => {
        const out = document.getElementById('password-result');
        out.innerHTML = '';
        const newPass = document.getElementById('new_password').value;
        const confirmPass = document.getElementById('new_password_confirmation').value;

        if (newPass !== confirmPass) {
            out.innerHTML = '<div class="alert alert-danger">New passwords do not match.</div>';
            return;
        }

        try {
            await apiFetch('/api/me/password', {
                method: 'PUT',
                body: JSON.stringify({
                    current_password: document.getElementById('current_password').value,
                    password: newPass,
                    password_confirmation: confirmPass,
                }),
            });
            out.innerHTML = '<div class="alert alert-success">Password updated.</div>';
            document.getElementById('current_password').value = '';
            document.getElementById('new_password').value = '';
            document.getElementById('new_password_confirmation').value = '';
        } catch (e) {
            out.innerHTML = '<div class="alert alert-danger">' + e.message + '</div>';
        }
    });

    loadProfile();
</script>
@endpush
