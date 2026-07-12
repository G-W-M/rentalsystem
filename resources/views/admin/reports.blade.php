@extends('layouts.admin')

@section('title', 'Reports & Exports')

@section('content')
<div class="p-4">
    <h1 class="text-2xl font-bold text-primary mb-1">Reports &amp; Exports</h1>
    <p class="text-muted small mb-4">
        Download system-wide data as CSV (for spreadsheets) or PDF (for filing and sharing).
        Leave filters blank to export everything.
    </p>

    <div class="row g-3">

        {{-- ============ USERS ============ --}}
        <div class="col-12 col-lg-6">
            <div class="card shadow-sm rounded-lg border-0 h-100">
                <div class="card-header bg-white fw-semibold">
                    <i class="fas fa-users me-2"></i> All Users
                </div>
                <div class="card-body">
                    <p class="text-muted small">Every account in the system across all roles, with status and last login.</p>

                    <div class="row g-2 mb-3">
                        <div class="col-6">
                            <label class="form-label small">Role</label>
                            <select class="form-select form-select-sm" id="users-role">
                                <option value="">All roles</option>
                                <option value="admin">Admin</option>
                                <option value="landlord">Landlord</option>
                                <option value="caretaker">Caretaker</option>
                                <option value="tenant">Tenant</option>
                            </select>
                        </div>
                        <div class="col-6">
                            <label class="form-label small">Status</label>
                            <select class="form-select form-select-sm" id="users-active">
                                <option value="">All</option>
                                <option value="1">Active only</option>
                                <option value="0">Inactive only</option>
                            </select>
                        </div>
                        <div class="col-6">
                            <label class="form-label small">Registered from</label>
                            <input type="date" class="form-control form-control-sm" id="users-from">
                        </div>
                        <div class="col-6">
                            <label class="form-label small">Registered to</label>
                            <input type="date" class="form-control form-control-sm" id="users-to">
                        </div>
                    </div>

                    <button class="btn btn-sm btn-outline-success me-1"
                            onclick="downloadExport('users', 'csv')">
                        <i class="fas fa-file-csv me-1"></i> CSV
                    </button>
                    <button class="btn btn-sm btn-outline-danger"
                            onclick="downloadExport('users', 'pdf')">
                        <i class="fas fa-file-pdf me-1"></i> PDF
                    </button>
                </div>
            </div>
        </div>

        {{-- ============ PAYMENTS ============ --}}
        <div class="col-12 col-lg-6">
            <div class="card shadow-sm rounded-lg border-0 h-100">
                <div class="card-header bg-white fw-semibold">
                    <i class="fas fa-credit-card me-2"></i> All Payments
                </div>
                <div class="card-body">
                    <p class="text-muted small">Every rent payment across every landlord, with verification status.</p>

                    <div class="row g-2 mb-3">
                        <div class="col-12">
                            <label class="form-label small">Status</label>
                            <select class="form-select form-select-sm" id="payments-status">
                                <option value="">All statuses</option>
                                <option value="pending">Pending</option>
                                <option value="awaiting_verification">Awaiting verification</option>
                                <option value="completed">Completed</option>
                                <option value="rejected">Rejected</option>
                            </select>
                        </div>
                        <div class="col-6">
                            <label class="form-label small">Due from</label>
                            <input type="date" class="form-control form-control-sm" id="payments-from">
                        </div>
                        <div class="col-6">
                            <label class="form-label small">Due to</label>
                            <input type="date" class="form-control form-control-sm" id="payments-to">
                        </div>
                    </div>

                    <button class="btn btn-sm btn-outline-success me-1"
                            onclick="downloadExport('payments', 'csv')">
                        <i class="fas fa-file-csv me-1"></i> CSV
                    </button>
                    <button class="btn btn-sm btn-outline-danger"
                            onclick="downloadExport('payments', 'pdf')">
                        <i class="fas fa-file-pdf me-1"></i> PDF
                    </button>
                </div>
            </div>
        </div>

        {{-- ============ MAINTENANCE ============ --}}
        <div class="col-12 col-lg-6">
            <div class="card shadow-sm rounded-lg border-0 h-100">
                <div class="card-header bg-white fw-semibold">
                    <i class="fas fa-wrench me-2"></i> All Maintenance Requests
                </div>
                <div class="card-body">
                    <p class="text-muted small">Every maintenance request system-wide, with category, priority and status.</p>

                    <div class="row g-2 mb-3">
                        <div class="col-12">
                            <label class="form-label small">Status</label>
                            <select class="form-select form-select-sm" id="maintenance-status">
                                <option value="">All statuses</option>
                                <option value="pending">Pending</option>
                                <option value="approved">Approved</option>
                                <option value="rejected">Rejected</option>
                                <option value="in_progress">In progress</option>
                                <option value="completed">Completed</option>
                                <option value="confirmed">Confirmed</option>
                            </select>
                        </div>
                        <div class="col-6">
                            <label class="form-label small">Submitted from</label>
                            <input type="date" class="form-control form-control-sm" id="maintenance-from">
                        </div>
                        <div class="col-6">
                            <label class="form-label small">Submitted to</label>
                            <input type="date" class="form-control form-control-sm" id="maintenance-to">
                        </div>
                    </div>

                    <button class="btn btn-sm btn-outline-success me-1"
                            onclick="downloadExport('maintenance', 'csv')">
                        <i class="fas fa-file-csv me-1"></i> CSV
                    </button>
                    <button class="btn btn-sm btn-outline-danger"
                            onclick="downloadExport('maintenance', 'pdf')">
                        <i class="fas fa-file-pdf me-1"></i> PDF
                    </button>
                </div>
            </div>
        </div>

        {{-- ============ PROPERTIES ============ --}}
        <div class="col-12 col-lg-6">
            <div class="card shadow-sm rounded-lg border-0 h-100">
                <div class="card-header bg-white fw-semibold">
                    <i class="fas fa-building me-2"></i> All Properties
                </div>
                <div class="card-body">
                    <p class="text-muted small">
                        Every property with its landlord, caretaker, unit counts and occupancy rate.
                    </p>

                    <div class="row g-2 mb-3">
                        <div class="col-6">
                            <label class="form-label small">Added from</label>
                            <input type="date" class="form-control form-control-sm" id="properties-from">
                        </div>
                        <div class="col-6">
                            <label class="form-label small">Added to</label>
                            <input type="date" class="form-control form-control-sm" id="properties-to">
                        </div>
                    </div>

                    <button class="btn btn-sm btn-outline-success me-1"
                            onclick="downloadExport('properties', 'csv')">
                        <i class="fas fa-file-csv me-1"></i> CSV
                    </button>
                    <button class="btn btn-sm btn-outline-danger"
                            onclick="downloadExport('properties', 'pdf')">
                        <i class="fas fa-file-pdf me-1"></i> PDF
                    </button>
                </div>
            </div>
        </div>

        {{-- ============ UNITS ============ --}}
        <div class="col-12 col-lg-6">
            <div class="card shadow-sm rounded-lg border-0 h-100">
                <div class="card-header bg-white fw-semibold">
                    <i class="fas fa-door-open me-2"></i> All Units
                </div>
                <div class="card-body">
                    <p class="text-muted small">
                        Every unit with rent amount, occupancy status and current tenant. Includes rent totals.
                    </p>

                    <div class="row g-2 mb-3">
                        <div class="col-12">
                            <label class="form-label small">Status</label>
                            <select class="form-select form-select-sm" id="units-status">
                                <option value="">All statuses</option>
                                <option value="available">Available</option>
                                <option value="occupied">Occupied</option>
                                <option value="maintenance">Under maintenance</option>
                            </select>
                        </div>
                    </div>

                    <button class="btn btn-sm btn-outline-success me-1"
                            onclick="downloadExport('units', 'csv')">
                        <i class="fas fa-file-csv me-1"></i> CSV
                    </button>
                    <button class="btn btn-sm btn-outline-danger"
                            onclick="downloadExport('units', 'pdf')">
                        <i class="fas fa-file-pdf me-1"></i> PDF
                    </button>
                </div>
            </div>
        </div>

    </div>
