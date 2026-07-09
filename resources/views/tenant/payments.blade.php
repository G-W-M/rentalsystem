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

    {{-- Global alerts --}}
    <div id="pay-alert"></div>
    <p id="payRentHint" class="text-muted small mb-3" style="min-height:1.2em;"></p>

    {{-- ── SECTION 1: Current / Overdue ── --}}
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-header bg-white border-bottom d-flex align-items-center justify-content-between">
            <h2 class="h6 fw-semibold mb-0 text-primary-dark">
                <i class="fas fa-file-invoice-dollar me-2 text-warning"></i> Current &amp; Overdue
            </h2>
            <span id="current-count" class="badge bg-warning text-dark">0</span>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Billing Month</th>
                            <th>Amount (KES)</th>
                            <th>Due Date</th>
                            <th>Method</th>
                            <th>Status</th>
                            <th class="text-end">Action</th>
                        </tr>
                    </thead>
                    <tbody id="current-body">
                        <tr><td colspan="6" class="text-center text-muted py-4">
                            <span class="spinner-border spinner-border-sm me-2"></span>Loading…
                        </td></tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    {{-- ── SECTION 2: Advance Payments ── --}}
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-header bg-white border-bottom d-flex align-items-center justify-content-between">
            <h2 class="h6 fw-semibold mb-0 text-primary-dark">
                <i class="fas fa-calendar-plus me-2 text-primary"></i> Advance Payments
            </h2>
            <span id="advance-count" class="badge bg-primary">0</span>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Billing Month</th>
                            <th>Amount (KES)</th>
                            <th>Due Date</th>
                            <th>Method</th>
                            <th>Status</th>
                            <th class="text-end">Action</th>
                        </tr>
                    </thead>
                    <tbody id="advance-body">
                        <tr><td colspan="6" class="text-center text-muted py-4">
                            <span class="spinner-border spinner-border-sm me-2"></span>Loading…
                        </td></tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    {{-- ── SECTION 3: Paid Payments ── --}}
    <div class="card border-0 shadow-sm">
        <div class="card-header bg-white border-bottom d-flex align-items-center justify-content-between">
            <h2 class="h6 fw-semibold mb-0 text-primary-dark">
                <i class="fas fa-circle-check me-2 text-success"></i> Paid
            </h2>
            <span id="paid-count" class="badge bg-success">0</span>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Billing Month</th>
                            <th>Amount (KES)</th>
                            <th>Paid On</th>
                            <th>Method</th>
                            <th class="text-end">Receipt</th>
                        </tr>
                    </thead>
                    <tbody id="paid-body">
                        <tr><td colspan="5" class="text-center text-muted py-4">
                            <span class="spinner-border spinner-border-sm me-2"></span>Loading…
                        </td></tr>
                    </tbody>
                </table>
            </div>
        </div>
        <div id="pay-pagination" class="card-footer bg-white border-top d-none"></div>
    </div>
</div>

