@extends('layouts.caretaker')

@section('title', 'Settings')

@section('content')
<div class="p-4">
    <h1 class="text-2xl font-bold text-primary mb-4">Settings</h1>
    <div class="card shadow-sm rounded-lg border-0" style="max-width:560px;">
        <div class="card-body" id="profile-block">
            <div class="text-muted">Loading your profile...</div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
(async () => {
    try {
        const res = await fetch('/api/me', { credentials: 'include', headers: { Accept: 'application/json' } });
        const u = await res.json();
        document.getElementById('profile-block').innerHTML =
            '<dl class="row mb-0">' +
            '<dt class="col-4 text-gray-600">Name</dt><dd class="col-8">' + (u.full_name || '-') + '</dd>' +
            '<dt class="col-4 text-gray-600">Email</dt><dd class="col-8">' + (u.email || '-') + '</dd>' +
            '<dt class="col-4 text-gray-600">Phone</dt><dd class="col-8">' + (u.phone || '-') + '</dd>' +
            '<dt class="col-4 text-gray-600">Role</dt><dd class="col-8">' + (u.role || '-') + '</dd>' +
            '</dl>';
    } catch (e) {
        document.getElementById('profile-block').innerHTML = '<div class="alert alert-danger">Could not load profile.</div>';
    }
})();
</script>
@endpush
