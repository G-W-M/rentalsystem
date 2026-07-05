@props([
    'placeholder' => 'Search...',
    'name' => 'search',
    'value' => null,
    'id' => null,
    'class' => null,
    'action' => null,
    'method' => 'GET',
    'autocomplete' => 'off',
    'filters' => [],
    'submitOnChange' => false,
])

@php
    $id = $id ?? 'search-' . Str::random(8);
    $value = old($name, $value ?? request($name));
    $hasFilters = count($filters) > 0;
@endphp

<div class="search-component {{ $class }}">
    <form action="{{ $action ?? url()->current() }}" method="{{ $method }}"
        class="d-flex flex-wrap gap-2 align-items-end" id="{{ $id }}-form">
        <!-- Search Input -->
        <div class="search-box flex-grow-1" style="min-width: 200px;">
            <div class="input-group">
                <span class="input-group-text bg-white border-end-0">
                    <i class="fas fa-search text-muted"></i>
                </span>
                <input type="text" name="{{ $name }}" class="form-control border-start-0"
                    placeholder="{{ $placeholder }}" value="{{ $value }}" autocomplete="{{ $autocomplete }}"
                    {{ $submitOnChange ? 'oninput="this.form.submit()"' : '' }}>
            </div>
        </div>

        <!-- Optional Filters -->
        @if ($hasFilters)
            @foreach ($filters as $filter)
                <div class="filter-item" style="min-width: 150px;">
                    @if ($filter['type'] === 'select')
                        <select name="{{ $filter['name'] ?? $filter['key'] }}" class="form-select form-select-sm"
                            {{ $submitOnChange ? 'onchange="this.form.submit()"' : '' }}>
                            <option value="">{{ $filter['label'] ?? 'All' }}</option>
                            @foreach ($filter['options'] as $value => $label)
                                <option value="{{ $value }}"
                                    {{ request($filter['name'] ?? $filter['key']) == $value ? 'selected' : '' }}>
                                    {{ $label }}
                                </option>
                            @endforeach
                        </select>
                    @elseif($filter['type'] === 'date')
                        <input type="date" name="{{ $filter['name'] ?? $filter['key'] }}"
                            class="form-control form-control-sm"
                            value="{{ request($filter['name'] ?? $filter['key']) }}"
                            {{ $submitOnChange ? 'onchange="this.form.submit()"' : '' }}>
                    @endif
                </div>
            @endforeach
        @endif

        <!-- Submit Button -->
        @if (!$submitOnChange)
            <button type="submit" class="btn btn-primary btn-sm">
                <i class="fas fa-search me-1"></i> Search
            </button>
        @endif

        <!-- Reset Link -->
        @if ($value || request()->anyFilled(array_column($filters, 'key')))
            <a href="{{ $action ?? url()->current() }}" class="btn btn-outline-secondary btn-sm">
                <i class="fas fa-undo me-1"></i> Reset
            </a>
        @endif
    </form>
</div>

@push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const form = document.getElementById('{{ $id }}-form');
            if (!form) return;

            @if (!$submitOnChange)
                form.querySelectorAll('select, input[type="date"]').forEach(el => {
                    el.addEventListener('change', function() {
                        form.submit();
                    });
                });
            @endif
        });
    </script>
@endpush