{{-- ── NEW MODAL: Pay Rent / Pick Month ── --}}
<div class="modal fade" id="payModal" tabindex="-1" aria-labelledby="payModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg">
            <div class="modal-header text-white" style="background: var(--primary-dark);">
                <h5 class="modal-title" id="payModalLabel">Pay Rent</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">

                {{-- Unit info line --}}
                <p id="payModalUnit" class="text-muted small mb-3"></p>

                {{-- Month picker (dropdown) --}}
                <div class="mb-3" id="payMonthWrap">
                    <label for="payMonthSelect" class="form-label fw-medium">Select month to pay</label>
                    <select id="payMonthSelect" class="form-select">
                        <option value="">— choose a month —</option>
                    </select>
                    <div id="payMonthInfo" class="form-text mt-1"></div>
                </div>

                {{-- Amount display --}}
                <div class="bg-light rounded p-3 mb-3 d-none" id="payAmountBox">
                    <div class="d-flex justify-content-between align-items-center">
                        <span class="text-muted small">Amount</span>
                        <span class="fw-bold text-primary-dark fs-5" id="payAmountDisplay">—</span>
                    </div>
                    <div class="d-flex justify-content-between align-items-center mt-1">
                        <span class="text-muted small">Due date</span>
                        <span class="small" id="payDueDateDisplay">—</span>
                    </div>
                </div>

                <div class="mb-3" id="payMethodWrap">
                    <label for="payMethod" class="form-label fw-medium">Payment method</label>
                    <select id="payMethod" class="form-select">
                        <option value="mpesa">M-Pesa</option>
                        <option value="bank">Bank Transfer</option>
                    </select>
                </div>

                <div class="mb-2" id="payRefWrap">
                    <label for="payRef" class="form-label fw-medium">Transaction reference</label>
                    <input id="payRef" type="text" class="form-control" placeholder="e.g. QGH7XYZ12" autocomplete="off">
                    <div class="form-text">Enter the M-Pesa or bank reference for this payment.</div>
                </div>

                <div id="payModalMsg" class="small mt-2" style="min-height:1.2em;"></div>
            </div>
            <div class="modal-footer border-top">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" id="payConfirm" class="btn btn-primary d-inline-flex align-items-center gap-2" disabled>
                    <span id="payConfirmSpinner" class="spinner-border spinner-border-sm d-none" role="status"></span>
                    <span id="payConfirmLabel">Submit</span>
                </button>
            </div>
        </div>
    </div>
</div>

