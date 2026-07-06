@extends('layouts.caretaker')

@section('title', 'Maintenance')

@section('content')
    <div class="p-4">
        <h1 class="text-2xl font-bold text-primary mb-4">Assigned Maintenance</h1>
        <div id="maint-error"></div>
        <div class="card shadow-sm rounded-lg border-0">
            <div class="card-body p-0">
                <table class="table mb-0 align-middle">
                    <thead>
                        <tr>
                            <th>Subject</th>
                            <th>Category</th>
                            <th>Priority</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody id="maint-body">
                        <tr>
                            <td colspan="4" class="text-muted">Loading...</td>
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
                const res = await fetch('/api/caretaker/tasks', {
                    credentials: 'include',
                    headers: {
                        'Accept': 'application/json',
                        'X-XSRF-TOKEN': xsrf()
                    },
                });
                const tasks = await res.json();
                if (!res.ok) throw new Error(tasks.message || 'Failed to load.');

                const rows = tasks.map((t) => {
                    const mr = t.maintenance_request || {};
                    return '<tr><td>' + (mr.subject || t.task_description || '-') + '</td><td>' +
                        (mr.category || '-') + '</td><td>' + (mr.priority || t.priority || '-') +
                        '</td><td><span class="badge bg-secondary">' + t.status + '</span></td></tr>';
                });
                document.getElementById('maint-body').innerHTML =
                    rows.length ? rows.join('') : '<tr><td colspan="4" class="text-muted">Nothing assigned</td></tr>';
            } catch (e) {
                document.getElementById('maint-error').innerHTML = '<div class="alert alert-danger">' + e.message +
                    '</div>';
            }
        }
        load();
    </script>
@endpush
