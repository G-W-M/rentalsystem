@extends('layouts.tenant')

{{-- resources/views/tenant/payments.blade.php --}}

@section('title', 'Payments')

@section('content')
<div class="tenant-payments container-fluid py-4">

    {{-- Page header --}}
    <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-4">
        <div>
            <h1 class="h4 fw-bold text-primary-dark mb-1">Payments</h1>
            <p class="text-muted mb-0">View your rent history and make payments.</p>
        </div>
        <button id="payRentBtn" class="btn btn-primary d-inline-flex align-items-center gap-2">
            <i class="fas fa-credit-card"></i>
            <span>Pay Rent</span>
        </button>
    </div>

    {{-- Global error / status feedback --}}
    <div id="pay-alert"></div>
    <p id="payRentHint" class="text-muted small mb-3" style="min-height:1.2em;"></p>

    {{-- Payment history --}}
    <div class="card border-0 shadow-sm">
        <div class="card-header bg-white border-bottom d-flex align-items-center justify-content-between">
            <h2 class="h6 fw-semibold mb-0 text-primary-dark">
                <i class="fas fa-clock-rotate-left me-2"></i> Payment History
            </h2>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th scope="col">Billing Month</th>
                            <th scope="col">Amount (KES)</th>
                            <th scope="col">Due Date</th>
                            <th scope="col">Method</th>
                            <th scope="col">Status</th>
                        </tr>
                    </thead>
                    <tbody id="pay-body">
                        <tr>
                            <td colspan="5" class="text-center text-muted py-4">
                                <span class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span>
                                Loading…
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
        <div id="pay-pagination" class="card-footer bg-white border-top d-none"></div>
    </div>
</div>

{{-- ============================================================
     MODAL 1: Pay Rent / Pay in Advance
     (triggered by the header "Pay Rent" button)
     ============================================================ --}}
<div class="modal fade" id="payModal" tabindex="-1" aria-labelledby="payModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg">
            <div class="modal-header text-white" style="background: var(--primary-dark);">
                <h5 class="modal-title" id="payModalLabel">Pay Rent</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <p id="payModalDetails" class="mb-3 text-muted"></p>

                <div class="mb-3">
                    <label for="payMethod" class="form-label fw-medium">Payment method</label>
                    <select id="payMethod" class="form-select">
                        <option value="mpesa">M-Pesa</option>
                        <option value="bank">Bank Transfer</option>
                    </select>
                </div>

                <div class="mb-2">
                    <label for="payRef" class="form-label fw-medium">Transaction reference</label>
                    <input id="payRef" type="text" class="form-control" placeholder="e.g. QGH7XYZ12" autocomplete="off">
                    <div class="form-text">Enter the M-Pesa or bank reference for this payment.</div>
                </div>

                <div id="payModalMsg" class="small mt-2" style="min-height:1.2em;"></div>
            </div>
            <div class="modal-footer border-top">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" id="payConfirm" class="btn btn-primary d-inline-flex align-items-center gap-2">
                    <span id="payConfirmSpinner" class="spinner-border spinner-border-sm d-none" role="status" aria-hidden="true"></span>
                    <span id="payConfirmLabel">Submit</span>
                </button>
            </div>
        </div>
    </div>
</div>

{{-- ============================================================
     MODAL 2: Submit Transaction Code
     (triggered by per-row "Submit Code" button on existing rows)
     ============================================================ --}}
