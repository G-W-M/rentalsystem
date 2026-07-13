@extends('layouts.admin')

@section('title', 'Activity Logs')

@section('content')
    <div class="p-4">
        <h1 class="text-2xl font-bold text-primary mb-4">Activity Logs</h1>

        <ul class="nav nav-tabs mb-3" id="logTabs">
            <li class="nav-item"><button class="nav-link active" data-tab="audit">Audit Trail</button></li>
            <li class="nav-item"><button class="nav-link" data-tab="caretaker">Caretaker Activity</button></li>
        </ul>

        <div id="page-error"></div>

        <div class="card shadow-sm rounded-lg border-0">
            <div class="card-body p-0">
                <table class="table mb-0 align-middle">
                    <thead id="table-head"></thead>
                    <tbody id="table-body">
                        <tr>
                            <td class="text-muted p-3">Loading...</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        function xsrf() {
            return decodeURIComponent((document.cookie.match(/XSRF-TOKEN=([^;]+)/) || [])[1] || '');
        }

        async function apiFetch(path) {
            const res = await fetch(path, {
                credentials: 'include',
                headers: {
                    Accept: 'application/json',
                    'X-XSRF-TOKEN': xsrf()
                }
            });
            const body = await res.json();
            if (!res.ok) throw {
                message: body.message || 'Request failed.'
            };
            return body;
        }

        function timestamp(iso) {
            if (!iso) return '-';
            const d = new Date(iso);
            if (isNaN(d.getTime())) return iso;
            return d.toLocaleString(undefined, {
                year: 'numeric',
                month: 'short',
                day: 'numeric',
                hour: '2-digit',
                minute: '2-digit',
                second: '2-digit',
            });
        }

        let currentTab = 'audit';

        async function loadAudit() {
            document.getElementById('table-head').innerHTML =
                '<tr><th>User</th><th>Action</th><th>IP</th><th>Timestamp</th></tr>';
            const page = await apiFetch('/api/admin/audit-trails');
            const items = page.data || [];
            document.getElementById('table-body').innerHTML = items.length ?
                items.map((a) =>
                    '<tr>' +
                    '<td>' + (a.full_name || 'System') + '</td>' +
                    '<td>' + a.action + '</td>' +
                    '<td>' + (a.ip_address || '-') + '</td>' +
                    '<td>' + timestamp(a.created_at) + '</td>' +
                    '</tr>'
                ).join('') :
                '<tr><td colspan="4" class="text-muted p-3">No audit entries found.</td></tr>';
        }

        async function loadCaretakerActivity() {
            document.getElementById('table-head').innerHTML =
                '<tr><th>Caretaker</th><th>Property</th><th>Type</th><th>Description</th><th>Timestamp</th><th>Status</th></tr>';
            const page = await apiFetch('/api/admin/caretaker-activity');
            const items = page.data || [];
            document.getElementById('table-body').innerHTML = items.length ?
                items.map((l) =>
                    '<tr>' +
                    '<td>' + l.caretaker_name + '</td>' +
                    '<td>' + (l.property_name || '-') + '</td>' +
                    '<td>' + l.activity_type + '</td>' +
                    '<td>' + l.description + '</td>' +
                    '<td>' + timestamp(l.log_date) + '</td>' +
                    '<td><span class="badge bg-secondary">' + l.status + '</span></td>' +
                    '</tr>'
                ).join('') :
                '<tr><td colspan="6" class="text-muted p-3">No caretaker activity found.</td></tr>';
        }

        async function load() {
            document.getElementById('page-error').innerHTML = '';
            document.getElementById('table-body').innerHTML = '<tr><td class="text-muted p-3">Loading...</td></tr>';
            try {
                if (currentTab === 'audit') await loadAudit();
                else await loadCaretakerActivity();
            } catch (e) {
                document.getElementById('page-error').innerHTML =
                    '<div class="alert alert-danger">' + e.message + '</div>';
            }
        }

        document.getElementById('logTabs').addEventListener('click', (ev) => {
            const btn = ev.target.closest('[data-tab]');
            if (!btn) return;
            document.querySelectorAll('#logTabs .nav-link').forEach((el) => el.classList.remove('active'));
            btn.classList.add('active');
            currentTab = btn.getAttribute('data-tab');
            load();
        });

        load();
    </script>
@endpush
