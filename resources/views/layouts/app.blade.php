<!doctype html>
<html lang="es">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Padel Leagues')</title>
    <link rel="icon" type="image/png" href="{{asset('img/icon/favicon-96x96.png')}}" sizes="96x96" />
    <link rel="icon" type="image/svg+xml" href="{{asset('img/icon/favicon.svg')}}" />
    <link rel="shortcut icon" href="{{asset('img/icon/favicon.ico')}}" />
    <link rel="apple-touch-icon" sizes="180x180" href="{{asset('img/icon/apple-touch-icon.png')}}" />
    <meta name="apple-mobile-web-app-title" content="PlayWinners Pro" />
    <link rel="manifest" href="{{asset('img/icon/site.webmanifest')}}" />
    @include('partials.theme-init')
    @vite(['resources/css/theme.css', 'resources/js/app.js'])
</head>

<body>
    <div class="app-shell">
        <aside class="app-sidebar" id="app-sidebar">
            <div class="brand">
                <!-- <span class="brand-mark"><i class="fa-solid fa-table-tennis-paddle-ball"></i></span>
                <span>Padel Leagues</span> -->
                <img src="{{asset('img/logo.png')}}" width="200" alt="">
            </div>

            <div class="nav-label">General</div>
            <nav class="nav flex-column">
                <a href="{{ route('dashboard') }}"
                    class="nav-link {{ request()->routeIs('dashboard') ? 'active' : '' }}">
                    <i class="fa-solid fa-gauge-high"></i> Dashboard
                </a>
                <a href="{{ route('leagues.index') }}"
                    class="nav-link {{ request()->routeIs('leagues.*') ? 'active' : '' }}">
                    <i class="fa-solid fa-trophy"></i> Ligas
                </a>
            </nav>

            <div class="nav-label">Cuenta</div>
            <nav class="nav flex-column">
                <a href="#" class="nav-link disabled">
                    <i class="fa-regular fa-circle-user"></i> Perfil
                </a>
            </nav>
        </aside>

        <div class="sidebar-backdrop" id="sidebar-backdrop"></div>

        <main class="app-main">
            @if (session('flash'))
            <div id="flash-message" data-message="{{ session('flash') }}" hidden></div>
            @endif

            @if (session('flash_error'))
            <div id="flash-error" data-message="{{ session('flash_error') }}" hidden></div>
            @endif
            @include('partials.topbar')

            <div class="app-content">
                @if (session('success'))
                <div class="alert alert-success d-flex align-items-center gap-2 mb-4" role="alert">
                    <i class="fa-solid fa-circle-check"></i>
                    <span>{{ session('success') }}</span>
                </div>
                @endif

                @yield('content')
            </div>
        </main>
    </div>
</body>

</html>