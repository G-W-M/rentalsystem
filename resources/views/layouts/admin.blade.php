{{-- resources/views/layouts/admin.blade.php --}}
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <link rel="manifest" href="/manifest.json">
    <meta name="theme-color" content="#055236">
    <link rel="apple-touch-icon" href="/icons/icon-192.png">

    <title>@yield('title', 'Admin Portal') - Rental System</title>

    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    @vite(['resources/css/app.css'])
    @stack('styles')
</head>

<body>
    <form id="logout-form" method="POST" action="{{ route('logout') }}" class="d-none">
        @csrf
    </form>

    <div class="landlord-shell">
        <aside class="landlord-sidebar d-none d-lg-flex flex-column">
            <div class="sidebar-brand">
                <div class="brand-chip">RS</div>
                <div>
                    <div class="brand-label">Rental System</div>
                    <div class="brand-title">Admin Portal</div>
                </div>
            </div>

            <nav class="nav nav-pills flex-column gap-1">
                <a class="nav-link {{ request()->routeIs('admin.dashboard') ? 'active' : '' }}" href="{{ route('admin.dashboard') }}">
                    <i class="fas fa-th-large me-2"></i> Dashboard
                </a>
                <a class="nav-link {{ request()->routeIs('admin.users') ? 'active' : '' }}" href="{{ route('admin.users') }}">
                    <i class="fas fa-users-cog me-2"></i> Users
                </a>
            </nav>

            <div class="sidebar-footer">
                <div class="small text-muted mb-1">Signed in as</div>
                <div class="user-name">{{ Auth::user()->full_name ?? 'Admin' }}</div>
                <div class="user-email">{{ Auth::user()->email ?? 'admin@example.com' }}</div>
                <div class="mt-2"><span class="badge bg-dark">Administrator</span></div>
                <a class="dropdown-item small" href="{{ route('admin.settings') }}">Settings</a>
                <button type="submit" form="logout-form" class="btn btn-outline-secondary btn-sm w-100 mt-2">
                    <i class="fas fa-sign-out-alt me-2"></i> Logout
                </button>
            </div>
        </aside>

        <main class="landlord-main">
            <div class="mobile-header">
                <button class="btn btn-outline-secondary" type="button" data-bs-toggle="offcanvas" data-bs-target="#sidebarOffcanvas">
                    <i class="fas fa-bars"></i>
                </button>
                <div class="portal-title">Admin Portal</div>
                <div class="dropdown">
                    <button class="btn btn-outline-secondary btn-sm rounded-circle" data-bs-toggle="dropdown"><i class="fas fa-user"></i></button>
                    <ul class="dropdown-menu dropdown-menu-end">
                        <li><a class="dropdown-item" href="{{ route('admin.settings') }}">Settings</a></li>
                        <li><hr class="dropdown-divider"></li>
                        <li><button type="submit" form="logout-form" class="dropdown-item text-danger">Logout</button></li>
                    </ul>
                </div>
            </div>

            @yield('content')
        </main>
    </div>

    <div class="offcanvas offcanvas-start landlord-offcanvas" tabindex="-1" id="sidebarOffcanvas">
        <div class="offcanvas-header">
            <div class="d-flex align-items-center gap-3">
                <div class="brand-chip">RS</div>
                <div>
                    <div class="brand-label">Rental System</div>
                    <div class="brand-title">Admin Portal</div>
                </div>
            </div>
            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="offcanvas"></button>
        </div>
        <div class="offcanvas-body">
            <nav class="nav nav-pills flex-column gap-1">
                <a class="nav-link" href="{{ route('admin.dashboard') }}"><i class="fas fa-th-large me-2"></i> Dashboard</a>
                <a class="nav-link" href="{{ route('admin.users') }}"><i class="fas fa-users-cog me-2"></i> Users</a>
            </nav>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    @vite(['resources/js/app.js'])
    @stack('scripts')
</body>

</html>
