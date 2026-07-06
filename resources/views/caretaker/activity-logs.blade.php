@extends('layouts.caretaker')

@section('title', 'Daily Activity Log')

@section('content')
    <div class="p-4">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h1 class="text-2xl font-bold text-primary mb-0">Daily Activity Log</h1>
            <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#logModal">
                <i class="fas fa-plus me-2"></i>Submit Today's Log
            </button>
        </div>

        <div id="page-error"></div>

        <div class="card shadow-sm rounded-lg border-0">
            <div class="card-body p-0">
                <table class="table mb-0 align-middle">
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Activities</th>
                            <th>Notes</th>
                        </tr>
                    </thead>
                    <tbody id="logs-body">
                        <tr>
                            <td colspan="3" class="text-muted">Loading...</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="modal fade" id="logModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Submit Daily Log</h5><button class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div id="submit-error"></div>
                    <div class="mb-3"><label class="form-label">Date</label><input type="date" class="form-control"
                            id="log-date"></div>
                    <div class="mb-3"><label class="form-label">Activities Performed</label>
                        <textarea class="form-control" id="log-activities" rows="4"
                            placeholder="1. Inspected Unit A1...&#10;2. Fixed leaking tap..."></textarea>
                    </div>
                    <div class="mb-3"><label class="form-label">Notes (optional)</label>
                        <textarea class="form-control" id="log-notes" rows="2"></textarea>
                    </div>
                </div>
                <div class="modal-footer"><button class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button><button
                        class="btn btn-primary" id="submit-log">Submit</button></div>
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
                    Accept: 'application/json',
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

        function fmt(iso) {
            if (!iso) return '-';
            const d = new Date(iso);
            if (isNaN(d.getTime())) return iso;
            return d.toLocaleDateString(undefined, {
                year: 'numeric',
                month: 'short',
                day: 'numeric'
            });
        }

        async function load() {
            try {
                const page = await apiFetch('/api/caretaker/activity-logs');
                const items = page.data || [];
                const body = document.getElementById('logs-body');
                body.innerHTML = items.length ?
                    items.map((l) => '<tr><td>' + fmt(l.log_date) + '</td><td style="white-space:pre-line;">' +
                        l.activities_performed + '</td><td>' + (l.notes || '-') + '</td></tr>').join('') :
                    '<tr><td colspan="3" class="text-muted">No logs submitted yet</td></tr>';
            } catch (e) {
                document.getElementById('page-error').innerHTML = '<div class="alert alert-danger">' + e.message +
                    '</div>';
            }
        }

        document.getElementById('logModal').addEventListener('show.bs.modal', () => {
            document.getElementById('log-date').value = new Date().toISOString().split('T')[0];
            document.getElementById('log-activities').value = '';
            document.getElementById('log-notes').value = '';
            document.getElementById('submit-error').innerHTML = '';
        });

        document.getElementById('submit-log').addEventListener('click', async () => {
            const out = document.getElementById('submit-error');
            try {
                await apiFetch('/api/caretaker/activity-logs', {
                    method: 'POST',
                    body: JSON.stringify({
                        log_date: document.getElementById('log-date').value,
                        activities_performed: document.getElementById('log-activities').value,
                        notes: document.getElementById('log-notes').value,
                    }),
                });
                bootstrap.Modal.getInstance(document.getElementById('logModal')).hide();
                load();
            } catch (e) {
                out.innerHTML = '<div class="alert alert-danger">' + e.message + '</div>';
            }
        });

        load();
    </script>
@endpush
