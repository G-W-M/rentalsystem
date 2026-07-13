<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ config('app.name', 'Rental System') }}</title>
    @vite(['resources/css/app.css'])
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            background: url('{{ asset('images/rentalbg.jpg') }}') center/cover no-repeat;
            position: relative;
        }

        body::before {
            content: '';
            position: fixed;
            inset: 0;
            background: rgba(0, 0, 0, 0.35);
            z-index: 0;
        }

        .splash {
            position: relative;
            z-index: 1;
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 1.5rem;
        }

        .logo-ring {
            width: 110px;
            height: 110px;
            border-radius: 50%;
            overflow: hidden;
            border: 4px solid #9BE866;
            box-shadow: 0 0 0 6px rgba(155, 232, 102, 0.25),
                0 20px 60px rgba(0, 0, 0, 0.4);
            background: #FEFDD6;
            animation: pulse 2s ease-in-out infinite;
        }

        .logo-ring img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        @keyframes pulse {

            0%,
            100% {
                box-shadow: 0 0 0 6px rgba(155, 232, 102, 0.25), 0 20px 60px rgba(0, 0, 0, 0.4);
            }

            50% {
                box-shadow: 0 0 0 14px rgba(155, 232, 102, 0.12), 0 20px 60px rgba(0, 0, 0, 0.4);
            }
        }

        .system-name {
            font-family: 'Segoe UI', Arial, sans-serif;
            font-size: 1.5rem;
            font-weight: 700;
            color: #ffffff;
            letter-spacing: 0.08em;
            text-transform: uppercase;
            text-shadow: 0 2px 8px rgba(0, 0, 0, 0.4);
        }

        .loading-row {
            display: flex;
            align-items: center;
            gap: 0.6rem;
            color: rgba(255, 255, 255, 0.85);
            font-family: 'Segoe UI', Arial, sans-serif;
            font-size: 0.95rem;
            letter-spacing: 0.04em;
        }

        .dots span {
            display: inline-block;
            width: 6px;
            height: 6px;
            border-radius: 50%;
            background: #9BE866;
            animation: bounce 1.2s ease-in-out infinite;
        }

        .dots span:nth-child(2) {
            animation-delay: 0.2s;
        }

        .dots span:nth-child(3) {
            animation-delay: 0.4s;
        }

        @keyframes bounce {

            0%,
            80%,
            100% {
                transform: scale(0.7);

            }

            40% {
                transform: scale(1);

            }

            .footer-text {
                position: fixed;
                bottom: 1.5rem;
                width: 100%;
                text-align: center;
                color: rgba(255, 255, 255, 0.55);
                font-family: 'Segoe UI', Arial, sans-serif;
                font-size: 0.8rem;
                z-index: 1;
            }
    </style>
</head>

<body>
    <div class="splash">
        <div class="logo-ring">
            <img src="{{ asset('images/logo.jpg') }}" alt="Rental System Logo">
        </div>
        <div class="system-name">Rental System</div>
        <div class="loading-row">
            Loading
            <div class="dots">
                <span></span><span></span><span></span>
            </div>
        </div>
    </div>

    <div class="footer-text">
        &copy; {{ date('Y') }} Rental System. All rights reserved.
    </div>

    <script>
        setTimeout(function() {
            window.location.href = "{{ route('login') }}";
        }, 5000);
    </script>
</body>

</html>
