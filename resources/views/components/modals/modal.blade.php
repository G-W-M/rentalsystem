@props([
    'id' => null,
    'title' => null,
    'size' => 'lg',
    'centered' => true,
    'scrollable' => false,
    'static' => false,
    'footer' => null,
    'submitText' => 'Save',
    'submitVariant' => 'primary',
    'cancelText' => 'Cancel',
    'showCancel' => true,
    'showSubmit' => true,
    'onSubmit' => null,
    'onCancel' => null,
])

@php
    $id = $id ?? 'modal-' . Str::random(8);
    $sizeClass = match ($size) {
        'sm' => 'modal-sm',
        'lg' => 'modal-lg',
        'xl' => 'modal-xl',
        default => '',
    };
@endphp

<div class="modal fade" id="{{ $id }}" tabindex="-1" aria-hidden="true"
    {{ $static ? 'data-bs-backdrop="static" data-bs-keyboard="false"' : '' }}>
    <div
        class="modal-dialog {{ $sizeClass }} {{ $centered ? 'modal-dialog-centered' : '' }} {{ $scrollable ? 'modal-dialog-scrollable' : '' }}">
        <div class="modal-content">
            @if ($title)
                <div class="modal-header">
                    <h5 class="modal-title">{{ $title }}</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
            @endif

            <div class="modal-body">
                {{ $slot }}
            </div>

            @if ($footer || $showSubmit || $showCancel)
                <div class="modal-footer">
                    @if ($footer)
                        {{ $footer }}
                    @else
                        @if ($showCancel)
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal"
                                onclick="{{ $onCancel ?? '' }}">
                                <i class="fas fa-times me-1"></i> {{ $cancelText }}
                            </button>
                        @endif
                        @if ($showSubmit)
                            <button type="button" class="btn btn-{{ $submitVariant }}"
                                onclick="{{ $onSubmit ?? '' }}">
                                <i class="fas fa-check me-1"></i> {{ $submitText }}
                            </button>
                        @endif
                    @endif
                </div>
            @endif
        </div>
    </div>
</div>
