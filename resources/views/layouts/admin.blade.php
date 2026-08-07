<!doctype html>
<html lang="es">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Admin — PlayWinners')</title>
    @include('partials.favicons')
    @include('partials.theme-init')
    @vite(['resources/css/theme.css', 'resources/js/app.js'])
</head>

<body>
    <div class="app-shell">
        <aside class="app-sidebar" id="app-sidebar">
            <div class="brand">
                <img src="{{ asset('img/logo.png') }}" width="200" alt="PlayWinners">
            </div>

            <div class="nav-label">Administración</div>
            <nav class="nav flex-column">
                <a href="{{ route('admin.dashboard') }}"
                    class="nav-link {{ request()->routeIs('admin.dashboard') ? 'active' : '' }}">
                    <i class="fa-solid fa-gauge-high"></i> Panel
                </a>
                <a href="{{ route('admin.leagues.index') }}"
                    class="nav-link {{ request()->routeIs('admin.leagues.*') ? 'active' : '' }}">
                    <i class="fa-solid fa-trophy"></i> Ligas
                </a>
                <a href="{{ route('admin.managers') }}"
                    class="nav-link {{ request()->routeIs('admin.managers') ? 'active' : '' }}">
                    <i class="fa-solid fa-users-gear"></i> Managers
                </a>
                <a href="{{ route('admin.ads.index') }}"
                    class="nav-link {{ request()->routeIs('admin.ads.*') ? 'active' : '' }}">
                    <i class="fa-solid fa-rectangle-ad"></i> Anuncios
                </a>
            </nav>

            <div class="nav-label">General</div>
            <nav class="nav flex-column">
                <a href="{{ route('dashboard') }}" class="nav-link">
                    <i class="fa-solid fa-arrow-left"></i> Volver a la app
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

                @if ($errors->any())
                <div class="alert alert-danger d-flex align-items-start gap-2 mb-4" role="alert">
                    <i class="fa-solid fa-triangle-exclamation mt-1"></i>
                    <div>
                        @foreach ($errors->all() as $error)
                        <div>{{ $error }}</div>
                        @endforeach
                    </div>
                </div>
                @endif

                @yield('content')
            </div>
        </main>
    </div>
</body>

</html>