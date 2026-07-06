@extends('layouts.auth')

@section('title', 'Reset Password')
@section('auth-title', 'Reset Password')
@section('auth-subtitle', 'Choose a new password for your account')

@section('auth-content')
<div id="rp-result"></div>

<div class="mb-3">
    <label class="form-label">Email</label>
    <input type="email" class="form-control" id="rp-email" autocomplete="username">
</div>
<div class="mb-3">
    <label class="form-label">New Password</label>
    <div class="input-group">
        <input type="password" class="form-control" id="rp-password" autocomplete="new-password">
        <button class="btn btn-outline-secondary toggle-pw" type="button" data-target="rp-password">
            <i class="fas fa-eye"></i>
        </button>
    </div>
</div>
<div class="mb-3">
    <label class="form-label">Confirm Password</label>
    <div class="input-group">
        <input type="password" class="form-control" id="rp-password-confirm" autocomplete="new-password">
        <button class="btn btn-outline-secondary toggle-pw" type="button" data-target="rp-password-confirm">
            <i class="fas fa-eye"></i>
        </button>
    </div>
</div>

<button class="btn btn-primary w-100" id="rp-btn">Reset Password</button>

<div class="text-center mt-3">
    <a href="{{ route('login') }}" class="small">Back to login</a>
</div>

<script>
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

    function tokenFromUrl() {
        const params = new URLSearchParams(window.location.search);
        return params.get('token') || '';
    }

    document.getElementById('rp-btn').addEventListener('click', async () => {
        const out = document.getElementById('rp-result');
        out.innerHTML = '';
        try {
            const res = await fetch('/api/reset-password', {
                method: 'POST',
                credentials: 'include',
                headers: { 'Accept': 'application/json', 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    token: tokenFromUrl(),
                    email: document.getElementById('rp-email').value,
                    password: document.getElementById('rp-password').value,
                    password_confirmation: document.getElementById('rp-password-confirm').value,
                }),
            });
            const body = await res.json();
            if (!res.ok) {
                out.innerHTML = '<div class="alert alert-danger">' + (body.message || 'Reset failed.') + '</div>';
                return;
            }
            out.innerHTML = '<div class="alert alert-success">' + body.message + '</div>';
        } catch (e) {
            out.innerHTML = '<div class="alert alert-danger">Network error. Try again.</div>';
        }
    });
</script>
@endsection
