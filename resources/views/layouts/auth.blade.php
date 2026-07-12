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

    @vite(['resources/css/auth.css'])
    @stack('styles')
</head>

<body>
    <div class="auth-wrapper d-flex align-items-center justify-content-center min-vh-100"
        style="background: url('{{ asset('images/rentalbg.jpg') }}') center/cover no-repeat; position: relative;">

        <div class="container" style="position: relative; z-index: 1;">
            <div class="row justify-content-center">
                <div class="col-md-6 col-lg-5 col-xl-4">
                    <div class="card shadow-lg border-0"
                        style="background: rgba(255, 255, 255, 0.95); backdrop-filter: blur(10px); border-radius: 20px;">
                        <div class="card-body p-4 p-md-5">
                            {{-- Brand Header --}}
                            <div class="text-center mb-4">
                                <div class="d-flex align-items-center justify-content-center gap-3 mb-3">
                                    <!-- Brand Chip with Logo Image -->
                                    <div class="brand-chip"
                                        style="width: 60px; height: 60px; border-radius: 50%; overflow: hidden; border: 3px solid #9BE866; box-shadow: 0 0 0 4px rgba(159, 232, 102, 0.2); display: flex; align-items: center; justify-content: center; background: #FEFDD6; flex-shrink: 0;">
                                        <img src="{{ asset('images/logo.jpg') }}" alt="Logo"
                                            style="width: 100%; height: 100%; object-fit: cover;">
                                    </div>
                                    <div>
                                        <div class="small text-uppercase fw-semibold" style="color: #504E76;">Rental
                                            System</div>
                                        <div class="h5 mb-0" style="color: #504E76; font-weight: 700;">@yield('auth-title', 'Welcome')
                                        </div>
                                    </div>
                                </div>
                                <p class="text-muted small" style="color: #504E76 !important;">@yield('auth-subtitle', 'Sign in to your account')</p>
                            </div>

                            {{-- Auth Content --}}
                            @yield('auth-content')
                        </div>
                    </div>

                    {{-- Footer --}}
                    <div class="text-center mt-3">
                        <p class="small mb-0" style="color: rgba(255, 255, 255, 0.8);">&copy; {{ date('Y') }} Rental
                            System. All rights reserved.</p>
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
