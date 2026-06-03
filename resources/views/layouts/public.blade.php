<!doctype html>
<html lang="es">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="theme-color" content="#1d4ed8">
    <title>@yield('title', 'Padel Leagues')</title>
    <meta name="csrf-token" content="{{ csrf_token() }}">
    @hasSection('og')
    @yield('og')
    @endif

    @vite(['resources/css/theme.css', 'resources/js/public.js'])
</head>

<body class="public-body">
    <div class="public-shell">
        @yield('content')

        <footer class="public-footer">
            <small class="text-secondary">
                <i class="fa-solid fa-table-tennis-paddle-ball me-1"></i>
                Powered by <strong>Padel Leagues</strong>
            </small>
        </footer>
    </div>
</body>

</html>