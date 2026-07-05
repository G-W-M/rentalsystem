@extends('layouts.tenant')

@section('title', 'Payments')

@section('content')
<div class="p-4">
    <h1 class="text-2xl font-bold text-primary mb-4">My Payments</h1>

    <div id="pay-error"></div>

    <div class="card shadow-sm rounded-lg border-0">
        <div class="card-body p-0">
            <table class="table mb-0 align-middle">
                <thead>
                    <tr>
                        <th>Due Date</th>
                        <th>Amount</th>
                        <th>Status</th>
                        <th>Receipt</th>
                        <th class="text-end">Action</th>
                    </tr>
                </thead>
                <tbody id="pay-body">
                    <tr><td colspan="5" class="text-muted">Loading...</td></tr>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Submit Transaction Code Modal -->
<div class="modal fade" id="txModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Submit Payment Code</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <input type="hidden" id="tx-payment-id">
                <div class="mb-3">
                    <label class="form-label">Transaction Code</label>
                    <input type="text" class="form-control" id="tx-code">
                </div>
                <div class="mb-3">
                    <label class="form-label">Payment Method</label>
                    <input type="text" class="form-control" id="tx-method" value="M-Pesa">
                </div>
                <div id="tx-error"></div>
            </div>
            <div class="modal-footer">
                <button class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button class="btn btn-primary" id="tx-submit">Submit</button>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script type="module">
    import { api, renderError, money } from '/resources/js/api.js';

    const body = document.getElementById('pay-body');

    function badge(status) {
        const map = { pending: 'warning', completed: 'success', failed: 'danger', refunded: 'info' };
        const cls = map[status] || 'secondary';
        const text = cls === 'warning' ? 'text-dark' : '';
        return '<span class="badge bg-' + cls + ' ' + text + '">' + status + '</span>';
    }

    async function load() {
        try {
            const page = await api('/api/tenant/payments');
            const items = (page && page.data) ? page.data : [];
            if (!items.length) {
                body.innerHTML = '<tr><td colspan="5" class="text-muted">No payments yet</td></tr>';
                return;
            }
            body.innerHTML = items.map((p) => {
                let action = '<span class="text-muted small">—</span>';
                if (p.status === 'pending') {
                    action = '<button class="btn btn-sm btn-primary" data-tx="' + p.id + '">Submit Code</button>';
                }
                return '<tr><td>' + (p.due_date || '-') + '</td>' +
                    '<td>' + money(p.amount) + '</td>' +
                    '<td>' + badge(p.status) + '</td>' +
                    '<td>' + (p.receipt_url || '-') + '</td>' +
                    '<td class="text-end">' + action + '</td></tr>';
            }).join('');
        } catch (e) {
            renderError(document.getElementById('pay-error'), e);
        }
    }

    body.addEventListener('click', (ev) => {
        const txId = ev.target.getAttribute('data-tx');
        if (!txId) return;
        document.getElementById('tx-payment-id').value = txId;
        document.getElementById('tx-code').value = '';
        document.getElementById('tx-error').innerHTML = '';
        new bootstrap.Modal(document.getElementById('txModal')).show();
    });

    document.getElementById('tx-submit').addEventListener('click', async () => {
        const id = document.getElementById('tx-payment-id').value;
        try {
            await api('/api/tenant/payments/' + id + '/transaction-code', {
                method: 'POST',
                body: JSON.stringify({
                    transaction_id: document.getElementById('tx-code').value,
                    payment_method: document.getElementById('tx-method').value,
                }),
            });
            bootstrap.Modal.getInstance(document.getElementById('txModal')).hide();
            load();
        } catch (e) {
            renderError(document.getElementById('tx-error'), e);
        }
    });

    load();
</script>
@endpush
