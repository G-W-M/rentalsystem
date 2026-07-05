@props([
    'title' => null,
    'label' => null,
    'value' => null,
    'number' => null,
    'icon' => null,
    'color' => 'primary',
    'iconClass' => null,
    'trend' => null,
    'trendValue' => null,
    'subtitle' => null,
    'loading' => false,
    'currency' => false,
])

@php
    $resolvedTitle = $title ?? ($label ?? '');
    $resolvedValue = $value ?? ($number ?? 0);
    $resolvedColor = $iconClass ?? $color;
@endphp

<div class="card metric border-0 h-100">
    <div class="card-body d-flex align-items-start gap-3">
        @if ($icon)
            <div class="icon bg-{{ $resolvedColor }}-light text-{{ $resolvedColor }} flex-shrink-0">
                <i class="fas {{ $icon }}"></i>
            </div>
        @endif

        <div class="flex-grow-1 min-w-0">
            <div class="label text-muted text-uppercase small fw-medium">
                {{ $resolvedTitle }}
            </div>
            <div class="value fs-3 fw-bold {{ $loading ? 'opacity-50' : '' }}">
                @if (is_numeric($resolvedValue))
                    @if ($currency)
                        KES {{ number_format((float) $resolvedValue, 2) }}
                    @else
                        {{ number_format((float) $resolvedValue) }}
                    @endif
                @else
                    {{ $resolvedValue }}
                @endif
            </div>

            @if ($subtitle)
                <div class="text-muted small mt-1">{{ $subtitle }}</div>
            @endif

            @if ($trend && $trendValue !== null)
                <div class="mt-2 d-flex align-items-center gap-2">
                    <span class="badge {{ $trend === 'up' ? 'bg-success' : 'bg-danger' }} rounded-pill">
                        <i class="fas fa-arrow-{{ $trend === 'up' ? 'up' : 'down' }} me-1"></i>
                        {{ $trendValue }}%
                    </span>
                    <span class="text-muted small">vs last month</span>
                </div>
            @endif
        </div>
    </div>
</div>
