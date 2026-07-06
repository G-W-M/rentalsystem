@extends('layouts.caretaker')

@section('title', 'Activity Log')

@section('content')
    <div class="p-4">
        <h1 class="text-2xl font-bold text-primary mb-4">My Activity Log</h1>
        <div id="log-error"></div>
        <div class="card shadow-sm rounded-lg border-0">
            <div class="card-body p-0">
                <table class="table mb-0 align-middle">
                    <thead>
                        <tr>
                            <th>Action</th>
                            <th>IP Address</th>
                            <th>When</th>
                        </tr>
                    </thead>
                    <tbody id="log-body">
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

        async function load() {
            try {
                const res = await fetch('/api/notifications', {
                    credentials: 'include',
                    headers: {
                        'Accept': 'application/json',
                        'X-XSRF-TOKEN': xsrf()
                    },
                });
                const page = await res.json();
                if (!res.ok) throw new Error(page.message || 'Failed to load.');
                const items = page.data || [];
                const body = document.getElementById('log-body');
                body.innerHTML = items.length ?
                    items.map((n) => '<tr><td>' + n.title + '</td><td>' + (n.message || '-') + '</td><td>' + new Date(n
                        .created_at).toLocaleString() + '</td></tr>').join('') :
                    '<tr><td colspan="3" class="text-muted">No activity recorded yet</td></tr>';
            } catch (e) {
                document.getElementById('log-error').innerHTML = '<div class="alert alert-danger">' + e.message +
                    '</div>';
            }
        }
        load();
    </script>
@endpush