{{-- ── MODAL: Submit Transaction Code (per-row) ── --}}
<div class="modal fade" id="txModal" tabindex="-1" aria-labelledby="txModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg">
            <div class="modal-header text-white" style="background: var(--primary-dark);">
                <h5 class="modal-title" id="txModalLabel">Submit Transaction Code</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
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
                    <span id="txSpinner" class="spinner-border spinner-border-sm d-none" role="status"></span>
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

  /* ── helpers ─────────────────────────────────────────────── */
  function xsrf() {
    return decodeURIComponent((document.cookie.match(/XSRF-TOKEN=([^;]+)/) || [])[1] || '');
  }
  const csrfMeta = () => document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');

  function formatDate(v) {
    if (!v) return '—';
    const d = new Date(v);
    return isNaN(d.getTime()) ? v : d.toLocaleDateString(undefined, { year:'numeric', month:'short', day:'numeric' });
  }
  function billingMonth(v) {
    if (!v) return '—';
    const d = new Date(v);
    return isNaN(d.getTime()) ? v : d.toLocaleDateString(undefined, { year:'numeric', month:'long' });
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
      completed : { label: 'Paid',                   cls: 'success'   },
      pending   : { label: transactionId ? 'Awaiting Verification' : 'Pending',
                    cls:   transactionId ? 'info'    : 'warning'       },
      failed    : { label: 'Failed',                 cls: 'danger'    },
      refunded  : { label: 'Refunded',               cls: 'secondary' },
    };
    const s = map[status] || { label: status, cls: 'secondary' };
    return `<span class="badge bg-${s.cls}-light text-${s.cls} border border-${s.cls} border-opacity-25">${s.label}</span>`;
  }

  function submitCodeBtn(p) {
    if (p.status === 'completed') return '—';
    if (p.transaction_id) return `<span class="text-muted small">Awaiting verification</span>`;
    return `<button class="btn btn-sm btn-outline-primary submit-code-btn"
              data-id="${p.id}" data-amount="${p.amount}" data-due="${formatDate(p.due_date)}">
              <i class="fas fa-paper-plane me-1"></i>Submit Code
            </button>`;
  }

  /* ── classify payments into 3 buckets ───────────────────── */
  function classify(items) {
    const now     = new Date();
    const thisYear  = now.getFullYear();
    const thisMonth = now.getMonth(); // 0-indexed

    const current = [], advance = [], paid = [];

    items.forEach(p => {
      if (p.status === 'completed') {
        paid.push(p);
        return;
      }
      const due = new Date(p.due_date);
      const isFutureMonth = due.getFullYear() > thisYear ||
        (due.getFullYear() === thisYear && due.getMonth() > thisMonth);

      if (isFutureMonth) {
        advance.push(p);
      } else {
        current.push(p);
      }
    });

    return { current, advance, paid };
  }

  /* ── render helpers ──────────────────────────────────────── */
  function renderCurrent(items) {
    const tbody = document.getElementById('current-body');
    const badge = document.getElementById('current-count');
    badge.textContent = items.length;

    if (!items.length) {
      tbody.innerHTML = `<tr><td colspan="6" class="text-center text-muted py-4">
        <i class="fas fa-circle-check text-success me-2"></i>No outstanding payments.
      </td></tr>`;
      return;
    }

    tbody.innerHTML = items.map(p => `
      <tr class="${p.status === 'pending' && !p.transaction_id && new Date(p.due_date) < new Date() ? 'table-danger' : ''}">
        <td class="fw-medium">${billingMonth(p.due_date)}</td>
        <td>${money(p.amount)}</td>
        <td>${formatDate(p.due_date)}</td>
        <td class="text-capitalize">${p.payment_method || '—'}</td>
        <td>${statusBadge(p.status, p.transaction_id)}</td>
        <td class="text-end">${submitCodeBtn(p)}</td>
      </tr>`).join('');
  }

  function renderAdvance(items) {
    const tbody = document.getElementById('advance-body');
    const badge = document.getElementById('advance-count');
    badge.textContent = items.length;

    if (!items.length) {
      tbody.innerHTML = `<tr><td colspan="6" class="text-center text-muted py-4">
        <i class="fas fa-calendar-plus me-2 opacity-50"></i>No advance payments yet.
        Pay next month early by tapping <strong>Pay Rent</strong> when this month is settled.
      </td></tr>`;
      return;
    }

    tbody.innerHTML = items.map(p => `
      <tr>
        <td class="fw-medium">
          ${billingMonth(p.due_date)}
          <span class="badge ms-1 text-white" style="background:var(--bs-primary)">Advance</span>
        </td>
        <td>${money(p.amount)}</td>
        <td>${formatDate(p.due_date)}</td>
        <td class="text-capitalize">${p.payment_method || '—'}</td>
        <td>${statusBadge(p.status, p.transaction_id)}</td>
        <td class="text-end">${submitCodeBtn(p)}</td>
      </tr>`).join('');
  }

  function renderPaid(items) {
    const tbody = document.getElementById('paid-body');
    const badge = document.getElementById('paid-count');
    badge.textContent = items.length;

    if (!items.length) {
      tbody.innerHTML = `<tr><td colspan="5" class="text-center text-muted py-4">
        <i class="fas fa-receipt fa-2x d-block mb-2 opacity-50"></i>No completed payments yet.
      </td></tr>`;
      return;
    }

    tbody.innerHTML = items.map(p => `
      <tr>
        <td class="fw-medium">${billingMonth(p.due_date)}</td>
        <td>${money(p.amount)}</td>
        <td>${formatDate(p.payment_date || p.verified_at)}</td>
        <td class="text-capitalize">${p.payment_method || '—'}</td>
        <td class="text-end">
          ${p.receipt_url
            ? `<a href="/tenant/payments/${p.id}/receipt"
                  class="btn btn-sm btn-outline-success d-inline-flex align-items-center gap-1">
                  <i class="fas fa-download"></i> Receipt
               </a>`
            : '<span class="text-muted small">—</span>'}
      </td></tr>`).join('');
  }

  /* ── load all payments, split into sections ──────────────── */
  async function loadPayments(page = 1) {
    ['current-body','advance-body','paid-body'].forEach(id => {
      document.getElementById(id).innerHTML = `<tr><td colspan="6" class="text-center text-muted py-3">
        <span class="spinner-border spinner-border-sm me-2"></span>Loading…</td></tr>`;
    });

    try {
      const { body } = await apiFetch(`/api/tenant/payments?page=${page}`);
      const items = body.data || (Array.isArray(body) ? body : []);

      // Overdue alert banner
      const overdue = items.find(p =>
        p.status === 'pending' && !p.transaction_id && new Date(p.due_date) < new Date()
      );
      document.getElementById('pay-alert').innerHTML = overdue
        ? `<div class="alert alert-danger d-flex align-items-center gap-2 shadow-sm border-0 mb-3">
             <i class="fas fa-triangle-exclamation flex-shrink-0"></i>
             <div>Overdue payment of <strong>${money(overdue.amount)}</strong>
             (due ${formatDate(overdue.due_date)}). Tap <strong>Pay Rent</strong> to settle it.</div>
           </div>` : '';

      const { current, advance, paid } = classify(items);
      renderCurrent(current);
      renderAdvance(advance);
      renderPaid(paid);

      // Pagination
      const pagination = document.getElementById('pay-pagination');
      if (body.last_page && body.last_page > 1) {
        pagination.classList.remove('d-none');
        let pages = '';
        for (let i = 1; i <= body.last_page; i++) {
          pages += `<li class="page-item ${i === body.current_page ? 'active' : ''}">
                      <button class="page-link" data-page="${i}">${i}</button></li>`;
        }
        pagination.innerHTML = `<nav><ul class="pagination pagination-sm mb-0">${pages}</ul></nav>`;
        pagination.querySelectorAll('[data-page]').forEach(btn =>
          btn.addEventListener('click', () => loadPayments(+btn.dataset.page))
        );
      } else {
        pagination.classList.add('d-none');
      }

    } catch (e) {
      ['current-body','advance-body'].forEach(id => {
        document.getElementById(id).innerHTML =
          `<tr><td colspan="6"><div class="alert alert-danger m-3">${e.message}</div></td></tr>`;
      });
    }
  }

  /* ── PAY RENT button — month picker flow ─────────────────── */
  const payRentBtn    = document.getElementById('payRentBtn');
  const payModalEl    = document.getElementById('payModal');
  const payModal      = new bootstrap.Modal(payModalEl);
  const payModalUnit  = document.getElementById('payModalUnit');
  const payMonthSel   = document.getElementById('payMonthSelect');
  const payMonthInfo  = document.getElementById('payMonthInfo');
  const payAmountBox  = document.getElementById('payAmountBox');
  const payAmountDisp = document.getElementById('payAmountDisplay');
  const payDueDisp    = document.getElementById('payDueDateDisplay');
  const payMsgEl      = document.getElementById('payModalMsg');
  const payRefEl      = document.getElementById('payRef');
  const payMethodEl   = document.getElementById('payMethod');
  const payConfirm    = document.getElementById('payConfirm');
  const paySpinner    = document.getElementById('payConfirmSpinner');
  const payLbl        = document.getElementById('payConfirmLabel');
  const payHint       = document.getElementById('payRentHint');

  // Stored list of payable months from the API
  let payableMonths = [];

  function setPayLoading(on) {
    paySpinner.classList.toggle('d-none', !on);
    payConfirm.disabled = on;
    payLbl.textContent = on ? 'Submitting…' : 'Submit';
  }

  function resetPayModal() {
    payMonthSel.innerHTML = '<option value="">— choose a month —</option>';
    payAmountBox.classList.add('d-none');
    payAmountDisp.textContent = '—';
    payDueDisp.textContent    = '—';
    payMonthInfo.textContent  = '';
    payMsgEl.textContent      = '';
    payMsgEl.className        = 'small mt-2';
    payRefEl.value            = '';
    payConfirm.disabled       = true;
    payableMonths             = [];
  }

  // When tenant picks a month from the dropdown
  payMonthSel.addEventListener('change', () => {
    const idx = payMonthSel.value;
    if (idx === '') {
      payAmountBox.classList.add('d-none');
      payConfirm.disabled = true;
      payMonthInfo.textContent = '';
      return;
    }

    const month = payableMonths[parseInt(idx)];
    payAmountDisp.textContent = money(month.amount);
    payDueDisp.textContent    = formatDate(month.due_date);
    payAmountBox.classList.remove('d-none');
    payConfirm.disabled = false;

    // Warn if month already has a pending/submitted reference
    if (month.transaction_id) {
      payMonthInfo.innerHTML = `<span class="text-warning">
        <i class="fas fa-triangle-exclamation me-1"></i>
        A reference was already submitted for this month and is awaiting verification.
        Submitting again will overwrite it.
      </span>`;
    } else {
      payMonthInfo.textContent = '';
    }
  });

  // Open modal — fetch payable months
  payRentBtn.addEventListener('click', async () => {
    payHint.textContent  = 'Checking your rent status…';
    payRentBtn.disabled  = true;
    resetPayModal();

    try {
      const { body } = await apiFetch('/api/tenant/pay-rent');

      payHint.textContent = '';
      payableMonths       = body.payable_months || [];

      payModalUnit.textContent = `Unit ${body.unit}`;

      if (!payableMonths.length) {
        payHint.textContent = body.message || 'Your rent is fully paid up to the end of your lease. 🎉';
        return;
      }

      // Populate dropdown
      payableMonths.forEach((m, i) => {
        const opt = document.createElement('option');
        opt.value       = i;
        opt.textContent = m.label + (m.transaction_id ? ' ⏳ submitted' : '');
        payMonthSel.appendChild(opt);
      });

      // If only one option auto-select it
      if (payableMonths.length === 1) {
        payMonthSel.value = '0';
        payMonthSel.dispatchEvent(new Event('change'));
      }

      payModal.show();
    } catch (e) {
      payHint.textContent = e.message || 'Could not load rent status.';
    } finally {
      payRentBtn.disabled = false;
    }
  });

  // Submit the selected month's payment
  payConfirm.addEventListener('click', async () => {
    const idx = payMonthSel.value;
    if (idx === '') return;

    const month  = payableMonths[parseInt(idx)];
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
      let status, body;

      if (month.payment_id) {
        // Row already exists — use existing submit endpoint
        ({ status, body } = await apiFetch(`/api/tenant/payments/${month.payment_id}/submit`, {
          method: 'POST',
          body: JSON.stringify({ transaction_id: ref, payment_method: method }),
        }));
      } else {
        // No row yet — create and submit in one call
        ({ status, body } = await apiFetch('/api/tenant/payments/init-and-submit', {
          method: 'POST',
          body: JSON.stringify({
            due_date:       month.due_date,
            transaction_id: ref,
            payment_method: method,
          }),
        }));
      }

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
      payMsgEl.textContent = e.message || 'Submission failed.';
      payMsgEl.className   = 'small mt-2 text-danger';
    } finally {
      setPayLoading(false);
    }
  });

  // Clear on close
  payModalEl.addEventListener('hidden.bs.modal', resetPayModal);

  /* ── SUBMIT CODE button (per-row) ────────────────────────── */
  const txModalEl   = document.getElementById('txModal');
  const txModal     = new bootstrap.Modal(txModalEl);
  const txPayIdEl   = document.getElementById('tx-payment-id');
  const txDetailsEl = document.getElementById('tx-details');
  const txCodeEl    = document.getElementById('tx-code');
  const txMethodEl  = document.getElementById('tx-method');
  const txSubmitBtn = document.getElementById('tx-submit');
  const txSpinner   = document.getElementById('txSpinner');
  const txLbl       = document.getElementById('txLabel');
  const txMsgEl     = document.getElementById('tx-msg');
  const txErrEl     = document.getElementById('tx-error');

  function setTxLoading(on) {
    txSpinner.classList.toggle('d-none', !on);
    txSubmitBtn.disabled = on;
    txLbl.textContent = on ? 'Submitting…' : 'Submit';
  }

  // Listen on all three tbodies for submit-code clicks
  ['current-body', 'advance-body'].forEach(id => {
    document.getElementById(id).addEventListener('click', e => {
      const btn = e.target.closest('.submit-code-btn');
      if (!btn) return;
      txPayIdEl.value     = btn.dataset.id;
      txCodeEl.value      = '';
      txMsgEl.textContent = '';
      txErrEl.innerHTML   = '';
      txDetailsEl.textContent = `${money(btn.dataset.amount)} due ${btn.dataset.due}`;
      txModal.show();
    });
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

  /* ── init ────────────────────────────────────────────────── */
  loadPayments();
})();
</script>
@endpush