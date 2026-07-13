@extends('layouts.tenant')

@section('title', 'Dashboard')

@section('content')
    <div class="p-4">
        <h1 class="text-2xl font-bold text-primary mb-4">My Dashboard</h1>
        <div id="dash-error"></div>

        <div class="row g-3">
            <div class="col-12 col-lg-6">
                <div class="card shadow-sm rounded-lg border-0 h-100">
                    <div class="card-header bg-white fw-semibold">My Unit</div>
                    <div class="card-body" id="unit-block">
                        <div class="text-muted">Loading...</div>
                    </div>
                </div>
            </div>
            <div class="col-12 col-lg-6">
                <div class="card shadow-sm rounded-lg border-0 h-100">
                    <div class="card-header bg-white fw-semibold">Next Payment</div>
                    <div class="card-body" id="payment-block">
                        <div class="text-muted">Loading...</div>
                    </div>
                </div>
            </div>
        </div>

        <div class="card shadow-sm rounded-lg border-0 mt-3">
            <div class="card-header bg-white fw-semibold">Open Maintenance</div>
            <div class="card-body">
                <span class="text-3xl font-bold text-warning" data-kpi="open_maintenance">--</span>
                <span class="text-gray-600">open request(s)</span>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        function xsrf() {
            return decodeURIComponent((document.cookie.match(/XSRF-TOKEN=([^;]+)/) || [])[1] || '');
        }

        function money(v) {
            return Number(v || 0).toLocaleString(undefined, {
                minimumFractionDigits: 2,
                maximumFractionDigits: 2
            });
        }

<<<<<<< HEAD
=======
        /**
         * Maps a raw payment status to a readable label + the right badge
         * colour. Previously the badge was hardcoded to bg-warning and printed
         * the raw enum, so an already-submitted payment still looked like an
         * unpaid one — the exact inconsistency between this card and the
         * Payments page.
         */
>>>>>>> 076421e84a66168988790ae31e4f00e41822a69e
        function statusBadge(status) {
            const map = {
                pending: {
                    label: 'Pending',
                    css: 'bg-warning text-dark'
                },
                awaiting_verification: {
                    label: 'Awaiting Verification',
                    css: 'bg-info text-dark'
                },
                completed: {
                    label: 'Paid',
                    css: 'bg-success'
                },
                rejected: {
                    label: 'Rejected',
                    css: 'bg-danger'
                },
            };
<<<<<<< HEAD
=======

>>>>>>> 076421e84a66168988790ae31e4f00e41822a69e
            const s = map[status] || {
                label: status,
                css: 'bg-secondary'
            };
<<<<<<< HEAD
=======

>>>>>>> 076421e84a66168988790ae31e4f00e41822a69e
            return '<span class="badge ' + s.css + '">' + s.label + '</span>';
        }

        async function load() {
            try {
                const res = await fetch('/api/tenant/dashboard', {
                    credentials: 'include',
                    headers: {
                        'Accept': 'application/json',
                        'X-XSRF-TOKEN': xsrf()
                    },
                });
                const data = await res.json();
                if (!res.ok) throw new Error(data.message || 'Failed to load dashboard.');

                const unit = document.getElementById('unit-block');
                if (data.has_unit && data.unit) {
                    unit.innerHTML =
                        '<div class="h5 mb-1">' + data.unit.property + ' — Unit ' + data.unit.unit_number + '</div>' +
                        '<div class="text-gray-600">Rent: KES ' + money(data.unit.rent) + '</div>' +
                        '<div class="text-gray-600">Since: ' + (data.unit.since || '-') + '</div>';
                } else {
                    unit.innerHTML = '<div class="text-muted">You have no active unit yet.</div>';
                }

                const pay = document.getElementById('payment-block');
                if (data.pending_payment) {
                    const p = data.pending_payment;
<<<<<<< HEAD
=======

                    // When the payment is already submitted, the card is no longer a
                    // "you owe this" prompt — say so explicitly rather than showing a
                    // due date that implies action is still needed.
>>>>>>> 076421e84a66168988790ae31e4f00e41822a69e
                    const isSubmitted = p.status === 'awaiting_verification';
                    const note = isSubmitted ?
                        '<div class="text-gray-600 small mt-1">Submitted — awaiting caretaker verification.</div>' :
                        '';
<<<<<<< HEAD
                    pay.innerHTML =
                        '<div class="h4 text-primary">' + money(p.amount) + '</div>' +
                        '<div class="text-gray-600">Due: ' + (p.due_date || '-') + '</div>' +
                        statusBadge(p.status) + note;
=======

                    pay.innerHTML =
                        '<div class="h4 text-primary">' + money(p.amount) + '</div>' +
                        '<div class="text-gray-600">Due: ' + (p.due_date || '-') + '</div>' +
                        statusBadge(p.status) +
                        note;
>>>>>>> 076421e84a66168988790ae31e4f00e41822a69e
                } else {
                    pay.innerHTML = '<div class="text-muted">No pending payment. You are all caught up. ✓</div>';
                }

                document.querySelector('[data-kpi="open_maintenance"]').textContent = data.open_maintenance ?? 0;
            } catch (e) {
                document.getElementById('dash-error').innerHTML = '<div class="alert alert-danger">' + e.message +
                    '</div>';
            }
        }
        load();
    </script>
@endpush