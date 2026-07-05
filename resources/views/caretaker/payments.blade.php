@extends('layouts.caretaker')

@section('title', 'Verify Payments')

@section('content')
<div class="p-4">
    <h1 class="text-2xl font-bold text-primary mb-4">Verify Payments</h1>

    <div class="alert alert-info text-sm">
        Enter a payment ID awaiting verification and confirm it. The amount is fixed
        by the system and cannot be changed here.
    </div>

    <div class="card shadow-sm rounded-lg border-0" style="max-width: 480px;">
        <div class="card-body">
            <div class="mb-3">
                <label class="form-label">Payment ID</label>
                <input type="number" class="form-control" id="payment-id" placeholder="e.g. 12">
            </div>
            <button class="btn btn-primary" id="verify-btn">Verify Payment</button>
            <div class="mt-3" id="verify-result"></div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script type="module">
    import { api, renderError } from '/resources/js/api.js';

    document.getElementById('verify-btn').addEventListener('click', async () => {
        const id = document.getElementById('payment-id').value;
        const out = document.getElementById('verify-result');
        if (!id) {
            out.innerHTML = '<div class="alert alert-warning">Enter a payment ID.</div>';
            return;
        }
        try {
            const res = await api('/api/caretaker/payments/' + id + '/verify', { method: 'POST' });
            out.innerHTML = '<div class="alert alert-success">' + res.message +
                ' Receipt: ' + (res.payment && res.payment.receipt_url ? res.payment.receipt_url : '-') + '</div>';
        } catch (e) {
            renderError(out, e);
        }
    });
</script>
@endpush
