@props([
    'id' => 'paymentModal',
    'title' => 'New Payment',
    'tenantOptions' => [],
    'unitOptions' => [],
    'methodOptions' => ['mpesa' => 'M-Pesa', 'bank' => 'Bank Transfer', 'cash' => 'Cash'],
    'selectedTenant' => null,
    'selectedUnit' => null,
    'selectedMethod' => null,
    'amount' => null,
])

<div class="modal fade" id="{{ $id }}" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">{{ $title }}</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="{{ $id }}-form" method="POST" action="{{ route('payments.store') }}">
                @csrf
                <div class="modal-body">
                    <div class="form-group mb-3">
                        <label for="tenant_id" class="form-label">Tenant</label>
                        <select name="tenant_id" id="tenant_id" class="form-select" required>
                            <option value="">Select Tenant</option>
                            @foreach ($tenantOptions as $value => $label)
                                <option value="{{ $value }}" {{ $selectedTenant == $value ? 'selected' : '' }}>
                                    {{ $label }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="form-group mb-3">
                        <label for="unit_id" class="form-label">Unit</label>
                        <select name="unit_id" id="unit_id" class="form-select" required>
                            <option value="">Select Unit</option>
                            @foreach ($unitOptions as $value => $label)
                                <option value="{{ $value }}" {{ $selectedUnit == $value ? 'selected' : '' }}>
                                    {{ $label }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="form-group mb-3">
                        <label for="amount" class="form-label">Amount (KES)</label>
                        <input type="number" name="amount" id="amount" class="form-control"
                            value="{{ $amount }}" min="1" step="0.01" required>
                    </div>

                    <div class="form-group mb-3">
                        <label for="method" class="form-label">Payment Method</label>
                        <select name="method" id="method" class="form-select" required>
                            <option value="">Select Method</option>
                            @foreach ($methodOptions as $value => $label)
                                <option value="{{ $value }}" {{ $selectedMethod == $value ? 'selected' : '' }}>
                                    {{ $label }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="form-group mb-3">
                        <label for="payment_date" class="form-label">Payment Date</label>
                        <input type="date" name="payment_date" id="payment_date" class="form-control"
                            value="{{ date('Y-m-d') }}" required>
                    </div>

                    <div class="form-group mb-3" id="reference-group" style="display: none;">
                        <label for="reference" class="form-label">M-Pesa Reference</label>
                        <input type="text" name="reference" id="reference" class="form-control"
                            placeholder="Enter M-Pesa reference code">
                    </div>

                    <div class="form-group mb-3" id="phone-group" style="display: none;">
                        <label for="phone_number" class="form-label">Phone Number</label>
                        <input type="tel" name="phone_number" id="phone_number" class="form-control"
                            placeholder="e.g., 0712345678">
                    </div>

                    <div class="form-group mb-3">
                        <label for="notes" class="form-label">Notes (Optional)</label>
                        <textarea name="notes" id="notes" class="form-control" rows="2" placeholder="Additional notes..."></textarea>
                    </div>
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                        <i class="fas fa-times me-1"></i> Cancel
                    </button>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-check me-1"></i> Submit Payment
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

@push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const methodSelect = document.getElementById('method');
            const referenceGroup = document.getElementById('reference-group');
            const phoneGroup = document.getElementById('phone-group');

            methodSelect?.addEventListener('change', function() {
                if (this.value === 'mpesa') {
                    referenceGroup.style.display = 'block';
                    phoneGroup.style.display = 'block';
                } else {
                    referenceGroup.style.display = 'none';
                    phoneGroup.style.display = 'none';
                }
            });

            if (methodSelect?.value === 'mpesa') {
                referenceGroup.style.display = 'block';
                phoneGroup.style.display = 'block';
            }
        });
    </script>
@endpush
