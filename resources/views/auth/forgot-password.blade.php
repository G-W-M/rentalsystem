@extends('layouts.auth')

@section('title', 'Forgot Password')
@section('auth-title', 'Forgot Password')
@section('auth-subtitle', "Enter your email and we'll generate a reset link")

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
            const res = await fetch('/api/forgot-password', {
                method: 'POST',
                credentials: 'include',
                headers: { 'Accept': 'application/json', 'Content-Type': 'application/json' },
                body: JSON.stringify({ email: document.getElementById('fp-email').value }),
            });
            const body = await res.json();

            if (!res.ok) {
                out.innerHTML = '<div class="alert alert-danger">' + (body.message || 'Something went wrong.') + '</div>';
                return;
            }

            if (body.reset_url) {
                out.innerHTML = '<div class="alert alert-success">' + body.message +
                    '<br><a href="' + body.reset_url + '" class="fw-semibold">Click here to reset your password</a></div>';
            } else {
                out.innerHTML = '<div class="alert alert-success">' + body.message + '</div>';
            }
        } catch (e) {
            out.innerHTML = '<div class="alert alert-danger">Network error. Try again.</div>';
        }
    });
</script>
@endsection