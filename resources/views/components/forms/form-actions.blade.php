@props([
    'cancelUrl' => null,
    'cancelText' => 'Cancel',
    'submitText' => 'Save',
    'cancelLabel' => null,
    'submitLabel' => null,
    'submitVariant' => 'primary',
    'align' => 'end',
    'reset' => false,
    'resetText' => 'Reset',
])

@php
    $resolvedCancelText = $cancelLabel ?? $cancelText;
    $resolvedSubmitText = $submitLabel ?? $submitText;
@endphp

<div class="form-actions d-flex flex-wrap gap-2 align-items-center justify-content-{{ $align }}">
    @if ($reset)
        <button type="reset" class="btn btn-outline-secondary">
            <i class="fas fa-undo me-1"></i> {{ $resetText }}
        </button>
    @endif

    @if ($cancelUrl)
        <a href="{{ $cancelUrl }}" class="btn btn-outline-secondary">
            <i class="fas fa-times me-1"></i> {{ $resolvedCancelText }}
        </a>
    @endif

    <button type="submit" class="btn btn-{{ $submitVariant }}">
        <i class="fas fa-save me-1"></i> {{ $resolvedSubmitText }}
    </button>

    {{ $slot }}
</div>
