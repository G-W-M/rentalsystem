{{-- resources/views/layouts/auth.blade.php --}}
{{-- FIXED: This is a standalone auth layout - NO @extends('layouts.app') --}}
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Authentication') - Rental System</title>

    <!-- Bootstrap Yeti Theme -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootswatch@5.3.8/dist/yeti/bootstrap.min.css">

    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">

    @vite(['resources/css/app.css'])
    @stack('styles')
</head>

<body>
    <div class="auth-wrapper d-flex align-items-center justify-content-center min-vh-100"
        style="background: linear-gradient(135deg, #13293d 0%, #1f3b57 100%);">
        <div class="container">
            <div class="row justify-content-center">
                <div class="col-md-6 col-lg-5 col-xl-4">
                    <div class="card shadow-lg border-0">
                        <div class="card-body p-4 p-md-5">
                            {{-- Brand Header --}}
                            <div class="text-center mb-4">
                                <div class="d-flex align-items-center justify-content-center gap-3 mb-3">
                                    <div class="brand-chip"
                                        style="width: 50px; height: 50px; background: #13293d; border-radius: 12px; display: flex; align-items: center; justify-content: center; color: white; font-weight: bold; font-size: 20px;">
                                        RS
                                    </div>
                                    <div>
                                        <div class="small text-uppercase fw-semibold text-muted">Rental System</div>
                                        <div class="h5 mb-0">@yield('auth-title', 'Welcome')</div>
                                    </div>
                                </div>
                                <p class="text-muted small">@yield('auth-subtitle', 'Sign in to your account')</p>
                            </div>

                            {{-- Auth Content --}}
                            @yield('auth-content')
                        </div>
                    </div>

                    {{-- Footer --}}
                    <div class="text-center mt-3">
                        <p class="text-white-50 small mb-0">&copy; {{ date('Y') }} Rental System. All rights
                            reserved.</p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Scripts --}}
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    @vite(['resources/js/app.js'])
    @stack('scripts')
</body>

</html>
