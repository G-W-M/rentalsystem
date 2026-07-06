@extends('layouts.landlord')

@section('title', 'Caretakers')

@section('content')
<div class="p-4">
    <h1 class="text-2xl font-bold text-primary mb-4">Caretakers</h1>
    <div class="row g-4">
        <div class="col-12 col-lg-7">
            <div class="card shadow-sm rounded-lg border-0">
                <div class="card-body p-0">
                    <table class="table mb-0 align-middle">
                        <thead><tr><th>Name</th><th>Email</th><th>Phone</th></tr></thead>
                        <tbody id="ct-body"><tr><td colspan="3" class="text-muted">Loading...</td></tr></tbody>
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
                    <div class="mb-3"><label class="form-label">Password</label><input type="password" class="form-control" id="ct-password"></div>
                    <button class="btn btn-primary w-100" id="ct-btn">Create Caretaker</button>
                </div>
            </div>
        </div>
    </div>
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
            headers: { 'Accept': 'application/json', 'Content-Type': 'application/json', 'X-XSRF-TOKEN': xsrf(), ...(options.headers || {}) },
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
            if (!rows.length) { body.innerHTML = '<tr><td colspan="3" class="text-muted">No caretakers yet</td></tr>'; return; }
            body.innerHTML = rows.map((c) => {
                const u = c.user || {};
                return '<tr><td>' + (u.full_name || '-') + '</td><td>' + (u.email || '-') + '</td><td>' + (u.phone || '-') + '</td></tr>';
            }).join('');
        } catch (e) {
            document.getElementById('ct-result').innerHTML = '<div class="alert alert-danger">' + e.message + '</div>';
        }
    }

    document.getElementById('ct-btn').addEventListener('click', async () => {
        const out = document.getElementById('ct-result');
        out.innerHTML = '';
        try {
            await apiFetch('/api/landlord/caretakers', {
                method: 'POST',
                body: JSON.stringify({
                    full_name: document.getElementById('ct-name').value,
                    email: document.getElementById('ct-email').value,
                    username: document.getElementById('ct-username').value,
                    phone: document.getElementById('ct-phone').value,
                    password: document.getElementById('ct-password').value,
                }),
            });
            out.innerHTML = '<div class="alert alert-success">Caretaker created.</div>';
            load();
        } catch (e) {
            out.innerHTML = '<div class="alert alert-danger">' + e.message + '</div>';
        }
    });

    load();
</script>
@endpush
