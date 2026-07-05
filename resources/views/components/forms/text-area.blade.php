@props([
    'name' => null,
    'id' => null,
    'label' => null,
    'value' => null,
    'placeholder' => null,
    'rows' => 3,
    'required' => false,
    'disabled' => false,
    'readonly' => false,
    'error' => null,
    'class' => null,
])

@php
    $id = $id ?? ($name ?? Str::random(8));
    $value = old($name, $value);
    $hasError = $error || $errors?->has($name);
    $inputClass = $hasError ? 'is-invalid' : '';
    $inputClass .= $class ? ' ' . $class : '';
@endphp

<div class="form-group">
    @if ($label)
        <label for="{{ $id }}" class="form-label">
            {{ $label }}
            @if ($required)
                <span class="text-danger">*</span>
            @endif
        </label>
    @endif

    <textarea name="{{ $name }}" id="{{ $id }}" class="form-control {{ $inputClass }}"
        placeholder="{{ $placeholder }}" rows="{{ $rows }}" {{ $required ? 'required' : '' }}
        {{ $disabled ? 'disabled' : '' }} {{ $readonly ? 'readonly' : '' }} {{ $attributes }}>{{ $value }}</textarea>

    @if ($hasError && $name)
        <div class="invalid-feedback d-block">
            {{ $error ?? $errors->first($name) }}
        </div>
    @endif
</div>
