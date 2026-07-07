<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ config('app.name', 'Rental System') }} - Welcome</title>

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=figtree:400,600&display=swap" rel="stylesheet" />

    <!-- Your existing CSS/JS via Vite -->
    @vite(['resources/css/app.css', 'resources/js/app.js'])

    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Figtree', sans-serif;
            background: #f3f4f6;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 1rem;
        }

        .welcome-container {
            background: white;
            border-radius: 1rem;
            padding: 3rem 4rem;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.1);
            text-align: center;
            max-width: 500px;
            width: 100%;
        }

        .welcome-title {
            font-size: 2.5rem;
            font-weight: 700;
            color: #1a1a2e;
            margin-bottom: 0.5rem;
        }

        .welcome-title span {
            color: #4f46e5;
        }

        .welcome-subtitle {
            color: #6b7280;
            font-size: 1.1rem;
            margin-bottom: 2rem;
        }

        .btn-group {
            display: flex;
            gap: 1rem;
            justify-content: center;
            flex-wrap: wrap;
        }

        .btn {
            padding: 0.75rem 2rem;
            border-radius: 0.5rem;
            font-weight: 600;
            text-decoration: none;
            transition: all 0.2s;
            display: inline-block;
            min-width: 140px;
        }

        .btn-primary {
            background: #4f46e5;
            color: white;
        }

        .btn-primary:hover {
            background: #4338ca;
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(79, 70, 229, 0.4);
        }

        .btn-secondary {
            background: #e5e7eb;
            color: #1a1a2e;
        }

        .btn-secondary:hover {
            background: #d1d5db;
            transform: translateY(-2px);
        }

        .countdown-badge {
            display: inline-block;
            background: #f3f4f6;
            padding: 0.5rem 1rem;
            border-radius: 9999px;
            font-size: 0.875rem;
            color: #6b7280;
            margin-top: 1.5rem;
        }

        .countdown-badge span {
            font-weight: 700;
            color: #4f46e5;
            font-size: 1.1rem;
        }

        .footer-text {
            margin-top: 2rem;
            color: #9ca3af;
            font-size: 0.875rem;
        }

        @media (max-width: 640px) {
            .welcome-container {
                padding: 2rem 1.5rem;
            }

            .welcome-title {
                font-size: 2rem;
            }

            .btn {
                width: 100%;
                min-width: unset;
            }

            .btn-group {
                flex-direction: column;
            }
        }
    </style>
</head>

<body>
    <div class="welcome-container">
        <h1 class="welcome-title"><span>Rental</span> System</h1>
        <p class="welcome-subtitle">Welcome to your property management solution</p>

        <div class="btn-group">
            <a href="{{ route('login') }}" class="btn btn-primary">Sign In</a>
            <a href="{{ route('register') }}" class="btn btn-secondary">Register</a>
        </div>

        <div class="countdown-badge">
            Redirecting to login in <span id="countdown">4</span> seconds...
        </div>

        <div class="footer-text">
            &copy; {{ date('Y') }} Rental System. All rights reserved.
        </div>
    </div>

    <script>
        // Countdown timer - redirect to login after 4 seconds
        let seconds = 4;
        const countdownElement = document.getElementById('countdown');

        const interval = setInterval(function() {
            seconds--;
            if (countdownElement) {
                countdownElement.textContent = seconds;
            }
            if (seconds <= 0) {
                clearInterval(interval);
                window.location.href = "{{ route('login') }}";
            }
        }, 1000);
    </script>
</body>

</html>
