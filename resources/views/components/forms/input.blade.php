@props(['name', 'label' => null, 'type' => 'text', 'value' => null, 'required' => false, 'placeholder' => null])

<div class="form-group">
    @if ($label)
        <label for="{{ $name }}" class="form-label">
            {{ $label }}
            @if ($required)
                <span class="required">*</span>
            @endif
        </label>
    @endif

    <input id="{{ $name }}" name="{{ $name }}" type="{{ $type }}" value="{{ old($name, $value) }}"
        placeholder="{{ $placeholder }}" @required($required)
        {{ $attributes->class(['form-control', 'is-invalid' => $errors->has($name)]) }}>

    @error($name)
        <div class="invalid-feedback">{{ $message }}</div>
    @enderror
</div>
