@extends('layouts.auth')

@section('title', 'Register')
@section('auth-title', 'Create Landlord Account')
@section('auth-subtitle', 'Register to start managing your properties')

@section('auth-content')
<div id="reg-error"></div>

<div class="mb-3"><label class="form-label">Full Name</label><input class="form-control" id="r-name"></div>
<div class="mb-3"><label class="form-label">Email</label><input type="email" class="form-control" id="r-email"></div>
<div class="mb-3"><label class="form-label">Username</label><input class="form-control" id="r-username"></div>
<div class="mb-3"><label class="form-label">Phone</label><input class="form-control" id="r-phone"></div>
<div class="mb-3">
    <label class="form-label">Password</label>
    <div class="input-group">
        <input type="password" class="form-control" id="r-password">
        <button class="btn btn-outline-secondary toggle-pw" type="button" data-target="r-password"><i class="fas fa-eye"></i></button>
    </div>
</div>
<div class="mb-3">
    <label class="form-label">Confirm Password</label>
    <div class="input-group">
        <input type="password" class="form-control" id="r-password-confirm">
        <button class="btn btn-outline-secondary toggle-pw" type="button" data-target="r-password-confirm"><i class="fas fa-eye"></i></button>
    </div>
</div>

<button class="btn btn-primary w-100" id="reg-btn">Create Account</button>

<div class="text-center mt-3">
    <span class="small">Already have an account? <a href="{{ route('login') }}">Sign in</a></span>
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

    function xsrf() { return decodeURIComponent((document.cookie.match(/XSRF-TOKEN=([^;]+)/) || [])[1] || ''); }

    document.getElementById('reg-btn').addEventListener('click', async () => {
        const out = document.getElementById('reg-error');
        out.innerHTML = '';

        const pw = document.getElementById('r-password').value;
        const confirmPw = document.getElementById('r-password-confirm').value;
        if (pw !== confirmPw) {
            out.innerHTML = '<div class="alert alert-danger">Passwords do not match.</div>';
            return;
        }

        try {
            await fetch('/sanctum/csrf-cookie', { credentials: 'include' });

            const res = await fetch('/api/register', {
                method: 'POST',
                credentials: 'include',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-XSRF-TOKEN': xsrf(),
                },
                body: JSON.stringify({
                    full_name: document.getElementById('r-name').value,
                    email: document.getElementById('r-email').value,
                    username: document.getElementById('r-username').value,
                    phone: document.getElementById('r-phone').value,
                    password: pw,
                    password_confirmation: confirmPw,
                }),
            });

            const body = await res.json();

            if (!res.ok) {
                out.innerHTML = '<div class="alert alert-danger">' + (body.message || 'Registration failed.') + '</div>';
                return;
            }

            window.location.href = '/landlord/dashboard';
        } catch (e) {
            out.innerHTML = '<div class="alert alert-danger">Network error. Try again.</div>';
        }
    });
</script>
@endsection