@props([
    'payment' => null,
    'tenant' => null,
    'unit' => null,
    'amount' => null,
    'status' => 'pending',
    'date' => null,
    'method' => null,
    'reference' => null,
    'onVerify' => null,
    'onReject' => null,
])

<div class="card payment-card border-0 shadow-sm hover-lift transition-all">
    <div class="card-body">
        <div class="d-flex align-items-start justify-content-between">
            <div class="d-flex align-items-center gap-3">
                <div
                    class="payment-icon rounded-2 p-3 bg-{{ $status === 'verified' ? 'success' : ($status === 'pending' ? 'warning' : 'danger') }}-light">
                    <i
                        class="fas fa-{{ $status === 'verified' ? 'check-circle' : ($status === 'pending' ? 'clock' : 'times-circle') }}
                          text-{{ $status === 'verified' ? 'success' : ($status === 'pending' ? 'warning' : 'danger') }} fs-4">
                    </i>
                </div>
                <div>
                    <div class="fw-bold text-lg">KES {{ number_format($amount ?? 0, 2) }}</div>
                    @if ($tenant)
                        <div class="text-muted small">{{ $tenant }}</div>
                    @endif
                    @if ($unit)
                        <div class="text-muted small">{{ $unit }}</div>
                    @endif
                </div>
            </div>
            <div class="text-end">
                <span class="badge-status {{ $status }}">
                    {{ ucfirst($status) }}
                </span>
                @if ($date)
                    <div class="text-muted small mt-1">{{ \Carbon\Carbon::parse($date)->format('M d, Y') }}</div>
                @endif
                @if ($method)
                    <div class="text-muted small">
                        <i
                            class="fas fa-{{ $method === 'mpesa' ? 'mobile-alt' : ($method === 'bank' ? 'university' : 'money-bill') }} me-1"></i>
                        {{ ucfirst($method) }}
                    </div>
                @endif
            </div>
        </div>

        @if ($reference)
            <div class="mt-2 pt-2 border-top">
                <div class="d-flex justify-content-between small">
                    <span class="text-muted">Reference:</span>
                    <span class="fw-medium">{{ $reference }}</span>
                </div>
            </div>
        @endif

        @if ($status === 'pending' && ($onVerify || $onReject))
            <div class="mt-3 pt-2 border-top d-flex gap-2 justify-content-end">
                @if ($onReject)
                    <button class="btn btn-outline-danger btn-sm" onclick="{{ $onReject }}">
                        <i class="fas fa-times me-1"></i> Reject
                    </button>
                @endif
                @if ($onVerify)
                    <button class="btn btn-success btn-sm" onclick="{{ $onVerify }}">
                        <i class="fas fa-check me-1"></i> Verify
                    </button>
                @endif
            </div>
        @endif
    </div>
</div>
