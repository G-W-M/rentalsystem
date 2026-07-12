@extends('layouts.auth')

@section('title', 'Forgot Password')
@section('auth-title', 'Forgot Password')
@section('auth-subtitle', "Enter your email and we'll send you a reset link")

@section('auth-content')
    <div id="fp-result"></div>

    <div class="mb-3">
        <label class="form-label">Email</label>
        <input type="email" class="form-control" id="fp-email" autocomplete="username">
    </div>

    <button class="btn btn-primary w-100" id="fp-btn">Send Reset Link</button>

    <div class="text-center mt-3">
        <a href="{{ route('login') }}" class="small">Back to login</a>
    </div>

    <script>
        document.getElementById('fp-btn').addEventListener('click', async () => {
            const out = document.getElementById('fp-result');
            out.innerHTML = '';
            try {
                // Fetch the CSRF cookie first — required before any stateful
                // POST to /api/*, same as the login page does. This was
                // missing here, which caused "CSRF token mismatch."
                await fetch('/sanctum/csrf-cookie', {
                    credentials: 'include'
                });

                const token = decodeURIComponent(
                    (document.cookie.match(/XSRF-TOKEN=([^;]+)/) || [])[1] || ''
                );

                const res = await fetch('/api/forgot-password', {
                    method: 'POST',
                    credentials: 'include',
                    headers: {
                        'Accept': 'application/json',
                        'Content-Type': 'application/json',
                        'X-XSRF-TOKEN': token,
                    },
                    body: JSON.stringify({
                        email: document.getElementById('fp-email').value
                    }),
                });
                const body = await res.json();

                if (!res.ok) {
                    out.innerHTML = '<div class="alert alert-danger">' + (body.message ||
                        'Something went wrong.') + '</div>';
                    return;
                }

                // reset_url is no longer returned — the link is emailed now.
                out.innerHTML = '<div class="alert alert-success">' + body.message + '</div>';
            } catch (e) {
                out.innerHTML = '<div class="alert alert-danger">Network error. Try again.</div>';
            }
        });
    </script>
@endsection
