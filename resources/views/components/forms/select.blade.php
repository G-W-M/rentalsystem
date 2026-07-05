@props([
    'name' => null,
    'id' => null,
    'label' => null,
    'options' => [],
    'selected' => null,
    'placeholder' => null,
    'required' => false,
    'disabled' => false,
    'multiple' => false,
    'error' => null,
    'class' => null,
])

@php
    $id = $id ?? ($name ?? Str::random(8));
    $selected = old($name, $selected);
    $hasError = $error || $errors?->has($name);
    $selectClass = $hasError ? 'is-invalid' : '';
    $selectClass .= $class ? ' ' . $class : '';
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

    <select name="{{ $name }}{{ $multiple ? '[]' : '' }}" id="{{ $id }}"
        class="form-select {{ $selectClass }}" {{ $required ? 'required' : '' }} {{ $disabled ? 'disabled' : '' }}
        {{ $multiple ? 'multiple' : '' }} {{ $attributes }}>

        @if ($placeholder && !$multiple)
            <option value="">{{ $placeholder }}</option>
        @endif

        @foreach ($options as $value => $label)
            @if (is_array($label) && isset($label['label']))
                <optgroup label="{{ $label['label'] ?? '' }}">
                    @foreach ($label['options'] ?? [] as $optValue => $optLabel)
                        <option value="{{ $optValue }}"
                            {{ (is_array($selected) && in_array($optValue, $selected)) || $selected == $optValue ? 'selected' : '' }}>
                            {{ $optLabel }}
                        </option>
                    @endforeach
                </optgroup>
            @else
                <option value="{{ $value }}"
                    {{ (is_array($selected) && in_array($value, $selected)) || $selected == $value ? 'selected' : '' }}>
                    {{ $label }}
                </option>
            @endif
        @endforeach
    </select>

    @if ($hasError && $name)
        <div class="invalid-feedback d-block">
            {{ $error ?? $errors->first($name) }}
        </div>
    @endif
</div>
