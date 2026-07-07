@extends('layouts.caretaker')

@section('title', 'Dashboard')

@section('content')
    <div class="p-4">
        <h1 class="text-2xl font-bold text-primary mb-4">Caretaker Dashboard</h1>
        <div id="kpi-error"></div>
        
        {{-- KPI Cards --}}
        <div class="row g-3" id="kpi-cards">
            <div class="col-6 col-lg-3">
                <div class="card shadow-sm rounded-lg border-0">
                    <div class="card-body">
                        <div class="text-xs text-gray-600 text-uppercase">Assigned Tasks</div>
                        <div class="text-3xl font-bold text-primary" data-kpi="assigned_tasks">--</div>
                    </div>
                </div>
            </div>
            <div class="col-6 col-lg-3">
                <div class="card shadow-sm rounded-lg border-0">
                    <div class="card-body">
                        <div class="text-xs text-gray-600 text-uppercase">In Progress</div>
                        <div class="text-3xl font-bold text-info" data-kpi="in_progress">--</div>
                    </div>
                </div>
            </div>
            <div class="col-6 col-lg-3">
                <div class="card shadow-sm rounded-lg border-0">
                    <div class="card-body">
                        <div class="text-xs text-gray-600 text-uppercase">Awaiting Confirm</div>
                        <div class="text-3xl font-bold text-warning" data-kpi="awaiting_confirm">--</div>
                    </div>
                </div>
            </div>
            <div class="col-6 col-lg-3">
                <div class="card shadow-sm rounded-lg border-0">
                    <div class="card-body">
                        <div class="text-xs text-gray-600 text-uppercase">Today's Logs</div>
                        <div class="text-3xl font-bold text-success" data-kpi="today_logs">--</div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Recent Tasks --}}
        <div class="card shadow-sm rounded-lg border-0 mt-4">
            <div class="card-header bg-white fw-semibold d-flex justify-content-between align-items-center">
                <span>Recent Tasks</span>
                <a href="{{ route('caretaker.tasks.index') }}" class="btn btn-sm btn-primary">View All</a>
            </div>
            <div class="card-body p-0">
                <table class="table mb-0">
                    <thead>
                        <tr>
                            <th>Subject</th>
                            <th>Priority</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody id="recent-tasks">
                        <tr>
                            <td colspan="3" class="text-muted">Loading...</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        {{-- Recent Activity Logs --}}
        <div class="card shadow-sm rounded-lg border-0 mt-4">
            <div class="card-header bg-white fw-semibold d-flex justify-content-between align-items-center">
                <span>Recent Activity Logs</span>
                <a href="{{ route('caretaker.activity-logs') }}" class="btn btn-sm btn-primary">View All</a>
            </div>
            <div class="card-body p-0">
                <table class="table mb-0">
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Activities</th>
                            <th>Notes</th>
                        </tr>
                    </thead>
                    <tbody id="activity-logs">
                        <tr>
                            <td colspan="3" class="text-muted">Loading...</td>
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

        function formatDate(iso) {
            if (!iso) return '-';
            const d = new Date(iso);
            if (isNaN(d.getTime())) return iso;
            return d.toLocaleDateString(undefined, {
                year: 'numeric',
                month: 'short',
                day: 'numeric'
            });
        }

        function getStatusBadge(status) {
            const map = {
                'pending': 'warning',
                'in_progress': 'info',
                'completed': 'success',
                'cancelled': 'danger',
                'approved': 'success',
                'rejected': 'danger',
                'awaiting_confirm': 'warning'
            };
            const cls = map[status] || 'secondary';
            return '<span class="badge bg-' + cls + (cls === 'warning' ? ' text-dark' : '') + '">' + 
                   status.replace('_', ' ') + '</span>';
        }

        async function load() {
            try {
                // Load dashboard data
                const res = await fetch('/api/caretaker/dashboard', {
                    credentials: 'include',
                    headers: {
                        'Accept': 'application/json',
                        'X-XSRF-TOKEN': xsrf()
                    },
                });
                const data = await res.json();
                if (!res.ok) throw new Error(data.message || 'Failed to load dashboard.');

                // Update KPI cards
                document.querySelectorAll('[data-kpi]').forEach((el) => {
                    el.textContent = data[el.getAttribute('data-kpi')] ?? 0;
                });

                // Update recent tasks
                const rows = (data.recent_tasks || []).map((t) => {
                    const mr = t.maintenance_request || {};
                    const status = t.status || mr.status || 'pending';
                    return '<tr><td>' + (mr.subject || t.task_description || '-') +
                        '</td><td><span class="badge bg-secondary">' + (mr.priority || t.priority || '-') +
                        '</span></td><td>' + getStatusBadge(status) + '</td></tr>';
                });
                document.getElementById('recent-tasks').innerHTML =
                    rows.length ? rows.join('') : '<tr><td colspan="3" class="text-muted">No tasks yet</td></tr>';

                // Load activity logs
                await loadActivityLogs();

            } catch (e) {
                document.getElementById('kpi-error').innerHTML = '<div class="alert alert-danger">' + e.message +
                    '</div>';
            }
        }

        async function loadActivityLogs() {
            try {
                const res = await fetch('/api/caretaker/activity-logs?limit=5', {
                    credentials: 'include',
                    headers: {
                        'Accept': 'application/json',
                        'X-XSRF-TOKEN': xsrf()
                    },
                });
                const data = await res.json();
                if (!res.ok) throw new Error(data.message || 'Failed to load activity logs.');

                const logs = data.data || [];
                const logRows = logs.map((l) => {
                    return '<tr><td>' + formatDate(l.log_date) +
                        '</td><td style="white-space:pre-line;">' + 
                        (l.activities_performed || '-') + 
                        '</td><td>' + (l.notes || '-') + '</td></tr>';
                });
                document.getElementById('activity-logs').innerHTML =
                    logRows.length ? logRows.join('') : 
                    '<tr><td colspan="3" class="text-muted">No activity logs yet. <a href="' + 
                    '{{ route('caretaker.activity-logs') }}" class="text-primary">Create your first log</a></td></tr>';

                // Update today's logs count in KPI
                const today = new Date().toISOString().split('T')[0];
                const todayLogs = logs.filter(l => l.log_date && l.log_date.startsWith(today));
                document.querySelector('[data-kpi="today_logs"]').textContent = todayLogs.length;

            } catch (e) {
                document.getElementById('activity-logs').innerHTML = 
                    '<tr><td colspan="3" class="text-muted">Could not load activity logs: ' + e.message + '</td></tr>';
            }
        }

        // Load dashboard when page is ready
        document.addEventListener('DOMContentLoaded', load);
    </script>
@endpush