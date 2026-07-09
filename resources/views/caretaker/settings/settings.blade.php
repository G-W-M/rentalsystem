@extends('layouts.caretaker')

@section('title', 'Settings')

@section('content')
<div class="container-fluid py-4">

    <div class="d-flex align-items-center gap-3 mb-4">
        <div class="rounded-circle d-flex align-items-center justify-content-center text-white fw-bold"
             style="width:52px;height:52px;background:var(--primary-dark);font-size:1.3rem;" id="avatar-initials">—</div>
        <div>
            <h1 class="h4 fw-bold text-primary-dark mb-0" id="header-name">—</h1>
            <p class="text-muted small mb-0" id="header-email">—</p>
        </div>
    </div>

    <div class="row g-4">

        {{-- ── Profile Card ── --}}
        <div class="col-12 col-lg-6">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-header bg-white border-bottom fw-semibold text-primary-dark">
                    <i class="fas fa-user-circle me-2"></i> Edit Profile
                </div>
                <div class="card-body">
                    <div id="profile-result" class="mb-3"></div>

                    <div class="mb-3">
                        <label class="form-label fw-medium">Full Name</label>
                        <input type="text" class="form-control" id="full_name" placeholder="Your full name">
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-medium">Email address</label>
                        <input type="email" class="form-control" id="email" placeholder="your@email.com">
                        <div class="form-text">Changing your email will require you to log in again.</div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-medium">Phone number</label>
                        <input type="text" class="form-control" id="phone" placeholder="+254 7XX XXX XXX">
                    </div>

                    <button class="btn btn-primary d-inline-flex align-items-center gap-2" id="profile-save">
                        <span id="profile-spinner" class="spinner-border spinner-border-sm d-none" role="status"></span>
                        <span>Save Changes</span>
                    </button>
                </div>
            </div>
        </div>

        {{-- ── Password Card ── --}}
        <div class="col-12 col-lg-6">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-header bg-white border-bottom fw-semibold text-primary-dark">
                    <i class="fas fa-lock me-2"></i> Change Password
                </div>
                <div class="card-body">
                    <div id="password-result" class="mb-3"></div>

                    <div class="mb-3">
                        <label class="form-label fw-medium">Current password</label>
                        <div class="input-group">
                            <input type="password" class="form-control" id="current_password" placeholder="Enter current password">
                            <button class="btn btn-outline-secondary toggle-pw" type="button" data-target="current_password">
                                <i class="fas fa-eye"></i>
                            </button>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-medium">New password</label>
                        <div class="input-group">
                            <input type="password" class="form-control" id="new_password" placeholder="Min. 8 characters">
                            <button class="btn btn-outline-secondary toggle-pw" type="button" data-target="new_password">
                                <i class="fas fa-eye"></i>
                            </button>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-medium">Confirm new password</label>
                        <div class="input-group">
                            <input type="password" class="form-control" id="new_password_confirmation" placeholder="Repeat new password">
                            <button class="btn btn-outline-secondary toggle-pw" type="button" data-target="new_password_confirmation">
                                <i class="fas fa-eye"></i>
                            </button>
                        </div>
                    </div>

                    <button class="btn btn-primary d-inline-flex align-items-center gap-2" id="password-save">
                        <span id="password-spinner" class="spinner-border spinner-border-sm d-none" role="status"></span>
                        <span>Update Password</span>
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
(function () {
  'use strict';

  function xsrf() {
    return decodeURIComponent((document.cookie.match(/XSRF-TOKEN=([^;]+)/) || [])[1] || '');
  }

  async function apiFetch(path, options = {}) {
    const method = (options.method || 'GET').toUpperCase();
    if (method !== 'GET') await fetch('/sanctum/csrf-cookie', { credentials: 'include' });
    const res = await fetch(path, {
      credentials: 'include', ...options, method,
      headers: {
        'Accept': 'application/json',
        'Content-Type': 'application/json',
        'X-XSRF-TOKEN': xsrf(),
        ...(options.headers || {}),
      },
    });
    const text = await res.text();
    const body = text ? JSON.parse(text) : null;
    if (!res.ok) throw { message: (body && body.message) || 'Request failed.' };
    return body;
  }

  function showMsg(elId, type, msg) {
    const el = document.getElementById(elId);
    el.innerHTML = '<div class="alert alert-' + type + ' py-2 small">' + msg + '</div>';
    setTimeout(() => { el.innerHTML = ''; }, 4000);
  }

  function setLoading(btnId, spinnerId, on) {
    document.getElementById(btnId).disabled = on;
    document.getElementById(spinnerId).classList.toggle('d-none', !on);
  }

  function getInitials(name) {
    if (!name) return '?';
    return name.trim().split(' ').map(w => w[0]).join('').toUpperCase().slice(0, 2);
  }

  /* ── Load profile ── */
  async function loadProfile() {
    try {
      const u = await apiFetch('/api/me');
      document.getElementById('full_name').value = u.full_name || '';
      document.getElementById('email').value     = u.email || '';
      document.getElementById('phone').value     = u.phone || '';
      document.getElementById('header-name').textContent  = u.full_name || '—';
      document.getElementById('header-email').textContent = u.email || '—';
      document.getElementById('avatar-initials').textContent = getInitials(u.full_name);
    } catch (e) {
      showMsg('profile-result', 'danger', e.message);
    }
  }

  /* ── Save profile (name + email + phone) ── */
  document.getElementById('profile-save').addEventListener('click', async () => {
    setLoading('profile-save', 'profile-spinner', true);
    try {
      const u = await apiFetch('/api/me', {
        method: 'PUT',
        body: JSON.stringify({
          full_name : document.getElementById('full_name').value.trim(),
          email     : document.getElementById('email').value.trim(),
          phone     : document.getElementById('phone').value.trim(),
        }),
      });
      // Update header immediately
      document.getElementById('header-name').textContent     = u.full_name || document.getElementById('full_name').value;
      document.getElementById('header-email').textContent    = u.email     || document.getElementById('email').value;
      document.getElementById('avatar-initials').textContent = getInitials(document.getElementById('full_name').value);
      showMsg('profile-result', 'success', 'Profile updated successfully.');
    } catch (e) {
      showMsg('profile-result', 'danger', e.message);
    } finally {
      setLoading('profile-save', 'profile-spinner', false);
    }
  });

  /* ── Change password ── */
  document.getElementById('password-save').addEventListener('click', async () => {
    const newPass     = document.getElementById('new_password').value;
    const confirmPass = document.getElementById('new_password_confirmation').value;

    if (!newPass) {
      showMsg('password-result', 'warning', 'Please enter a new password.');
      return;
    }
    if (newPass !== confirmPass) {
      showMsg('password-result', 'danger', 'New passwords do not match.');
      return;
    }
    if (newPass.length < 8) {
      showMsg('password-result', 'warning', 'Password must be at least 8 characters.');
      return;
    }

    setLoading('password-save', 'password-spinner', true);
    try {
      await apiFetch('/api/me/password', {
        method: 'PUT',
        body: JSON.stringify({
          current_password      : document.getElementById('current_password').value,
          password              : newPass,
          password_confirmation : confirmPass,
        }),
      });
      showMsg('password-result', 'success', 'Password updated successfully.');
      document.getElementById('current_password').value       = '';
      document.getElementById('new_password').value           = '';
      document.getElementById('new_password_confirmation').value = '';
    } catch (e) {
      showMsg('password-result', 'danger', e.message);
    } finally {
      setLoading('password-save', 'password-spinner', false);
    }
  });

  /* ── Password show/hide toggle ── */
  document.querySelectorAll('.toggle-pw').forEach(btn => {
    btn.addEventListener('click', () => {
      const input = document.getElementById(btn.getAttribute('data-target'));
      const icon  = btn.querySelector('i');
      const show  = input.type === 'password';
      input.type  = show ? 'text' : 'password';
      icon.classList.toggle('fa-eye',       !show);
      icon.classList.toggle('fa-eye-slash',  show);
    });
  });

  loadProfile();
})();
</script>
@endpush