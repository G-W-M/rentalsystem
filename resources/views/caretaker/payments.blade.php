@extends('layouts.caretaker')

@section('title', 'Verify Payments')

@section('content')
<div class="p-4">
    <h1 class="text-2xl font-bold text-primary mb-4">Verify Payments</h1>

    <div class="alert alert-info text-sm">
        Enter the payment ID and the transaction code the tenant gave you. The amount
        is fixed by the system and cannot be changed here. If the code doesn't match
        what the tenant submitted, verification will be blocked.
    </div>

    <div class="card shadow-sm rounded-lg border-0 mb-4" style="max-width: 480px;">
        <div class="card-body">
            <div class="mb-3">
                <label class="form-label">Payment ID</label>
                <input type="number" class="form-control" id="payment-id" placeholder="e.g. 12">
            </div>
            <div class="mb-3">
                <label class="form-label">Transaction Code (from tenant)</label>
                <input type="text" class="form-control" id="tx-code" placeholder="e.g. MPESA123456">
            </div>
            <button class="btn btn-primary" id="verify-btn">Verify Payment</button>
            <div class="mt-3" id="verify-result"></div>
        </div>
    </div>

    <h2 class="text-xl font-bold text-primary mb-3">Payments I've Verified</h2>
    <div id="history-error"></div>
    <div class="card shadow-sm rounded-lg border-0">
        <div class="card-body p-0">
            <table class="table mb-0 align-middle">
                <thead><tr><th>Tenant</th><th>Unit</th><th>Amount</th><th>Verified On</th><th>Receipt</th></tr></thead>
                <tbody id="history-body"><tr><td colspan="5" class="text-muted">Loading...</td></tr></tbody>
            </table>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    function xsrf() { return decodeURIComponent((document.cookie.match(/XSRF-TOKEN=([^;]+)/) || [])[1] || ''); }

    function fmt(iso) {
        if (!iso) return '-';
        const d = new Date(iso);
        if (isNaN(d.getTime())) return iso;
        return d.toLocaleDateString(undefined, { year: 'numeric', month: 'short', day: 'numeric' });
    }
    function money(v) { return Number(v || 0).toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 }); }

    document.getElementById('verify-btn').addEventListener('click', async () => {
        const id = document.getElementById('payment-id').value;
        const code = document.getElementById('tx-code').value;
        const out = document.getElementById('verify-result');
        out.innerHTML = '';

        if (!id || !code) {
            out.innerHTML = '<div class="alert alert-warning">Enter both the payment ID and the transaction code.</div>';
            return;
        }

        try {
            await fetch('/sanctum/csrf-cookie', { credentials: 'include' });

            const res = await fetch('/api/caretaker/payments/' + id + '/verify', {
                method: 'POST',
                credentials: 'include',
                headers: {
                    'Accept': 'application/json',
                    'Content-Type': 'application/json',
                    'X-XSRF-TOKEN': xsrf(),
                },
                body: JSON.stringify({ transaction_code: code }),
            });

            const body = await res.json();

            if (!res.ok) {
                out.innerHTML = '<div class="alert alert-danger">' + (body.message || 'Verification failed.') + '</div>';
                return;
            }

            const receipt = body.payment && body.payment.receipt_url ? body.payment.receipt_url : '-';
            out.innerHTML = '<div class="alert alert-success">' + body.message + ' Receipt: ' + receipt + '</div>';
            document.getElementById('payment-id').value = '';
            document.getElementById('tx-code').value = '';
            loadHistory();
        } catch (e) {
            out.innerHTML = '<div class="alert alert-danger">Network error. Try again.</div>';
        }
    });

    async function loadHistory() {
        try {
            const res = await fetch('/api/caretaker/payments/verified', {
                credentials: 'include',
                headers: { Accept: 'application/json', 'X-XSRF-TOKEN': xsrf() },
            });
            const page = await res.json();
            if (!res.ok) throw new Error(page.message || 'Failed to load.');

            const items = page.data || [];
            const body = document.getElementById('history-body');
            body.innerHTML = items.length
                ? items.map((p) => {
                    const tenantName = p.tenant && p.tenant.user ? p.tenant.user.full_name : '-';
                    const unitNumber = p.unit ? p.unit.unit_number : '-';
                    return '<tr><td>' + tenantName + '</td><td>' + unitNumber + '</td><td>' + money(p.amount) +
                        '</td><td>' + fmt(p.verified_at) + '</td><td><span class="badge bg-success">' + (p.receipt_url || '-') + '</span></td></tr>';
                }).join('')
                : '<tr><td colspan="5" class="text-muted">You have not verified any payments yet</td></tr>';
        } catch (e) {
            document.getElementById('history-error').innerHTML = '<div class="alert alert-danger">' + e.message + '</div>';
        }
    }

    loadHistory();
</script>
@endpush