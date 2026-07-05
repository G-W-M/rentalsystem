@props([
    'headers' => [],
    'columns' => [],
    'rows' => [],
    'actions' => false,
    'searchPlaceholder' => 'Search...',
    'id' => null,
    'class' => null,
    'responsive' => true,
    'hover' => true,
    'striped' => false,
    'bordered' => false,
    'emptyMessage' => 'No data found.',
    'loading' => false,
])

@php
    $resolvedHeaders = $headers;

    if (empty($resolvedHeaders) && !empty($columns)) {
        $resolvedHeaders = collect($columns)
            ->map(function ($column) {
                if (is_array($column)) {
                    return [
                        'key' => $column['key'] ?? null,
                        'label' => $column['label'] ?? ($column['key'] ?? ''),
                    ];
                }

                return [
                    'key' => null,
                    'label' => $column,
                ];
            })
            ->values()
            ->all();
    }

    $renderActionsColumn = (bool) $actions;
@endphp

<div class="table-container {{ $class }}">
    <div class="table-responsive {{ $responsive ? '' : 'overflow-visible' }}">
        <table
            class="table {{ $hover ? 'table-hover' : '' }} {{ $striped ? 'table-striped' : '' }} {{ $bordered ? 'table-bordered' : '' }} mb-0"
            id="{{ $id }}" style="min-width: 600px;">
            <thead>
                <tr>
                    @foreach ($resolvedHeaders as $header)
                        <th {{ $header['width'] ?? false ? 'style="width: ' . $header['width'] . ';"' : '' }}>
                            @if ($header['sortable'] ?? false)
                                <a href="#"
                                    class="text-decoration-none text-dark d-flex align-items-center gap-1 sort-link"
                                    data-sort="{{ $header['key'] ?? '' }}">
                                    {{ $header['label'] }}
                                    <i class="fas fa-sort text-muted small"></i>
                                </a>
                            @else
                                {{ $header['label'] }}
                            @endif
                        </th>
                    @endforeach
                    @if ($renderActionsColumn)
                        <th class="text-end">Actions</th>
                    @endif
                </tr>
            </thead>
            <tbody>
                @if (count($rows) > 0)
                    @foreach ($rows as $row)
                        <tr class="{{ $loading ? 'opacity-50' : '' }}">
                            @foreach ($resolvedHeaders as $key => $header)
                                <td>
                                    @if (isset($row['actions']) && (($header['key'] ?? null) === 'actions' || $key === 'actions'))
                                        <div class="table-actions d-flex gap-1">
                                            @foreach ($row['actions'] as $action)
                                                @if ($action['type'] === 'button')
                                                    <button
                                                        class="btn btn-{{ $action['variant'] ?? 'outline-primary' }} btn-sm"
                                                        onclick="{{ $action['onclick'] ?? '' }}"
                                                        data-bs-toggle="tooltip"
                                                        title="{{ $action['tooltip'] ?? '' }}">
                                                        <i class="fas {{ $action['icon'] ?? '' }}"></i>
                                                        @if ($action['label'] ?? false)
                                                            <span
                                                                class="d-none d-md-inline ms-1">{{ $action['label'] }}</span>
                                                        @endif
                                                    </button>
                                                @else
                                                    <a href="{{ $action['href'] ?? '#' }}"
                                                        class="btn btn-{{ $action['variant'] ?? 'outline-primary' }} btn-sm"
                                                        data-bs-toggle="tooltip"
                                                        title="{{ $action['tooltip'] ?? '' }}">
                                                        <i class="fas {{ $action['icon'] ?? '' }}"></i>
                                                        @if ($action['label'] ?? false)
                                                            <span
                                                                class="d-none d-md-inline ms-1">{{ $action['label'] }}</span>
                                                        @endif
                                                    </a>
                                                @endif
                                            @endforeach
                                        </div>
                                    @else
                                        @php
                                            $rowValue = '';

                                            if (is_array($row)) {
                                                $columnKey = $header['key'] ?? null;

                                                if ($columnKey !== null && array_key_exists($columnKey, $row)) {
                                                    $rowValue = $row[$columnKey];
                                                } elseif (array_key_exists($key, $row)) {
                                                    $rowValue = $row[$key];
                                                }
                                            }
                                        @endphp
                                        {!! $rowValue !!}
                                    @endif
                                </td>
                            @endforeach
                            @if ($renderActionsColumn)
                                <td class="text-end">
                                    @if (isset($row['actions']) && is_array($row['actions']))
                                        <div class="d-flex justify-content-end gap-1">
                                            @foreach ($row['actions'] as $action)
                                                @if (($action['type'] ?? 'button') === 'button')
                                                    <button
                                                        class="btn btn-{{ $action['variant'] ?? 'outline-primary' }} btn-sm"
                                                        onclick="{{ $action['onclick'] ?? '' }}"
                                                        title="{{ $action['tooltip'] ?? '' }}">
                                                        <i class="fas {{ $action['icon'] ?? '' }}"></i>
                                                    </button>
                                                @else
                                                    <a href="{{ $action['href'] ?? '#' }}"
                                                        class="btn btn-{{ $action['variant'] ?? 'outline-primary' }} btn-sm"
                                                        title="{{ $action['tooltip'] ?? '' }}">
                                                        <i class="fas {{ $action['icon'] ?? '' }}"></i>
                                                    </a>
                                                @endif
                                            @endforeach
                                        </div>
                                    @else
                                        <button class="btn btn-sm btn-outline-primary" type="button"
                                            aria-label="View row">
                                            <i class="fas fa-eye" aria-hidden="true"></i>
                                        </button>
                                    @endif
                                </td>
                            @endif
                        </tr>
                    @endforeach
                @else
                    <tr>
                        <td colspan="{{ count($resolvedHeaders) + ($renderActionsColumn ? 1 : 0) }}"
                            class="text-center py-5 text-muted">
                            @if ($loading)
                                <div class="d-flex align-items-center justify-content-center gap-2">
                                    <div class="spinner-border spinner-border-sm" role="status">
                                        <span class="visually-hidden">Loading...</span>
                                    </div>
                                    <span>Loading data...</span>
                                </div>
                            @else
                                <i class="fas fa-inbox fs-2 d-block mb-2 opacity-25"></i>
                                {{ $emptyMessage }}
                            @endif
                        </td>
                    </tr>
                @endif
            </tbody>
        </table>
    </div>
</div>

@push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            document.querySelectorAll('.sort-link').forEach(link => {
                link.addEventListener('click', function(e) {
                    e.preventDefault();
                    const key = this.dataset.sort;
                    const icon = this.querySelector('.fa-sort');
                    const isAsc = this.dataset.asc === 'true';

                    this.dataset.asc = !isAsc;
                    icon.className = `fas fa-sort-${isAsc ? 'up' : 'down'} text-primary`;

                    this.closest('table').dispatchEvent(new CustomEvent('sort', {
                        detail: {
                            key,
                            asc: isAsc
                        }
                    }));
                });
            });
        });
    </script>
@endpush