</div>
@endsection

@push('scripts')
<script>
    /**
     * Builds the query string from whichever filter inputs exist for that
     * report, then triggers the download by navigating to the export URL.
     *
     * A plain navigation (rather than fetch) is used deliberately here:
     * the browser handles the Content-Disposition: attachment response
     * natively, giving a real "Save as..." download. Doing this via fetch
     * would require manually creating a Blob and an object URL for no
     * benefit — and the session cookie is sent automatically on a
     * same-origin navigation, so auth still works.
     */
    function downloadExport(report, format) {
        const params = new URLSearchParams();

        const add = (id, key) => {
            const el = document.getElementById(id);
            if (el && el.value !== '') {
                params.append(key, el.value);
            }
        };

        // shared date range (present on most reports)
        add(report + '-from', 'from');
        add(report + '-to', 'to');

        // report-specific filters
        add(report + '-status', 'status');
        add(report + '-role', 'role');

        // users has a separate is_active select
        const activeEl = document.getElementById(report + '-active');
        if (activeEl && activeEl.value !== '') {
            params.append('is_active', activeEl.value);
        }

        const qs = params.toString();
        const url = '/api/admin/' + report + '/export/' + format + (qs ? '?' + qs : '');

        window.location.href = url;
    }
</script>
@endpush
