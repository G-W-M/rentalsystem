@extends('layouts.auth')

@section('title', 'Login')
@section('auth-title', 'Welcome')
@section('auth-subtitle', 'Sign in to your account')

@section('auth-content')
<div id="login-error"></div>

<div class="mb-3">
    <label class="form-label">Email</label>
    <input type="email" class="form-control" id="email" autocomplete="username">
</div>
<div class="mb-3">
    <label class="form-label">Password</label>
    <div class="input-group">
        <input type="password" class="form-control" id="password" autocomplete="current-password">
        <button class="btn btn-outline-secondary toggle-pw" type="button" data-target="password">
            <i class="fas fa-eye"></i>
        </button>
    </div>
</div>

<button class="btn btn-primary w-100" id="login-btn">Sign In</button>

<div class="text-center mt-3">
    <a href="{{ route('password.request') }}" class="small">Forgot password?</a>
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

    const roleHome = {
        admin: '/admin/dashboard',
        landlord: '/landlord/dashboard',
        caretaker: '/caretaker/dashboard',
        tenant: '/tenant/dashboard',
    };

    async function doLogin() {
        const out = document.getElementById('login-error');
        out.innerHTML = '';
        try {
            await fetch('/sanctum/csrf-cookie', { credentials: 'include' });

            const token = decodeURIComponent(
                (document.cookie.match(/XSRF-TOKEN=([^;]+)/) || [])[1] || ''
            );

            const res = await fetch('/api/login', {
                method: 'POST',
                credentials: 'include',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-XSRF-TOKEN': token,
                },
                body: JSON.stringify({
                    email: document.getElementById('email').value,
                    password: document.getElementById('password').value,
                }),
            });

            const data = await res.json();

            if (!res.ok) {
                out.innerHTML = '<div class="alert alert-danger">' +
                    (data.message || 'Login failed.') + '</div>';
                return;
            }

            const role = data.user ? data.user.role : 'tenant';
            window.location.href = roleHome[role] || '/tenant/dashboard';
        } catch (e) {
            out.innerHTML = '<div class="alert alert-danger">Network error. Try again.</div>';
        }
    }

    document.getElementById('login-btn').addEventListener('click', doLogin);
    document.getElementById('password').addEventListener('keydown', (e) => {
        if (e.key === 'Enter') doLogin();
    });
</script>
@endsection
