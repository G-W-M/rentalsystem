@props([
    'request' => null,
    'description' => null,
    'title' => null,
    'property' => null,
    'unit' => null,
    'tenant' => null,
    'status' => 'pending',
    'priority' => 'medium',
    'date' => null,
    'is_major' => false,
    'onApprove' => null,
    'onReject' => null,
])

@php
    $priorityColors = [
        'low' => 'success',
        'medium' => 'warning',
        'high' => 'danger',
        'emergency' => 'danger',
    ];
    $priorityColor = $priorityColors[$priority] ?? 'secondary';
    $resolvedDescription = $description ?? ($title ?? ($request['description'] ?? 'No description'));
@endphp

<div class="card maintenance-card border-0 shadow-sm hover-lift transition-all">
    <div class="card-body">
        <div class="d-flex align-items-start gap-3">
            <div class="maintenance-icon rounded-2 p-3 bg-{{ $is_major ? 'danger' : 'info' }}-light flex-shrink-0">
                <i
                    class="fas fa-{{ $is_major ? 'exclamation-triangle' : 'wrench' }}
                      text-{{ $is_major ? 'danger' : 'info' }} fs-4">
                </i>
            </div>
            <div class="flex-grow-1 min-w-0">
                <div class="d-flex align-items-start justify-content-between">
                    <div>
                        <h6 class="mb-1 fw-semibold">{{ Str::limit($resolvedDescription, 100) }}</h6>
                        @if ($property)
                            <div class="text-muted small">
                                <i class="fas fa-city me-1"></i> {{ $property }}
                            </div>
                        @endif
                        @if ($unit)
                            <div class="text-muted small">
                                <i class="fas fa-building me-1"></i> {{ $unit }}
                            </div>
                        @endif
                        @if ($tenant)
                            <div class="text-muted small">
                                <i class="fas fa-user me-1"></i> {{ $tenant }}
                            </div>
                        @endif
                    </div>
                    <div class="text-end flex-shrink-0 ms-3">
                        <span class="badge-status {{ $status }}">
                            {{ ucfirst(str_replace('_', ' ', $status)) }}
                        </span>
                        @if ($is_major)
                            <span class="badge bg-danger ms-1">Major</span>
                        @endif
                        <span class="badge bg-{{ $priorityColor }} ms-1">
                            {{ ucfirst($priority) }}
                        </span>
                        @if ($date)
                            <div class="text-muted small mt-1">{{ \Carbon\Carbon::parse($date)->diffForHumans() }}
                            </div>
                        @endif
                    </div>
                </div>

                @if ($status === 'pending' && ($onApprove || $onReject))
                    <div class="mt-3 pt-2 border-top d-flex gap-2 justify-content-end">
                        @if ($onReject)
                            <button class="btn btn-outline-danger btn-sm" onclick="{{ $onReject }}">
                                <i class="fas fa-times me-1"></i> Reject
                            </button>
                        @endif
                        @if ($onApprove)
                            <button class="btn btn-primary btn-sm" onclick="{{ $onApprove }}">
                                <i class="fas fa-check me-1"></i> Approve
                            </button>
                        @endif
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>
