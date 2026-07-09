@extends('layouts.caretaker')

@section('title', 'Payments')

@section('content')
<div class="container-fluid py-4">

    {{-- Page header --}}
    <div class="d-flex align-items-center justify-content-between mb-4">
        <div>
            <h1 class="h4 fw-bold text-primary-dark mb-1">Payments</h1>
            <p class="text-muted mb-0">Verify tenant payments for your property.</p>
        </div>
    </div>

    {{-- ── PENDING VERIFICATION ── --}}
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-header bg-white border-bottom d-flex align-items-center justify-content-between">
            <h2 class="h6 fw-semibold mb-0 text-primary-dark">
                <i class="fas fa-clock me-2 text-warning"></i> Awaiting Verification
            </h2>
            <span id="pending-count" class="badge bg-warning text-dark">0</span>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Tenant</th>
                            <th>Unit</th>
                            <th>Amount (KES)</th>
                            <th>Due Date</th>
                            <th>Method</th>
                            <th class="text-end">Action</th>
                        </tr>
                    </thead>
                    <tbody id="pending-body">
                        <tr>
                            <td colspan="6" class="text-center text-muted py-4">
                                <span class="spinner-border spinner-border-sm me-2"></span>Loading…
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    {{-- ── VERIFIED HISTORY ── --}}
    <div class="card border-0 shadow-sm">
        <div class="card-header bg-white border-bottom">
            <h2 class="h6 fw-semibold mb-0 text-primary-dark">
                <i class="fas fa-circle-check me-2 text-success"></i> Verified Payments
            </h2>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Tenant</th>
                            <th>Unit</th>
                            <th>Amount (KES)</th>
                            <th>Verified On</th>
                            <th class="text-end">Receipt</th>
                        </tr>
                    </thead>
                    <tbody id="history-body">
                        <tr>
                            <td colspan="5" class="text-center text-muted py-4">
                                <span class="spinner-border spinner-border-sm me-2"></span>Loading…
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

{{-- ── VERIFY MODAL ── --}}
<div class="modal fade" id="verifyModal" tabindex="-1" aria-labelledby="verifyModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg">
            <div class="modal-header text-white" style="background: var(--primary-dark);">
                <h5 class="modal-title" id="verifyModalLabel">Verify Payment</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                {{-- Read-only payment summary --}}
                <div class="bg-light rounded p-3 mb-4">
                    <div class="row g-2">
                        <div class="col-6">
                            <p class="text-muted small mb-0">Tenant</p>
                            <p class="fw-semibold mb-0" id="vm-tenant">—</p>
                        </div>
                        <div class="col-6">
                            <p class="text-muted small mb-0">Unit</p>
                            <p class="fw-semibold mb-0" id="vm-unit">—</p>
                        </div>
                        <div class="col-6">
                            <p class="text-muted small mb-0">Amount</p>
                            <p class="fw-semibold mb-0 text-primary-dark" id="vm-amount">—</p>
                        </div>
                        <div class="col-6">
                            <p class="text-muted small mb-0">Method</p>
                            <p class="fw-semibold mb-0 text-capitalize" id="vm-method">—</p>
                        </div>
                    </div>
                </div>

                <div class="alert alert-info py-2 small">
                    <i class="fas fa-info-circle me-1"></i>
                    Enter the transaction code the tenant gave you. It must exactly match
                    what they submitted — this is your independent confirmation.
                </div>

                <div class="mb-2">
                    <label for="vm-code" class="form-label fw-medium">Transaction code</label>
                    <input type="text"
                           class="form-control form-control-lg font-monospace"
                           id="vm-code"
                           placeholder="e.g. MPESA123456"
                           autocomplete="off"
                           autocapitalize="characters">
                </div>

                <div id="vm-msg" class="small mt-2" style="min-height:1.2em;"></div>
            </div>
            <div class="modal-footer border-top">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" id="vm-confirm" class="btn btn-success d-inline-flex align-items-center gap-2">
                    <span id="vm-spinner" class="spinner-border spinner-border-sm d-none" role="status"></span>
                    <span id="vm-label"><i class="fas fa-check me-1"></i>Confirm & Verify</span>
                </button>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
