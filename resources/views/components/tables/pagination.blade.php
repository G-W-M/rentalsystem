@props([
    'currentPage' => 1,
    'lastPage' => 1,
    'total' => 0,
    'totalPages' => null,
    'totalItems' => null,
    'perPage' => 15,
    'url' => null,
    'showInfo' => true,
])

@php
    $currentPage = (int) $currentPage;
    $resolvedTotal = (int) ($totalItems ?? ($total ?? 0));
    $resolvedLastPage = (int) ($totalPages ?? ($lastPage ?? 1));
@endphp

<div class="pagination-container d-flex align-items-center justify-content-between flex-wrap gap-3">
    <div class="pagination-info text-muted small">
        @if ($showInfo)
            Showing
            @if ($resolvedTotal > 0)
                {{ ($currentPage - 1) * $perPage + 1 }}
                to
                {{ min($currentPage * $perPage, $resolvedTotal) }}
            @else
                0
            @endif
            of {{ $resolvedTotal }} entries
        @endif
    </div>

    <nav aria-label="Page navigation">
        <ul class="pagination pagination-sm mb-0">
            <!-- First -->
            <li class="page-item {{ $currentPage <= 1 ? 'disabled' : '' }}">
                <a class="page-link" href="{{ $url ? $url . '?page=1' : '?page=1' }}" aria-label="First">
                    <i class="fas fa-angle-double-left"></i>
                </a>
            </li>

            <!-- Previous -->
            <li class="page-item {{ $currentPage <= 1 ? 'disabled' : '' }}">
                <a class="page-link"
                    href="{{ $url ? $url . '?page=' . ($currentPage - 1) : '?page=' . ($currentPage - 1) }}"
                    aria-label="Previous">
                    <i class="fas fa-angle-left"></i>
                </a>
            </li>

            <!-- Pages -->
            @php
                $start = max(1, $currentPage - 2);
                $end = min($resolvedLastPage, $currentPage + 2);

                if ($end - $start < 4) {
                    if ($start > 1) {
                        $start = max(1, $end - 4);
                    }
                    if ($end < $resolvedLastPage) {
                        $end = min($resolvedLastPage, $start + 4);
                    }
                }
            @endphp

            @if ($start > 1)
                <li class="page-item disabled"><span class="page-link">...</span></li>
            @endif

            @for ($i = $start; $i <= $end; $i++)
                <li class="page-item {{ $i == $currentPage ? 'active' : '' }}">
                    <a class="page-link" href="{{ $url ? $url . '?page=' . $i : '?page=' . $i }}">
                        {{ $i }}
                    </a>
                </li>
            @endfor

            @if ($end < $resolvedLastPage)
                <li class="page-item disabled"><span class="page-link">...</span></li>
            @endif

            <!-- Next -->
            <li class="page-item {{ $currentPage >= $resolvedLastPage ? 'disabled' : '' }}">
                <a class="page-link"
                    href="{{ $url ? $url . '?page=' . ($currentPage + 1) : '?page=' . ($currentPage + 1) }}"
                    aria-label="Next">
                    <i class="fas fa-angle-right"></i>
                </a>
            </li>

            <!-- Last -->
            <li class="page-item {{ $currentPage >= $resolvedLastPage ? 'disabled' : '' }}">
                <a class="page-link"
                    href="{{ $url ? $url . '?page=' . $resolvedLastPage : '?page=' . $resolvedLastPage }}"
                    aria-label="Last">
                    <i class="fas fa-angle-double-right"></i>
                </a>
            </li>
        </ul>
    </nav>
</div>
