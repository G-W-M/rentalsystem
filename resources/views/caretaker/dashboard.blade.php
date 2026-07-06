@extends('layouts.caretaker')

@section('title', 'Dashboard')

@section('content')
    <div class="p-4">
        <h1 class="text-2xl font-bold text-primary mb-4">Caretaker Dashboard</h1>
        <div id="kpi-error"></div>
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
                        <div class="text-xs text-gray-600 text-uppercase">Completed</div>
                        <div class="text-3xl font-bold text-success" data-kpi="completed">--</div>
                    </div>
                </div>
            </div>
        </div>
        <div class="card shadow-sm rounded-lg border-0 mt-4">
            <div class="card-header bg-white fw-semibold">Recent Tasks</div>
            <div class="card-body p-0">
                <table class="table mb-0">
                    <thead>
                        <tr>
                            <th>Subject</th>
                            <th>Priority</th>
                        </tr>
                    </thead>
                    <tbody id="recent-tasks">
                        <tr>
                            <td colspan="2" class="text-muted">Loading...</td>
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

        async function load() {
            try {
                const res = await fetch('/api/caretaker/dashboard', {
                    credentials: 'include',
                    headers: {
                        'Accept': 'application/json',
                        'X-XSRF-TOKEN': xsrf()
                    },
                });
                const data = await res.json();
                if (!res.ok) throw new Error(data.message || 'Failed to load dashboard.');

                document.querySelectorAll('[data-kpi]').forEach((el) => {
                    el.textContent = data[el.getAttribute('data-kpi')] ?? 0;
                });

                const rows = (data.recent_tasks || []).map((t) => {
                    const mr = t.maintenance_request || {};
                    return '<tr><td>' + (mr.subject || t.task_description || '-') +
                        '</td><td><span class="badge bg-secondary">' + (mr.priority || t.priority || '-') +
                        '</span></td></tr>';
                });
                document.getElementById('recent-tasks').innerHTML =
                    rows.length ? rows.join('') : '<tr><td colspan="2" class="text-muted">No tasks yet</td></tr>';
            } catch (e) {
                document.getElementById('kpi-error').innerHTML = '<div class="alert alert-danger">' + e.message +
                    '</div>';
            }
        }
        load();
    </script>
@endpush
