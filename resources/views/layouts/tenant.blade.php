<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <link rel="manifest" href="/manifest.json">
    <meta name="theme-color" content="#0d6efd">
    <link rel="apple-touch-icon" href="/icons/icon-192.png">

    <title>@yield('title', 'Tenant Portal') - Rental System</title>

    {{-- Font Awesome --}}
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">

    {{-- Bootstrap 5 --}}
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">

    {{-- Custom CSS --}}
    @vite(['resources/css/app.css'])
    @stack('styles')
</head>

<body>
    <form id="logout-form" method="POST" action="{{ route('logout') }}" class="d-none">
        @csrf
    </form>

    <div class="tenant-shell">
        {{-- Desktop Sidebar --}}
        <aside class="tenant-sidebar d-none d-lg-flex flex-column">
            <div class="sidebar-brand">
                <div class="brand-chip">RS</div>
                <div>
                    <div class="brand-label">Rental System</div>
                    <div class="brand-title">Tenant Portal</div>
                </div>
            </div>
<div class="dropdown me-2" id="notif-bell">
    <button class="btn btn-outline-secondary btn-sm rounded-circle position-relative" data-bs-toggle="dropdown">
        <i class="fas fa-bell"></i>
        <span id="notif-badge" class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger" style="display:none;font-size:0.65rem;"></span>
    </button>
    <ul class="dropdown-menu dropdown-menu-end" id="notif-list" style="min-width:280px;max-height:350px;overflow-y:auto;">
        <li class="dropdown-item text-muted small">Loading...</li>
    </ul>
</div>
<button class="btn btn-outline-secondary btn-sm rounded-circle me-2" id="theme-toggle" title="Toggle dark mode">
    <i class="fas fa-moon"></i>
</button>
            <nav class="nav nav-pills flex-column gap-1">
                <a class="nav-link {{ request()->routeIs('tenant.dashboard') ? 'active' : '' }}"
                    href="{{ route('tenant.dashboard') }}">
                    <i class="fas fa-th-large me-2"></i> Dashboard
                </a>
                <a class="nav-link {{ request()->routeIs('tenant.unit') ? 'active' : '' }}"
                    href="{{ route('tenant.unit') }}">
                    <i class="fas fa-home me-2"></i> My Unit
                </a>
                <a class="nav-link {{ request()->routeIs('tenant.payments') ? 'active' : '' }}"
                    href="{{ route('tenant.payments') }}">
                    <i class="fas fa-credit-card me-2"></i> Payments
                </a>
                <a class="nav-link {{ request()->routeIs('tenant.maintenance') ? 'active' : '' }}"
                    href="{{ route('tenant.maintenance') }}">
                    <i class="fas fa-wrench me-2"></i> Maintenance
                </a>
            </nav>

            <div class="sidebar-footer">
                <div class="small text-muted mb-1">Signed in as</div>
                <div class="user-name">{{ Auth::user()->full_name ?? 'Tenant' }}</div>
                <div class="user-email">{{ Auth::user()->email ?? 'tenant@example.com' }}</div>
                <div class="mt-2">
                    <span class="badge bg-success">Active</span>
                </div>
                <button type="submit" form="logout-form"
                    class="btn btn-outline-secondary btn-sm w-100 mt-2">
                    <i class="fas fa-sign-out-alt me-2"></i> Logout
                </button>
            </div>
        </aside>

        {{-- Main Content --}}
        <main class="tenant-main">
            {{-- Mobile Header --}}
            <div class="mobile-header">
                <button class="btn btn-outline-secondary" type="button" data-bs-toggle="offcanvas"
                    data-bs-target="#sidebarOffcanvas">
                    <i class="fas fa-bars"></i>
                </button>
                <div class="portal-title">Tenant Portal</div>
                <div class="dropdown">
                    <button class="btn btn-outline-secondary btn-sm rounded-circle" data-bs-toggle="dropdown">
                        <i class="fas fa-user"></i>
                    </button>
                    <ul class="dropdown-menu dropdown-menu-end">
                        <li><a class="dropdown-item" href="{{ route('tenant.settings') }}">Settings</a></li>
                        <li>
                            <hr class="dropdown-divider">
                        </li>
                        <li>
                            <button type="submit" form="logout-form" class="dropdown-item text-danger">
                                Logout
                            </button>
                        </li>
                    </ul>
                </div>
            </div>

            {{-- Page Content --}}
            @yield('content')
        </main>
    </div>

    {{-- Mobile Offcanvas Sidebar --}}
    <div class="offcanvas offcanvas-start tenant-offcanvas" tabindex="-1" id="sidebarOffcanvas">
        <div class="offcanvas-header">
            <div class="d-flex align-items-center gap-3">
                <div class="brand-chip">RS</div>
                <div>
                    <div class="brand-label">Rental System</div>
                    <div class="brand-title">Tenant Portal</div>
                </div>
            </div>
            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="offcanvas"></button>
        </div>
        <div class="offcanvas-body">
            <nav class="nav nav-pills flex-column gap-1">
                <a class="nav-link {{ request()->routeIs('tenant.dashboard') ? 'active' : '' }}"
                    href="{{ route('tenant.dashboard') }}">
                    <i class="fas fa-th-large me-2"></i> Dashboard
                </a>
                <a class="nav-link {{ request()->routeIs('tenant.unit') ? 'active' : '' }}"
                    href="{{ route('tenant.unit') }}">
                    <i class="fas fa-home me-2"></i> My Unit
                </a>
                <a class="nav-link {{ request()->routeIs('tenant.payments') ? 'active' : '' }}"
                    href="{{ route('tenant.payments') }}">
                    <i class="fas fa-credit-card me-2"></i> Payments
                </a>
                <a class="nav-link {{ request()->routeIs('tenant.maintenance') ? 'active' : '' }}"
                    href="{{ route('tenant.maintenance') }}">
                    <i class="fas fa-wrench me-2"></i> Maintenance
                </a>
            </nav>
        </div>
    </div>

    {{-- Scripts --}}
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    @vite(['resources/js/app.js'])
    @stack('scripts')
</body>

</html>
PHPEOF