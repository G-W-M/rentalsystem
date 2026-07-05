@props([
    'title' => null,
    'subtitle' => null,
    'icon' => null,
    'iconColor' => 'primary',
    'actions' => null,
    'padding' => true,
    'headerClass' => null,
    'bodyClass' => null,
    'footer' => null,
])

<div class="card panel h-100">
    @if ($title || $subtitle || $icon || $actions)
        <div class="card-header d-flex align-items-center justify-content-between {{ $headerClass }}">
            <div class="d-flex align-items-center gap-3">
                @if ($icon)
                    <div class="icon-square-60 bg-{{ $iconColor }}-light text-{{ $iconColor }}">
                        <i class="fas {{ $icon }}"></i>
                    </div>
                @endif
                <div>
                    @if ($title)
                        <h5 class="card-title mb-0">{{ $title }}</h5>
                    @endif
                    @if ($subtitle)
                        <span class="text-muted small">{{ $subtitle }}</span>
                    @endif
                </div>
            </div>
            @if ($actions)
                <div class="card-actions">
                    {{ $actions }}
                </div>
            @endif
        </div>
    @endif

    <div class="card-body {{ $padding ? 'p-4' : 'p-0' }} {{ $bodyClass }}">
        {{ $slot }}
    </div>

    @if ($footer)
        <div class="card-footer bg-transparent border-top">
            {{ $footer }}
        </div>
    @endif
</div>