(function () {
  'use strict';

  /* ── helpers ─────────────────────────────────────────────── */
  function xsrf() {
    return decodeURIComponent((document.cookie.match(/XSRF-TOKEN=([^;]+)/) || [])[1] || '');
  }
  function fmt(iso) {
    if (!iso) return '—';
    const d = new Date(iso);
    return isNaN(d.getTime()) ? iso : d.toLocaleDateString(undefined, { year:'numeric', month:'short', day:'numeric' });
  }
  function money(v) {
    return 'KES ' + Number(v || 0).toLocaleString('en-KE', { minimumFractionDigits: 2 });
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
    if (!res.ok) throw { status: res.status, message: (body && body.message) || 'Request failed.' };
    return body;
  }

  /* ── modal refs ──────────────────────────────────────────── */
  const modalEl   = document.getElementById('verifyModal');
  const modal     = new bootstrap.Modal(modalEl);
  const vmTenant  = document.getElementById('vm-tenant');
  const vmUnit    = document.getElementById('vm-unit');
  const vmAmount  = document.getElementById('vm-amount');
  const vmMethod  = document.getElementById('vm-method');
  const vmCode    = document.getElementById('vm-code');
  const vmMsg     = document.getElementById('vm-msg');
  const vmConfirm = document.getElementById('vm-confirm');
  const vmSpinner = document.getElementById('vm-spinner');
  const vmLbl     = document.getElementById('vm-label');

  let activePaymentId = null;

  function setLoading(on) {
    vmSpinner.classList.toggle('d-none', !on);
    vmConfirm.disabled = on;
    vmLbl.innerHTML = on
      ? 'Verifying…'
      : '<i class="fas fa-check me-1"></i>Confirm & Verify';
  }

  /* ── load pending payments (caretaker's property only) ───── */
  async function loadPending() {
    const tbody = document.getElementById('pending-body');
    const badge = document.getElementById('pending-count');
    try {
      const data = await apiFetch('/api/caretaker/payments/pending');
      const items = data.data || (Array.isArray(data) ? data : []);
      badge.textContent = items.length;

      if (!items.length) {
        tbody.innerHTML = `
          <tr>
            <td colspan="6" class="text-center text-muted py-5">
              <i class="fas fa-circle-check fa-2x d-block mb-2 text-success opacity-50"></i>
              No payments awaiting verification.
            </td>
          </tr>`;
        return;
      }

      tbody.innerHTML = items.map(p => {
        const tenant = p.tenant?.user?.full_name ?? '—';
        const unit   = p.unit?.unit_number ?? '—';
        return `
          <tr>
            <td class="fw-medium">${tenant}</td>
            <td>${unit}</td>
            <td>${money(p.amount)}</td>
            <td>${fmt(p.due_date)}</td>
            <td class="text-capitalize">${p.payment_method ?? '—'}</td>
            <td class="text-end">
              <button class="btn btn-sm btn-success verify-btn"
                data-id="${p.id}"
                data-tenant="${tenant}"
                data-unit="${unit}"
                data-amount="${p.amount}"
                data-method="${p.payment_method ?? ''}">
                <i class="fas fa-check me-1"></i>Verify
              </button>
            </td>
          </tr>`;
      }).join('');
    } catch (e) {
      tbody.innerHTML = `<tr><td colspan="6"><div class="alert alert-danger m-3">${e.message}</div></td></tr>`;
    }
  }

  /* ── load verified history ───────────────────────────────── */
  async function loadHistory() {
    const tbody = document.getElementById('history-body');
    try {
      const data  = await apiFetch('/api/caretaker/payments/verified');
      const items = data.data || (Array.isArray(data) ? data : []);

      tbody.innerHTML = items.length
        ? items.map(p => `
            <tr>
              <td class="fw-medium">${p.tenant?.user?.full_name ?? '—'}</td>
              <td>${p.unit?.unit_number ?? '—'}</td>
              <td>${money(p.amount)}</td>
              <td>${fmt(p.verified_at)}</td>
              <td class="text-end">
                <span class="badge bg-success-light text-success border border-success border-opacity-25">
                  ${p.receipt_url ?? '—'}
                </span>
              </td>
            </tr>`).join('')
        : `<tr><td colspan="5" class="text-center text-muted py-4">No verified payments yet.</td></tr>`;
    } catch (e) {
      document.getElementById('history-body').innerHTML =
        `<tr><td colspan="5"><div class="alert alert-danger m-3">${e.message}</div></td></tr>`;
    }
  }

  /* ── open verify modal from row button ───────────────────── */
  document.getElementById('pending-body').addEventListener('click', e => {
    const btn = e.target.closest('.verify-btn');
    if (!btn) return;

    activePaymentId   = btn.dataset.id;
    vmTenant.textContent = btn.dataset.tenant;
    vmUnit.textContent   = btn.dataset.unit;
    vmAmount.textContent = money(btn.dataset.amount);
    vmMethod.textContent = btn.dataset.method || '—';
    vmCode.value         = '';
    vmMsg.textContent    = '';
    vmMsg.className      = 'small mt-2';
    modal.show();
    setTimeout(() => vmCode.focus(), 400);
  });

  /* ── submit verification ─────────────────────────────────── */
  vmConfirm.addEventListener('click', async () => {
    const code = vmCode.value.trim();
    if (!code) {
      vmMsg.textContent = 'Please enter the transaction code.';
      vmMsg.className   = 'small mt-2 text-danger';
      return;
    }

    setLoading(true);
    vmMsg.textContent = '';

    try {
      const data = await apiFetch(`/api/caretaker/payments/${activePaymentId}/verify`, {
        method: 'POST',
        body: JSON.stringify({ transaction_code: code }),
      });

      vmMsg.textContent = data.message || 'Payment verified successfully.';
      vmMsg.className   = 'small mt-2 text-success';

      setTimeout(() => {
        modal.hide();
        loadPending();
        loadHistory();
      }, 1200);
    } catch (e) {
      vmMsg.textContent = e.message || 'Verification failed.';
      vmMsg.className   = 'small mt-2 text-danger';
    } finally {
      setLoading(false);
    }
  });

  // Clear message when modal closes
  modalEl.addEventListener('hidden.bs.modal', () => {
    vmMsg.textContent = '';
    vmCode.value = '';
    activePaymentId = null;
  });

  /* ── init ────────────────────────────────────────────────── */
  loadPending();
  loadHistory();
})();
</script>
@endpush