<div class="modal fade" id="txModal" tabindex="-1" aria-labelledby="txModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg">
            <div class="modal-header text-white" style="background: var(--primary-dark);">
                <h5 class="modal-title" id="txModalLabel">Submit Transaction Code</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <input type="hidden" id="tx-payment-id">
                <p id="tx-details" class="text-muted mb-3"></p>
                <div id="tx-error"></div>

                <div class="mb-3">
                    <label for="tx-code" class="form-label fw-medium">Transaction reference</label>
                    <input type="text" class="form-control" id="tx-code" placeholder="e.g. QGH7XYZ12" autocomplete="off">
                </div>

                <div class="mb-2">
                    <label for="tx-method" class="form-label fw-medium">Payment method</label>
                    <select id="tx-method" class="form-select">
                        <option value="mpesa">M-Pesa</option>
                        <option value="bank">Bank Transfer</option>
                    </select>
                </div>

                <div id="tx-msg" class="small mt-2" style="min-height:1.2em;"></div>
            </div>
            <div class="modal-footer border-top">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" id="tx-submit" class="btn btn-primary d-inline-flex align-items-center gap-2">
                    <span id="txSpinner" class="spinner-border spinner-border-sm d-none" role="status" aria-hidden="true"></span>
                    <span id="txLabel">Submit</span>
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

  /* ── helpers ─────────────────────────────────────────────────── */

  function xsrf() {
    return decodeURIComponent((document.cookie.match(/XSRF-TOKEN=([^;]+)/) || [])[1] || '');
  }

  const csrfMeta = () => document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');

  function formatDate(value) {
    if (!value) return '—';
    const d = new Date(value);
    return isNaN(d.getTime()) ? value : d.toLocaleDateString(undefined, { year: 'numeric', month: 'short', day: 'numeric' });
  }

  function billingMonth(value) {
    if (!value) return '—';
    const d = new Date(value);
    return isNaN(d.getTime()) ? value : d.toLocaleDateString(undefined, { year: 'numeric', month: 'long' });
  }

  async function apiFetch(path, options = {}) {
    const method = (options.method || 'GET').toUpperCase();
    if (method !== 'GET') {
      await fetch('/sanctum/csrf-cookie', { credentials: 'include' });
    }
    const res = await fetch(path, {
      credentials: 'include',
      ...options,
      method,
      headers: {
        'Accept': 'application/json',
        'Content-Type': 'application/json',
        'X-CSRF-TOKEN': csrfMeta() || '',
        'X-XSRF-TOKEN': xsrf(),
        ...(options.headers || {}),
      },
    });
    const text = await res.text();
    const body = text ? JSON.parse(text) : null;
    if (!res.ok) throw { status: res.status, message: (body && body.message) || 'Request failed.' };
    return { status: res.status, body };
  }

  function statusBadge(status, transactionId) {
    const map = {
      completed : { label: 'Paid',                cls: 'success'   },
      pending   : { label: transactionId ? 'Awaiting Verification' : 'Pending', cls: transactionId ? 'info' : 'warning' },
      failed    : { label: 'Failed',              cls: 'danger'    },
      refunded  : { label: 'Refunded',            cls: 'secondary' },
    };
    const s = map[status] || { label: status, cls: 'secondary' };
    return `<span class="badge bg-${s.cls}-light text-${s.cls} border border-${s.cls} border-opacity-25">${s.label}</span>`;
  }

  /* ── state ───────────────────────────────────────────────────── */

  const payBody       = document.getElementById('pay-body');
  const payAlert      = document.getElementById('pay-alert');
  const payHint       = document.getElementById('payRentHint');
  const payPagination = document.getElementById('pay-pagination');

  /* ── load table ──────────────────────────────────────────────── */

  async function loadPayments(page = 1) {
    payBody.innerHTML = `
      <tr>
        <td colspan="5" class="text-center text-muted py-4">
          <span class="spinner-border spinner-border-sm me-2" role="status"></span>Loading…
        </td>
      </tr>`;
    try {
      const { body } = await apiFetch(`/api/tenant/payments?page=${page}`);
      const items = body.data || (Array.isArray(body) ? body : []);

      if (!items.length) {
        payBody.innerHTML = `
          <tr>
            <td colspan="5" class="text-center text-muted py-5">
              <i class="fas fa-receipt fa-2x d-block mb-3 opacity-50"></i>
              You have no payment records yet.
            </td>
          </tr>`;
        return;
      }

      // Overdue banner — first overdue row.
      const overdue = items.find(p =>
        ['pending'].includes(p.status) && !p.transaction_id && new Date(p.due_date) < new Date()
      );
      payAlert.innerHTML = overdue
        ? `<div class="alert alert-danger d-flex align-items-center gap-2 shadow-sm border-0 mb-3" role="alert">
             <i class="fas fa-triangle-exclamation flex-shrink-0"></i>
             <div>You have an overdue payment of <strong>KES ${Number(overdue.amount).toLocaleString()}</strong>
             (due ${formatDate(overdue.due_date)}). Tap <strong>Pay Rent</strong> to settle it.</div>
           </div>`
        : '';

      payBody.innerHTML = items.map(p => {
        const due       = new Date(p.due_date);
        const isFuture  = due > new Date();
        const isAdvance = p.status !== 'completed' && isFuture;
        const advBadge  = isAdvance
          ? `<span class="badge ms-1 text-white" style="background:var(--accent-purple)">Advance</span>`
          : '';

        return `
          <tr>
            <td class="fw-medium">${billingMonth(p.due_date)}${advBadge}</td>
            <td>KES ${Number(p.amount).toLocaleString('en-KE', { minimumFractionDigits: 2 })}</td>
            <td>${formatDate(p.due_date)}</td>
            <td class="text-capitalize">${p.payment_method || '—'}</td>
            <td>${statusBadge(p.status, p.transaction_id)}</td>
          </tr>`;
      }).join('');

      // Pagination (if paginated response).
      if (body.last_page && body.last_page > 1) {
        payPagination.classList.remove('d-none');
        let pages = '';
        for (let i = 1; i <= body.last_page; i++) {
          pages += `<li class="page-item ${i === body.current_page ? 'active' : ''}">
                      <button class="page-link" data-page="${i}">${i}</button>
                    </li>`;
        }
        payPagination.innerHTML = `<nav><ul class="pagination pagination-sm mb-0">${pages}</ul></nav>`;
        payPagination.querySelectorAll('[data-page]').forEach(btn => {
          btn.addEventListener('click', () => loadPayments(+btn.dataset.page));
        });
      } else {
        payPagination.classList.add('d-none');
      }

    } catch (e) {
      payBody.innerHTML = `
        <tr>
          <td colspan="5">
            <div class="alert alert-danger m-3">${e.message || 'Failed to load payments.'}</div>
          </td>
        </tr>`;
    }
  }

  /* ── PAY RENT button (header) — checks status, may offer advance ── */

  const payRentBtn   = document.getElementById('payRentBtn');
  const payModalEl   = document.getElementById('payModal');
  const payModal     = new bootstrap.Modal(payModalEl);
  const payTitleEl   = document.getElementById('payModalLabel');
  const payDetailsEl = document.getElementById('payModalDetails');
  const payMsgEl     = document.getElementById('payModalMsg');
  const payRefEl     = document.getElementById('payRef');
  const payMethodEl  = document.getElementById('payMethod');
  const payConfirm   = document.getElementById('payConfirm');
  const paySpinner   = document.getElementById('payConfirmSpinner');
  const payLbl       = document.getElementById('payConfirmLabel');

  let activePayId = null;

  function setPayLoading(on) {
    paySpinner.classList.toggle('d-none', !on);
    payConfirm.disabled = on;
    payLbl.textContent = on ? 'Submitting…' : 'Submit';
  }

  payRentBtn.addEventListener('click', async () => {
    payHint.textContent = 'Checking your rent status…';
    payRentBtn.disabled = true;
    try {
      const { body } = await apiFetch('{{ route('tenant.pay-rent') }}');

      payHint.textContent = '';
      activePayId = body.payment.id;
      payMsgEl.textContent = '';
      payMsgEl.className = 'small mt-2';
      payRefEl.value = '';

      if (body.advance) {
        payTitleEl.textContent  = 'Pay in Advance';
        payDetailsEl.innerHTML  =
          `This month's rent is already paid.<br>Would you like to pay next month ` +
          `(<strong>KES ${Number(body.payment.amount).toLocaleString()}</strong>, ` +
          `due ${body.payment.due_date}) in advance?`;
      } else {
        payTitleEl.textContent  = 'Pay Rent';
        payDetailsEl.innerHTML  =
          `Rent for Unit <strong>${body.payment.unit}</strong>: ` +
          `<strong>KES ${Number(body.payment.amount).toLocaleString()}</strong>, ` +
          `due ${body.payment.due_date}.`;
      }
      payModal.show();
    } catch (e) {
      payHint.textContent = e.message || 'Could not load rent status.';
    } finally {
      payRentBtn.disabled = false;
    }
  });

  payConfirm.addEventListener('click', async () => {
    const ref    = payRefEl.value.trim();
    const method = payMethodEl.value;
    if (!ref) {
      payMsgEl.textContent = 'Please enter a transaction reference.';
      payMsgEl.className   = 'small mt-2 text-danger';
      return;
    }
    setPayLoading(true);
    payMsgEl.textContent = '';
    try {
      const { status, body } = await apiFetch(`/api/tenant/payments/${activePayId}/submit`, {
        method: 'POST',
        body: JSON.stringify({ transaction_id: ref, payment_method: method }),
      });

      if (status === 202) {
        payMsgEl.textContent = body?.message || 'Saved offline. Will sync when you reconnect.';
        payMsgEl.className   = 'small mt-2 text-info';
        setTimeout(() => payModal.hide(), 1800);
      } else {
        payMsgEl.textContent = body?.message || 'Payment submitted for verification.';
        payMsgEl.className   = 'small mt-2 text-success';
        setTimeout(() => { payModal.hide(); loadPayments(); }, 1400);
      }
    } catch (e) {
      payMsgEl.textContent = e.message || 'Submission failed. Please try again.';
      payMsgEl.className   = 'small mt-2 text-danger';
    } finally {
      setPayLoading(false);
    }
  });

  /* ── SUBMIT CODE button (per-row) — existing pending rows ─────── */

  const txModalEl  = document.getElementById('txModal');
  const txModal    = new bootstrap.Modal(txModalEl);
  const txPayIdEl  = document.getElementById('tx-payment-id');
  const txDetailsEl= document.getElementById('tx-details');
  const txCodeEl   = document.getElementById('tx-code');
  const txMethodEl = document.getElementById('tx-method');
  const txSubmitBtn= document.getElementById('tx-submit');
  const txSpinner  = document.getElementById('txSpinner');
  const txLbl      = document.getElementById('txLabel');
  const txMsgEl    = document.getElementById('tx-msg');
  const txErrEl    = document.getElementById('tx-error');

  function setTxLoading(on) {
    txSpinner.classList.toggle('d-none', !on);
    txSubmitBtn.disabled = on;
    txLbl.textContent    = on ? 'Submitting…' : 'Submit';
  }

  payBody.addEventListener('click', (e) => {
    const btn = e.target.closest('.submit-code-btn');
    if (!btn) return;
    txPayIdEl.value      = btn.dataset.id;
    txCodeEl.value       = '';
    txMsgEl.textContent  = '';
    txErrEl.innerHTML    = '';
    txDetailsEl.textContent =
      `KES ${Number(btn.dataset.amount).toLocaleString()} due ${btn.dataset.due}`;
    txModal.show();
  });

  txSubmitBtn.addEventListener('click', async () => {
    const ref    = txCodeEl.value.trim();
    const method = txMethodEl.value;
    const id     = txPayIdEl.value;
    if (!ref) {
      txMsgEl.textContent = 'Please enter a transaction reference.';
      txMsgEl.className   = 'small mt-2 text-danger';
      return;
    }
    setTxLoading(true);
    txMsgEl.textContent = '';
    txErrEl.innerHTML   = '';
    try {
      const { status, body } = await apiFetch(`/api/tenant/payments/${id}/transaction-code`, {
        method: 'POST',
        body: JSON.stringify({ transaction_id: ref, payment_method: method }),
      });

      if (status === 202) {
        txMsgEl.textContent = body?.message || 'Saved offline. Will sync when you reconnect.';
        txMsgEl.className   = 'small mt-2 text-info';
        setTimeout(() => txModal.hide(), 1800);
      } else {
        txMsgEl.textContent = body?.message || 'Submitted for verification.';
        txMsgEl.className   = 'small mt-2 text-success';
        setTimeout(() => { txModal.hide(); loadPayments(); }, 1400);
      }
    } catch (e) {
      txErrEl.innerHTML = `<div class="alert alert-danger py-2">${e.message || 'Submission failed.'}</div>`;
    } finally {
      setTxLoading(false);
    }
  });

  /* ── init ─────────────────────────────────────────────────────── */
  loadPayments();

})();
</script>
@endpush