@extends('layouts.caretaker')

@section('title', 'Verified Payments')

@section('content')
<div class="p-4">
    <h1 class="text-2xl font-bold text-primary mb-4">Payments I've Verified</h1>
    <div id="page-error"></div>

    <div class="card shadow-sm rounded-lg border-0">
        <div class="card-body p-0">
            <table class="table mb-0 align-middle">
                <thead><tr><th>Tenant</th><th>Unit</th><th>Amount</th><th>Verified On</th><th>Receipt</th></tr></thead>
                <tbody id="pay-body"><tr><td colspan="5" class="text-muted">Loading...</td></tr></tbody>
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

    async function load() {
        try {
            const res = await fetch('/api/caretaker/payments/verified', {
                credentials: 'include',
                headers: { Accept: 'application/json', 'X-XSRF-TOKEN': xsrf() },
            });
            const page = await res.json();
            if (!res.ok) throw new Error(page.message || 'Failed to load.');

            const items = page.data || [];
            const body = document.getElementById('pay-body');
            body.innerHTML = items.length
                ? items.map((p) => {
                    const tenantName = p.tenant && p.tenant.user ? p.tenant.user.full_name : '-';
                    const unitNumber = p.unit ? p.unit.unit_number : '-';
                    return '<tr><td>' + tenantName + '</td><td>' + unitNumber + '</td><td>' + money(p.amount) +
                        '</td><td>' + fmt(p.verified_at) + '</td><td><span class="badge bg-success">' + (p.receipt_url || '-') + '</span></td></tr>';
                }).join('')
                : '<tr><td colspan="5" class="text-muted">You have not verified any payments yet</td></tr>';
        } catch (e) {
            document.getElementById('page-error').innerHTML = '<div class="alert alert-danger">' + e.message + '</div>';
        }
    }
    load();
</script>
@endpush