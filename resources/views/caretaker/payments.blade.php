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
    <script>
        function xsrf() {
            return decodeURIComponent((document.cookie.match(/XSRF-TOKEN=([^;]+)/) || [])[1] || '');
        }

        document.getElementById('verify-btn').addEventListener('click', async () => {
            const id = document.getElementById('payment-id').value;
            const out = document.getElementById('verify-result');
            out.innerHTML = '';

            if (!id) {
                out.innerHTML = '<div class="alert alert-warning">Enter a payment ID.</div>';
                return;
            }

            try {
                await fetch('/sanctum/csrf-cookie', {
                    credentials: 'include'
                });

                const res = await fetch('/api/caretaker/payments/' + id + '/verify', {
                    method: 'POST',
                    credentials: 'include',
                    headers: {
                        'Accept': 'application/json',
                        'Content-Type': 'application/json',
                        'X-XSRF-TOKEN': xsrf(),
                    },
                });

                const body = await res.json();

                if (!res.ok) {
                    out.innerHTML = '<div class="alert alert-danger">' + (body.message ||
                        'Verification failed.') + '</div>';
                    return;
                }

                const receipt = body.payment && body.payment.receipt_url ? body.payment.receipt_url : '-';
                out.innerHTML = '<div class="alert alert-success">' + body.message + ' Receipt: ' + receipt +
                    '</div>';
            } catch (e) {
                out.innerHTML = '<div class="alert alert-danger">Network error. Try again.</div>';
            }
        });
    </script>
@endpush
