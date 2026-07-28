<!doctype html>
<html lang="es">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title') — PlayWinners</title>
    <link rel="icon" type="image/png" href="{{asset('img/icon/favicon-96x96.png')}}" sizes="96x96" />
    <link rel="icon" type="image/svg+xml" href="{{asset('img/icon/favicon.svg')}}" />
    <link rel="shortcut icon" href="{{asset('img/icon/favicon.ico')}}" />
    <link rel="apple-touch-icon" sizes="180x180" href="{{asset('img/icon/apple-touch-icon.png')}}" />
    <meta name="apple-mobile-web-app-title" content="PlayWinners Pro" />
    <link rel="manifest" href="{{asset('img/icon/site.webmanifest')}}" />
    @include('partials.theme-init')
    @vite(['resources/css/theme.css'])
</head>

<body class="error-body">
    <main class="error-shell">
        <div class="error-content">
            <div class="error-brand">
                <!-- <span class="brand-mark"><i class="fa-solid fa-table-tennis-paddle-ball"></i></span>
                <span class="brand-name">PlayWinners</span> -->
                <img src="{{ asset('img/logo.png') }}" alt="Logo" class="public-stat-logo-img">
            </div>
            <div class="error-code">@yield('code')</div>
            <h1>@yield('title')</h1>
            <p>@yield('message')</p>
            <div class="error-actions">
                <a href="{{ url('/') }}" class="btn btn-primary">
                    <i class="fa-solid fa-house me-1"></i> Volver al inicio
                </a>
                <button onclick="history.back()" class="btn btn-outline-secondary">
                    <i class="fa-solid fa-arrow-left me-1"></i> Atrás
                </button>
            </div>
        </div>
    </main>
</body>

</html>