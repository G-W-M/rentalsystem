@props([
    'type' => 'info',
    'dismissible' => true,
    'autoHide' => false,
    'delay' => 5000,
    'icon' => null,
    'class' => null,
])

@php
    $iconMap = [
        'success' => 'fa-check-circle',
        'error' => 'fa-exclamation-circle',
        'warning' => 'fa-exclamation-triangle',
        'info' => 'fa-info-circle',
    ];
    $icon = $icon ?? ($iconMap[$type] ?? 'fa-info-circle');
    $alertClass = match ($type) {
        'success' => 'alert-success',
        'error' => 'alert-danger',
        'warning' => 'alert-warning',
        default => 'alert-info',
    };
@endphp

<div class="alert {{ $alertClass }} {{ $dismissible ? 'alert-dismissible' : '' }} {{ $class }}" role="alert"
    {{ $autoHide ? 'data-delay="' . $delay . '"' : '' }}>
    @if ($icon)
        <i class="fas {{ $icon }} me-2"></i>
    @endif
    {{ $slot }}

    @if ($dismissible)
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    @endif
</div>

@push('scripts')
    <script>
        // Auto-hide alerts with data-delay attribute
        document.addEventListener('DOMContentLoaded', function() {
            document.querySelectorAll('.alert[data-delay]').forEach(function(alert) {
                const delay = parseInt(alert.dataset.delay) || 5000;
                setTimeout(function() {
                    const bsAlert = new bootstrap.Alert(alert);
                    bsAlert.close();
                }, delay);
            });
        });
    </script>
@endpush
