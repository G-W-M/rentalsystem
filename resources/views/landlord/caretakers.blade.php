@extends('layouts.landlord')

@section('title', 'Caretakers')

@section('content')
    <div class="p-4">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h1 class="text-2xl font-bold text-primary mb-0">Caretakers</h1>
            <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#createCaretakerModal">
                <i class="fas fa-plus me-2"></i>Add Caretaker
            </button>
        </div>

        <div id="caretaker-error"></div>

        <div class="card shadow-sm rounded-lg border-0">
            <div class="card-body p-0">
                <table class="table mb-0 align-middle">
                    <thead>
                        <tr>
                            <th>Name</th>
                            <th>Email</th>
                            <th>Phone</th>
                            <th>Property</th>
                            <th class="text-end">Action</th>
                        </tr>
                    </thead>
                    <tbody id="caretaker-body">
                        <tr>
                            <td colspan="5" class="text-muted">Loading...</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Create Caretaker Modal -->
    <div class="modal fade" id="createCaretakerModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Add Caretaker</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div id="create-caretaker-error"></div>
                    <div class="mb-2">
                        <label class="form-label">Full Name <span class="text-danger">*</span></label>
                        <input class="form-control" id="c-name" placeholder="Enter full name">
                    </div>
                    <div class="mb-2">
                        <label class="form-label">Email <span class="text-danger">*</span></label>
                        <input type="email" class="form-control" id="c-email" placeholder="Enter email address">
                    </div>
                    <div class="mb-2">
                        <label class="form-label">Username <span class="text-danger">*</span></label>
                        <input class="form-control" id="c-username" placeholder="Choose a username">
                    </div>
                    <div class="mb-2">
                        <label class="form-label">Phone</label>
                        <input class="form-control" id="c-phone" placeholder="Enter phone number">
                    </div>
                    <div class="mb-2">
                        <label class="form-label">ID Number</label>
                        <input class="form-control" id="c-id-number" placeholder="Enter ID number">
                    </div>
                    <div class="mb-2">
                        <label class="form-label">Emergency Contact</label>
                        <input class="form-control" id="c-emergency-contact" placeholder="Emergency contact name">
                    </div>
                    <div class="mb-2">
                        <label class="form-label">Emergency Phone</label>
                        <input class="form-control" id="c-emergency-phone" placeholder="Emergency phone number">
                    </div>
                    <div class="mb-2">
                        <label class="form-label">Skills</label>
                        <input class="form-control" id="c-skills"
                            placeholder="e.g., Plumbing, Electrical (comma separated)">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Assign to Property (Optional)</label>
                        <select class="form-select" id="c-property">
                            <option value="">None (assign later)</option>
                        </select>
                        <small class="text-muted">Leave empty to assign later</small>
                    </div>
                </div>
                <div class="modal-footer">
                    <button class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button class="btn btn-primary" id="create-caretaker-submit">Create Caretaker</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Credentials Modal (replaces native alert()) -->
    <div class="modal fade" id="credentialsModal" tabindex="-1" data-bs-backdrop="static" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header border-0 pb-0">
                    <h5 class="modal-title">Caretaker Created</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body pt-2">
                    <div class="text-center mb-3">
                        <i class="fas fa-check-circle text-success fs-1"></i>
                    </div>
                    <p class="text-center mb-3">Caretaker account created successfully.</p>
                    <div class="alert alert-info d-flex align-items-center justify-content-between mb-2" role="alert">
                        <div>
                            <div class="small text-muted mb-1">Temporary password</div>
                            <code id="credentials-password" class="fs-6"></code>
                        </div>
                        <button type="button" class="btn btn-sm btn-outline-secondary" id="credentials-copy"
                            title="Copy password">
                            <i class="fas fa-copy"></i>
                        </button>
                    </div>
                    <p class="text-center text-muted small mb-0">Please share these credentials with the caretaker
                        directly.</p>
                </div>
                <div class="modal-footer border-0 pt-0">
                    <button type="button" class="btn btn-primary" data-bs-dismiss="modal">Done</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Assign Property Modal -->
    <div class="modal fade" id="assignModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Assign to Property</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" id="assign-caretaker-id">
                    <div id="assign-error"></div>
                    <div class="mb-3">
                        <label class="form-label">Property <span class="text-danger">*</span></label>
                        <select class="form-select" id="assign-property">
                            <option value="">Select a property</option>
                        </select>
                        <small class="text-muted">Select a property to assign this caretaker to</small>
                    </div>
                </div>
                <div class="modal-footer">
                    <button class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button class="btn btn-primary" id="assign-submit">Assign</button>
                </div>
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
            if (method !== 'GET') {
                await fetch('/sanctum/csrf-cookie', {
                    credentials: 'include'
                });
            }
            const res = await fetch(path, {
                credentials: 'include',
                ...options,
                method,
                headers: {
                    'Accept': 'application/json',
                    'Content-Type': 'application/json',
                    'X-XSRF-TOKEN': xsrf(),
                    ...(options.headers || {}),
                },
            });
            const text = await res.text();
            const body = text ? JSON.parse(text) : null;
            if (!res.ok) {
                throw {
                    status: res.status,
                    message: (body && body.message) || 'Request failed.'
                };
            }
            return body;
        }

        function showError(elId, e) {
            document.getElementById(elId).innerHTML =
                '<div class="alert alert-danger">' + (e.message || 'Something went wrong.') + '</div>';
        }

        function clearError(elId) {
            document.getElementById(elId).innerHTML = '';
        }

        // Shows the credentials modal once the given modal element has fully
        // finished hiding, so the two never visually overlap/freeze together.
        function showCredentialsAfterClose(sourceModalEl, password) {
            const onHidden = () => {
                sourceModalEl.removeEventListener('hidden.bs.modal', onHidden);
                document.getElementById('credentials-password').textContent = password;
                new bootstrap.Modal(document.getElementById('credentialsModal')).show();
            };
            sourceModalEl.addEventListener('hidden.bs.modal', onHidden);
        }

        const caretakerBody = document.getElementById('caretaker-body');

        async function loadCaretakers() {
            try {
                const caretakers = await apiFetch('/api/landlord/caretakers');
                if (!caretakers.length) {
                    caretakerBody.innerHTML =
                        '<tr><td colspan="5" class="text-muted text-center">No caretakers yet</td></tr>';
                    return;
                }
                caretakerBody.innerHTML = caretakers.map((c) => {
                    const u = c.user || {};
                    const property = c.property || null;
                    const propertyName = property ? property.name :
                    '<span class="text-muted">Unassigned</span>';
                    const isAssigned = property !== null;
                    const action = isAssigned ?
                        '<button class="btn btn-sm btn-outline-primary" data-assign="' + c.user_id +
                        '">Reassign</button>' :
                        '<button class="btn btn-sm btn-primary" data-assign="' + c.user_id +
                        '">Assign</button>';
                    return '<tr>' +
                        '<td>' + (u.full_name || '-') + '</td>' +
                        '<td>' + (u.email || '-') + '</td>' +
                        '<td>' + (u.phone || '-') + '</td>' +
                        '<td>' + propertyName + '</td>' +
                        '<td class="text-end">' + action + '</td>' +
                        '</tr>';
                }).join('');
            } catch (e) {
                showError('caretaker-error', e);
            }
        }

        // Load properties for dropdown
        async function loadProperties(selectId) {
            try {
                const properties = await apiFetch('/api/landlord/properties');
                const sel = document.getElementById(selectId);
                sel.innerHTML = '<option value="">None</option>' +
                    properties.map((p) => {
                        return '<option value="' + p.id + '">' + p.name + ' (' + p.address + ')</option>';
                    }).join('');
            } catch (e) {
                console.error('Failed to load properties:', e);
            }
        }

        // Load properties for assign modal
        async function loadAssignProperties() {
            try {
                const properties = await apiFetch('/api/landlord/properties');
                const sel = document.getElementById('assign-property');
                sel.innerHTML = '<option value="">Select a property</option>' +
                    properties.map((p) => {
                        return '<option value="' + p.id + '">' + p.name + ' (' + p.address + ')</option>';
                    }).join('');
            } catch (e) {
                showError('assign-error', e);
            }
        }

        function resetCreateForm() {
            document.getElementById('c-name').value = '';
            document.getElementById('c-email').value = '';
            document.getElementById('c-username').value = '';
            document.getElementById('c-phone').value = '';
            document.getElementById('c-id-number').value = '';
            document.getElementById('c-emergency-contact').value = '';
            document.getElementById('c-emergency-phone').value = '';
            document.getElementById('c-skills').value = '';
            document.getElementById('c-property').value = '';
        }

        // Create Caretaker
        document.getElementById('create-caretaker-submit').addEventListener('click', async () => {
            clearError('create-caretaker-error');

            try {
                const skills = document.getElementById('c-skills').value;
                const skillsArray = skills ? skills.split(',').map(s => s.trim()).filter(s => s) : [];

                const res = await apiFetch('/api/landlord/caretakers', {
                    method: 'POST',
                    body: JSON.stringify({
                        full_name: document.getElementById('c-name').value,
                        email: document.getElementById('c-email').value,
                        username: document.getElementById('c-username').value,
                        phone: document.getElementById('c-phone').value || null,
                        id_number: document.getElementById('c-id-number').value || null,
                        emergency_contact: document.getElementById('c-emergency-contact')
                            .value || null,
                        emergency_phone: document.getElementById('c-emergency-phone').value ||
                            null,
                        skills: skillsArray,
                        property_id: document.getElementById('c-property').value || null,
                    }),
                });

                const createModalEl = document.getElementById('createCaretakerModal');
                showCredentialsAfterClose(createModalEl, res.password);
                bootstrap.Modal.getInstance(createModalEl).hide();

                resetCreateForm();
                loadCaretakers();
            } catch (e) {
                showError('create-caretaker-error', e);
            }
        });

        // Copy password to clipboard
        document.getElementById('credentials-copy').addEventListener('click', async () => {
            const pw = document.getElementById('credentials-password').textContent;
            const btn = document.getElementById('credentials-copy');
            try {
                await navigator.clipboard.writeText(pw);
                const original = btn.innerHTML;
                btn.innerHTML = '<i class="fas fa-check"></i>';
                setTimeout(() => {
                    btn.innerHTML = original;
                }, 1500);
            } catch (e) {
                // Clipboard API unavailable — password is already visible for manual copy.
            }
        });

        // Assign button click handler (event delegation)
        caretakerBody.addEventListener('click', async (ev) => {
            const id = ev.target.getAttribute('data-assign');
            if (!id) return;

            document.getElementById('assign-caretaker-id').value = id;
            clearError('assign-error');

            try {
                await loadAssignProperties();
                new bootstrap.Modal(document.getElementById('assignModal')).show();
            } catch (e) {
                showError('caretaker-error', e);
            }
        });

        // Submit assignment
        document.getElementById('assign-submit').addEventListener('click', async () => {
            const id = document.getElementById('assign-caretaker-id').value;
            const propertyId = document.getElementById('assign-property').value;

            if (!propertyId) {
                showError('assign-error', {
                    message: 'Please select a property to assign.'
                });
                return;
            }

            try {
                await apiFetch('/api/landlord/caretakers/' + id + '/assign-property', {
                    method: 'PUT',
                    body: JSON.stringify({
                        property_id: propertyId,
                    }),
                });

                bootstrap.Modal.getInstance(document.getElementById('assignModal')).hide();
                loadCaretakers();
            } catch (e) {
                showError('assign-error', e);
            }
        });

        // Initialize
        loadCaretakers();
        loadProperties('c-property');
    </script>
@endpush
