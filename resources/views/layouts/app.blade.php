{{-- resources/views/layouts/app.blade.php --}}
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'System UI')</title>

    <!-- Bootstrap Yeti Theme -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootswatch@5.3.8/dist/yeti/bootstrap.min.css">

    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">

    <!-- Custom assets (app.js imports all project CSS modules) -->
    @vite(['resources/js/app.js'])
    @stack('styles')
</head>

<body>
    <div id="app">
        <!-- Navbar -->
        @include('components.navigation.navbar')

        <!-- Main Content Area with Sidebar -->
        <div class="d-flex">
            <!-- Sidebar -->
            @include('components.navigation.sidebar')

            <!-- Main Content -->
            <main class="flex-grow-1 p-3 p-md-4 p-xl-5 @yield('main-class', 'bg-default')" style="@yield('main-style', '')">
                @yield('content')
            </main>
        </div>

        <!-- Footer -->
        @include('components.navigation.footer')
    </div>

    <!-- Bootstrap Bundle -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

    <!-- Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

    <!-- Custom JS -->
    @stack('scripts')
</body>

</html>
