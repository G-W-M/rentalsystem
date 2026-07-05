@props([
    'filters' => [],
    'activeFilters' => [],
    'onApply' => null,
    'onReset' => null,
])

<div class="table-toolbar bg-light rounded-top p-3">
    <div class="row g-2 align-items-end">
        <!-- Search -->
        <div class="col-md-4">
            <div class="search-box">
                <div class="input-group">
                    <span class="input-group-text bg-white border-end-0">
                        <i class="fas fa-search text-muted"></i>
                    </span>
                    <input type="text" class="form-control border-start-0" placeholder="Search..."
                        value="{{ $activeFilters['search'] ?? '' }}" data-filter="search">
                </div>
            </div>
        </div>

        <!-- Dynamic Filters -->
        @foreach ($filters as $filter)
            <div class="col-md-2 col-6">
                @if ($filter['type'] === 'select')
                    <select class="form-select form-select-sm" data-filter="{{ $filter['key'] }}">
                        <option value="">{{ $filter['label'] ?? 'All' }}</option>
                        @foreach ($filter['options'] as $value => $label)
                            <option value="{{ $value }}"
                                {{ ($activeFilters[$filter['key']] ?? '') == $value ? 'selected' : '' }}>
                                {{ $label }}
                            </option>
                        @endforeach
                    </select>
                @elseif($filter['type'] === 'date')
                    <input type="date" class="form-control form-control-sm" data-filter="{{ $filter['key'] }}"
                        value="{{ $activeFilters[$filter['key']] ?? '' }}"
                        placeholder="{{ $filter['label'] ?? 'Date' }}">
                @elseif($filter['type'] === 'daterange')
                    <input type="text" class="form-control form-control-sm date-range"
                        data-filter="{{ $filter['key'] }}" value="{{ $activeFilters[$filter['key']] ?? '' }}"
                        placeholder="{{ $filter['label'] ?? 'Date Range' }}">
                @endif
            </div>
        @endforeach

        <!-- Actions -->
        <div class="col-md-auto ms-auto">
            <div class="d-flex gap-2">
                @if ($onReset)
                    <button class="btn btn-outline-secondary btn-sm" onclick="{{ $onReset }}">
                        <i class="fas fa-undo me-1"></i> Reset
                    </button>
                @endif
                @if ($onApply)
                    <button class="btn btn-primary btn-sm" onclick="{{ $onApply }}">
                        <i class="fas fa-filter me-1"></i> Apply
                    </button>
                @endif
            </div>
        </div>
    </div>
</div>
