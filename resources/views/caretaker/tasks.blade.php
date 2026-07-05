@extends('layouts.caretaker')

@section('title', 'Tasks')

@section('content')
<div class="p-4">
    <h1 class="text-2xl font-bold text-primary mb-4">My Tasks</h1>

    <div id="tasks-error"></div>

    <div class="card shadow-sm rounded-lg border-0">
        <div class="card-body p-0">
            <table class="table mb-0 align-middle">
                <thead>
                    <tr>
                        <th>Description</th>
                        <th>Priority</th>
                        <th>Status</th>
                        <th class="text-end">Action</th>
                    </tr>
                </thead>
                <tbody id="tasks-body">
                    <tr><td colspan="4" class="text-muted">Loading...</td></tr>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Complete Task Modal -->
<div class="modal fade" id="completeModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Complete Task</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <input type="hidden" id="complete-task-id">
                <div class="mb-3">
                    <label class="form-label">Completion Notes</label>
                    <textarea class="form-control" id="completion-notes" rows="3"></textarea>
                </div>
                <div class="mb-3">
                    <label class="form-label">Completion Photo URL (optional)</label>
                    <input type="text" class="form-control" id="completion-photo">
                </div>
                <div id="complete-error"></div>
            </div>
            <div class="modal-footer">
                <button class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button class="btn btn-primary" id="complete-submit">Mark Complete</button>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script type="module">
    import { api, renderError } from '/resources/js/api.js';

    const body = document.getElementById('tasks-body');

    function badge(status) {
        const map = { assigned: 'secondary', in_progress: 'info', completed: 'success', cancelled: 'dark' };
        return '<span class="badge bg-' + (map[status] || 'secondary') + '">' + status + '</span>';
    }

    async function load() {
        try {
            const tasks = await api('/api/caretaker/tasks');
            if (!tasks.length) {
                body.innerHTML = '<tr><td colspan="4" class="text-muted">No tasks assigned</td></tr>';
                return;
            }
            body.innerHTML = tasks.map((t) => {
                const mr = t.maintenance_request || {};
                let action = '';
                if (t.status === 'assigned') {
                    action = '<button class="btn btn-sm btn-info" data-start="' + t.id + '">Start</button>';
                } else if (t.status === 'in_progress') {
                    action = '<button class="btn btn-sm btn-primary" data-complete="' + t.id + '">Complete</button>';
                } else {
                    action = '<span class="text-muted small">—</span>';
                }
                return '<tr><td>' + (mr.subject || t.task_description || '-') + '</td>' +
                    '<td>' + (t.priority || '-') + '</td>' +
                    '<td>' + badge(t.status) + '</td>' +
                    '<td class="text-end">' + action + '</td></tr>';
            }).join('');
        } catch (e) {
            renderError(document.getElementById('tasks-error'), e);
        }
    }

    body.addEventListener('click', async (ev) => {
        const startId = ev.target.getAttribute('data-start');
        const completeId = ev.target.getAttribute('data-complete');

        if (startId) {
            try {
                await api('/api/caretaker/tasks/' + startId + '/start', { method: 'POST' });
                load();
            } catch (e) {
                renderError(document.getElementById('tasks-error'), e);
            }
        }

        if (completeId) {
            document.getElementById('complete-task-id').value = completeId;
            document.getElementById('completion-notes').value = '';
            document.getElementById('completion-photo').value = '';
            document.getElementById('complete-error').innerHTML = '';
            new bootstrap.Modal(document.getElementById('completeModal')).show();
        }
    });

    document.getElementById('complete-submit').addEventListener('click', async () => {
        const id = document.getElementById('complete-task-id').value;
        try {
            await api('/api/caretaker/tasks/' + id + '/complete', {
                method: 'POST',
                body: JSON.stringify({
                    completion_notes: document.getElementById('completion-notes').value,
                    completion_photo: document.getElementById('completion-photo').value || null,
                }),
            });
            bootstrap.Modal.getInstance(document.getElementById('completeModal')).hide();
            load();
        } catch (e) {
            renderError(document.getElementById('complete-error'), e);
        }
    });

    load();
</script>
@endpush
