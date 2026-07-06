@extends('layouts.landlord')

@section('title', 'Maintenance')

@section('content')
    <div class="p-4">
        <h1 class="text-2xl font-bold text-primary mb-4">Maintenance Requests</h1>
        <div id="maint-error"></div>

        <div class="card shadow-sm rounded-lg border-0">
            <div class="card-body p-0">
                <table class="table mb-0 align-middle">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Subject</th>
                            <th>Category</th>
                            <th>Priority</th>
                            <th>Status</th>
                            <th class="text-end">Action</th>
                        </tr>
                    </thead>
                    <tbody id="maint-body">
                        <tr>
                            <td colspan="6" class="text-muted">Enter a maintenance request ID below to act on it.</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <div class="card shadow-sm rounded-lg border-0 mt-4" style="max-width: 480px;">
            <div class="card-body">
                <label class="form-label">Maintenance Request ID</label>
                <input type="number" class="form-control mb-3" id="mreq-id" placeholder="e.g. 1">
                <button class="btn btn-success me-2" id="approve-btn">Approve</button>
                <button class="btn btn-danger" data-bs-toggle="modal" data-bs-target="#rejectModal">Reject</button>
                <div class="mt-3" id="action-result"></div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="rejectModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Reject Request</h5><button class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div id="reject-error"></div>
                    <div class="mb-3"><label class="form-label">Reason</label>
                        <textarea class="form-control" id="reject-notes" rows="3"></textarea>
                    </div>
                </div>
                <div class="modal-footer"><button class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button><button
                        class="btn btn-danger" id="reject-submit">Reject</button></div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        function xsrf() {
            return decodeURIComponent((document.cookie.match(/XSRF-TOKEN=([^;]+)/) || [])[1] || '');
        }

        async function apiFetch(path, options = {}) {
            const method = (options.method || 'GET').toUpperCase();
            if (method !== 'GET') await fetch('/sanctum/csrf-cookie', {
                credentials: 'include'
            });
            const res = await fetch(path, {
                credentials: 'include',
                ...options,
                method,
                headers: {
                    'Accept': 'application/json',
                    'Content-Type': 'application/json',
                    'X-XSRF-TOKEN': xsrf(),
                    ...(options.headers || {})
                },
            });
            const text = await res.text();
            const body = text ? JSON.parse(text) : null;
            if (!res.ok) throw {
                message: (body && body.message) || 'Request failed.'
            };
            return body;
        }

        document.getElementById('approve-btn').addEventListener('click', async () => {
            const id = document.getElementById('mreq-id').value;
            const out = document.getElementById('action-result');
            if (!id) {
                out.innerHTML = '<div class="alert alert-warning">Enter a request ID.</div>';
                return;
            }
            try {
                const res = await apiFetch('/api/landlord/maintenance/' + id + '/approve', {
                    method: 'POST'
                });
                out.innerHTML = '<div class="alert alert-success">' + res.message + '</div>';
            } catch (e) {
                out.innerHTML = '<div class="alert alert-danger">' + e.message + '</div>';
            }
        });

        document.getElementById('reject-submit').addEventListener('click', async () => {
            const id = document.getElementById('mreq-id').value;
            try {
                const res = await apiFetch('/api/landlord/maintenance/' + id + '/reject', {
                    method: 'POST',
                    body: JSON.stringify({
                        resolution_notes: document.getElementById('reject-notes').value
                    }),
                });
                bootstrap.Modal.getInstance(document.getElementById('rejectModal')).hide();
                document.getElementById('action-result').innerHTML = '<div class="alert alert-success">' + res
                    .message + '</div>';
            } catch (e) {
                document.getElementById('reject-error').innerHTML = '<div class="alert alert-danger">' + e
                    .message + '</div>';
            }
        });
    </script>
@endpush
