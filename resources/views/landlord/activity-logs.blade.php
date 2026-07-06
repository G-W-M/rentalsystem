@extends('layouts.landlord')

@section('title', "Caretakers' Daily Logs")

@section('content')
    <div class="p-4">
        <h1 class="text-2xl font-bold text-primary mb-4">Caretaker Daily Logs</h1>
        <div id="page-error"></div>

        <div class="card shadow-sm rounded-lg border-0 mb-3">
            <div class="card-body">
                <div class="row g-2">
                    <div class="col-auto">
                        <select class="form-select form-select-sm" id="filter-caretaker">
                            <option value="">All Caretakers</option>
                        </select>
                    </div>
                    <div class="col-auto"><button class="btn btn-sm btn-outline-secondary" id="filter-apply">Filter</button>
                    </div>
                </div>
            </div>
        </div>

        <div class="card shadow-sm rounded-lg border-0">
            <div class="card-body p-0">
                <table class="table mb-0 align-middle">
                    <thead>
                        <tr>
                            <th>Caretaker</th>
                            <th>Date</th>
                            <th>Activities</th>
                            <th>Notes</th>
                        </tr>
                    </thead>
                    <tbody id="logs-body">
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

        async function loadCaretakerOptions() {
            try {
                const caretakers = await apiFetch('/api/landlord/caretakers');
                const sel = document.getElementById('filter-caretaker');
                caretakers.forEach((c) => {
                    const opt = document.createElement('option');
                    opt.value = c.user_id;
                    opt.textContent = c.user ? c.user.full_name : ('Caretaker #' + c.user_id);
                    sel.appendChild(opt);
                });
            } catch (e) {}
        }

        async function load() {
            try {
                const caretakerId = document.getElementById('filter-caretaker').value;
                const params = new URLSearchParams();
                if (caretakerId) params.set('caretaker_id', caretakerId);

                const page = await apiFetch('/api/landlord/activity-logs?' + params.toString());
                const items = page.data || [];
                const body = document.getElementById('logs-body');
                body.innerHTML = items.length ?
                    items.map((l) => {
                        const name = l.caretaker && l.caretaker.user ? l.caretaker.user.full_name : '-';
                        return '<tr><td>' + name + '</td><td>' + fmt(l.log_date) +
                            '</td><td style="white-space:pre-line;">' +
                            l.activities_performed + '</td><td>' + (l.notes || '-') + '</td></tr>';
                    }).join('') :
                    '<tr><td colspan="4" class="text-muted">No logs found</td></tr>';
            } catch (e) {
                document.getElementById('page-error').innerHTML = '<div class="alert alert-danger">' + e.message +
                    '</div>';
            }
        }

        document.getElementById('filter-apply').addEventListener('click', load);

        loadCaretakerOptions();
        load();
    </script>
@endpush
