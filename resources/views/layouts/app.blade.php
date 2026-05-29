<!doctype html>
<html lang="es">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Padel Leagues')</title>
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