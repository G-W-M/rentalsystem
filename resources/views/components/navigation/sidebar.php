@props([
    'brand' => null,
    'logo' => null,
    'logoAlt' => 'Logo',
    'brandUrl' => '/',
    'navItems' => [],
    'collapsed' => false,
    'user' => null,
    'userRole' => null,
])

@php
    $navItems =
        $navItems instanceof \Illuminate\Support\Collection ? $navItems->all() : (is_array($navItems) ? $navItems : []);

    $userName = data_get($user, 'name', 'User');
    $userEmail = data_get($user, 'email', '');
    $userStatus = data_get($user, 'status');
@endphp

<aside class="sidebar {{ $collapsed ? 'collapsed' : '' }}" id="mainSidebar">
    <!-- Brand -->
    <div class="sidebar-brand">
        @if ($logo)
            <div class="sidebar-logo-circle">
                <img src="{{ $logo }}" alt="{{ $logoAlt }}" loading="lazy">
            </div>
        @endif
        <div>
            <div class="brand-label text-white-50 small text-uppercase fw-semibold">
                {{ $brand ?? config('app.name') }}
            </div>
            <div class="brand-text">
                {{ $userRole ?? 'Dashboard' }}
            </div>
        </div>
    </div>

    <!-- Navigation -->
    <nav class="nav flex-column">
        @foreach ($navItems as $item)
            @php
                $itemHeader = data_get($item, 'header');
                $itemDivider = (bool) data_get($item, 'divider', false);
                $itemSubmenu = data_get($item, 'submenu', []);
                $itemActive = (bool) data_get($item, 'active', false);
                $itemIcon = data_get($item, 'icon');
                $itemLabel = data_get($item, 'label', '');
                $itemRoute = data_get($item, 'route', '');
                $itemUrl = data_get($item, 'url', '#');
                $itemBadge = data_get($item, 'badge');
                $itemBadgeColor = data_get($item, 'badgeColor', 'primary');
                $itemSubmenu =
                    $itemSubmenu instanceof \Illuminate\Support\Collection
                        ? $itemSubmenu->all()
                        : (is_array($itemSubmenu)
                            ? $itemSubmenu
                            : []);
            @endphp
            @if ($itemHeader !== null)
                <div class="sidebar-header text-uppercase text-white-50 small px-3 mt-3 mb-2">
                    {{ $itemHeader }}
                </div>
            @elseif($itemDivider)
                <hr class="sidebar-divider my-2" style="border-color: rgba(255,255,255,0.1);">
            @else
                @if (count($itemSubmenu) > 0)
                    <div class="has-submenu {{ $itemActive ? 'active' : '' }}">
                        <a class="nav-link {{ $itemActive ? 'active' : '' }}" href="#" data-bs-toggle="collapse"
                            data-bs-target="#submenu-{{ $loop->index }}"
                            aria-expanded="{{ $itemActive ? 'true' : 'false' }}">
                            @if ($itemIcon)
                                <i class="fas {{ $itemIcon }} me-2"></i>
                            @endif
                            <span>{{ $itemLabel }}</span>
                            <i class="fas fa-chevron-down ms-auto submenu-arrow"></i>
                        </a>
                        <div class="submenu collapse {{ $itemActive ? 'show' : '' }}"
                            id="submenu-{{ $loop->index }}">
                            @foreach ($itemSubmenu as $subitem)
                                @php
                                    $subitemRoute = data_get($subitem, 'route', '');
                                    $subitemUrl = data_get($subitem, 'url', '#');
                                    $subitemIcon = data_get($subitem, 'icon');
                                    $subitemLabel = data_get($subitem, 'label', '');
                                @endphp
                                <a class="nav-link subnav-link {{ Route::currentRouteName() == $subitemRoute ? 'active' : '' }}"
                                    href="{{ $subitemUrl }}">
                                    @if ($subitemIcon)
                                        <i class="fas {{ $subitemIcon }} me-2"></i>
                                    @endif
                                    {{ $subitemLabel }}
                                </a>
                            @endforeach
                        </div>
                    </div>
                @else
                    <a class="nav-link {{ Route::currentRouteName() == $itemRoute ? 'active' : '' }}"
                        href="{{ $itemUrl }}">
                        @if ($itemIcon)
                            <i class="fas {{ $itemIcon }} me-2"></i>
                        @endif
                        <span>{{ $itemLabel }}</span>
                        @if ($itemBadge)
                            <span class="badge bg-{{ $itemBadgeColor }} ms-auto">
                                {{ $itemBadge }}
                            </span>
                        @endif
                    </a>
                @endif
            @endif
        @endforeach
    </nav>

    <!-- Sidebar Footer -->
    <div class="sidebar-footer mt-auto pt-3 border-top" style="border-color: rgba(255,255,255,0.1);">
        @if ($user)
            <div class="small text-white-50 mb-1">Signed in as</div>
            <div class="fw-semibold text-white">{{ $userName }}</div>
            <div class="small text-white-50">{{ $userEmail }}</div>
            @if ($userStatus)
                <div class="mt-2">
                    <span class="badge bg-{{ $userStatus === 'verified' ? 'success' : 'warning' }}">
                        {{ ucfirst($userStatus) }}
                    </span>
                </div>
            @endif
        @endif
        <a href="{{ route('logout') }}" class="btn btn-outline-light btn-sm w-100 mt-2"
            onclick="event.preventDefault(); document.getElementById('logout-form').submit();">
            <i class="fas fa-sign-out-alt me-2"></i> Logout
        </a>
        <form id="logout-form" action="{{ route('logout') }}" method="POST" class="d-none">
            @csrf
        </form>
    </div>
