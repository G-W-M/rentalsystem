@props([
    'label' => null,
    'for' => null,
    'required' => false,
    'error' => null,
    'help' => null,
    'inline' => false,
    'class' => null,
])

<div class="form-group {{ $inline ? 'd-inline-block' : '' }} {{ $class }}">
    @if ($label)
        <label for="{{ $for }}" class="form-label {{ $required ? 'required' : '' }}">
            {{ $label }}
            @if ($required)
                <span class="text-danger required-asterisk">*</span>
            @endif
        </label>
    @endif

    {{ $slot }}

    @if ($help && !$error)
        <div class="form-text">{{ $help }}</div>
    @endif

    @if ($error)
        <div class="invalid-feedback d-block">{{ $error }}</div>
    @endif
</div>
