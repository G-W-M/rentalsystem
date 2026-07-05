@extends('layouts.tenant')

@section('title', 'Maintenance')

@section('content')
<div class="p-4">
    <h1 class="text-2xl font-bold text-primary mb-4">Maintenance</h1>

    <div class="row g-4">
        <div class="col-12 col-lg-5">
            <div class="card shadow-sm rounded-lg border-0">
                <div class="card-header bg-white fw-semibold">Raise a Request</div>
                <div class="card-body">
                    <div class="mb-3">
                        <label class="form-label">Category</label>
                        <select class="form-select" id="category">
                            <option value="plumbing">Plumbing</option>
                            <option value="electrical">Electrical</option>
                            <option value="structural">Structural</option>
                            <option value="appliance">Appliance</option>
                            <option value="pest">Pest</option>
                            <option value="security">Security</option>
                            <option value="other">Other</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Subject</label>
                        <input type="text" class="form-control" id="subject">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Priority</label>
                        <select class="form-select" id="priority">
                            <option value="low">Low</option>
                            <option value="medium" selected>Medium</option>
                            <option value="high">High</option>
                            <option value="emergency">Emergency</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Description</label>
                        <textarea class="form-control" id="description" rows="3"></textarea>
                    </div>
                    <button class="btn btn-primary w-100" id="submit-request">Submit Request</button>
                    <div class="mt-3" id="submit-result"></div>
                </div>
            </div>
        </div>

        <div class="col-12 col-lg-7">
            <div class="card shadow-sm rounded-lg border-0">
                <div class="card-header bg-white fw-semibold">My Requests</div>
                <div class="card-body p-0">
                    <table class="table mb-0 align-middle">
                        <thead>
                            <tr>
                                <th>Subject</th>
                                <th>Status</th>
                                <th class="text-end">Action</th>
                            </tr>
                        </thead>
                        <tbody id="requests-body">
                            <tr><td colspan="3" class="text-muted">Loading...</td></tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script type="module">
    import { api, renderError } from '/resources/js/api.js';

    // Requests list is derived from the dashboard's open count plus a dedicated
    // fetch is not exposed for tenants; we display submitted requests optimistically
    // and rely on confirm action for completed ones. For a full list, the tenant
    // payments/dashboard endpoints are used; here we track this session's submissions.
    const submitted = [];

    function renderRequests() {
        const body = document.getElementById('requests-body');
        if (!submitted.length) {
            body.innerHTML = '<tr><td colspan="3" class="text-muted">No requests this session</td></tr>';
            return;
        }
        body.innerHTML = submitted.map((r) => {
            let action = '<span class="text-muted small">—</span>';
            if (r.status === 'awaiting_confirm') {
                action = '<button class="btn btn-sm btn-success" data-confirm="' + r.id + '">Confirm</button>';
            }
            return '<tr><td>' + (r.subject || '-') + '</td>' +
                '<td><span class="badge bg-secondary">' + r.status + '</span></td>' +
                '<td class="text-end">' + action + '</td></tr>';
        }).join('');
    }

    document.getElementById('submit-request').addEventListener('click', async () => {
        const out = document.getElementById('submit-result');
        try {
            const res = await api('/api/tenant/maintenance', {
                method: 'POST',
                body: JSON.stringify({
                    category: document.getElementById('category').value,
                    subject: document.getElementById('subject').value,
                    priority: document.getElementById('priority').value,
                    description: document.getElementById('description').value,
                }),
            });
            out.innerHTML = '<div class="alert alert-success">Request submitted.</div>';
            submitted.unshift({ id: res.id, subject: res.subject, status: res.status });
            renderRequests();
            document.getElementById('subject').value = '';
            document.getElementById('description').value = '';
        } catch (e) {
            renderError(out, e);
        }
    });

    document.getElementById('requests-body').addEventListener('click', async (ev) => {
        const confirmId = ev.target.getAttribute('data-confirm');
        if (!confirmId) return;
        try {
            await api('/api/tenant/maintenance/' + confirmId + '/confirm', { method: 'POST' });
            const item = submitted.find((r) => String(r.id) === String(confirmId));
            if (item) item.status = 'resolved';
            renderRequests();
        } catch (e) {
            renderError(document.getElementById('submit-result'), e);
        }
    });

    renderRequests();
</script>
@endpush