</aside>

<!-- Mobile Offcanvas -->
<div class="offcanvas offcanvas-start" tabindex="-1" id="sidebarOffcanvas"
    style="width: 280px; background: linear-gradient(180deg, var(--primary-dark) 0%, var(--primary) 100%); color: #fff;">
    <div class="offcanvas-header border-bottom" style="border-color: rgba(255,255,255,0.1);">
        <div class="d-flex align-items-center gap-3">
            @if ($logo)
                <div class="sidebar-logo-circle">
                    <img src="{{ $logo }}" alt="{{ $logoAlt }}" loading="lazy">
                </div>
            @endif
            <div>
                <div class="small text-white-50 text-uppercase fw-semibold">
                    {{ $brand ?? config('app.name') }}
                </div>
                <div class="h6 mb-0 fw-bold text-white">
                    {{ $userRole ?? 'Dashboard' }}
                </div>
            </div>
        </div>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="offcanvas"></button>
    </div>
    <div class="offcanvas-body">
        <nav class="nav flex-column">
            @foreach ($navItems as $item)
                @php
                    $itemHeader = data_get($item, 'header');
                    $itemDivider = (bool) data_get($item, 'divider', false);
                    $itemSubmenu = data_get($item, 'submenu', []);
                    $itemIcon = data_get($item, 'icon');
                    $itemLabel = data_get($item, 'label', '');
                    $itemUrl = data_get($item, 'url', '#');
                    $itemSubmenu =
                        $itemSubmenu instanceof \Illuminate\Support\Collection
                            ? $itemSubmenu->all()
                            : (is_array($itemSubmenu)
                                ? $itemSubmenu
                                : []);
                @endphp
                @if ($itemHeader !== null)
                    <div class="text-white-50 small text-uppercase px-3 mt-3 mb-2">{{ $itemHeader }}</div>
                @elseif($itemDivider)
                    <hr class="my-2" style="border-color: rgba(255,255,255,0.1);">
                @elseif(count($itemSubmenu) > 0)
                    <div class="has-submenu">
                        <a class="nav-link text-white" href="#" data-bs-toggle="collapse"
                            data-bs-target="#mobile-submenu-{{ $loop->index }}">
                            @if ($itemIcon)
                                <i class="fas {{ $itemIcon }} me-2"></i>
                            @endif
                            {{ $itemLabel }}
                            <i class="fas fa-chevron-down ms-auto submenu-arrow"></i>
                        </a>
                        <div class="submenu collapse" id="mobile-submenu-{{ $loop->index }}">
                            @foreach ($itemSubmenu as $subitem)
                                <a class="nav-link text-white-50" href="{{ data_get($subitem, 'url', '#') }}">
                                    {{ data_get($subitem, 'label', '') }}
                                </a>
                            @endforeach
                        </div>
                    </div>
                @else
                    <a class="nav-link text-white" href="{{ $itemUrl }}">
                        @if ($itemIcon)
                            <i class="fas {{ $itemIcon }} me-2"></i>
                        @endif
                        {{ $itemLabel }}
                    </a>
                @endif
            @endforeach
        </nav>
    </div>
</div>

@push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const offcanvasElement = document.getElementById('sidebarOffcanvas');
            if (offcanvasElement) {
                new bootstrap.Offcanvas(offcanvasElement);
            }
        });
    </script>
@endpush
