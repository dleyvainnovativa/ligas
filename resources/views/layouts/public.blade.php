<!doctype html>
<html lang="es" data-bs-theme="light">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <link rel="icon" type="image/png" href="{{asset('img/icon/favicon-96x96.png')}}" sizes="96x96" />
    <link rel="icon" type="image/svg+xml" href="{{asset('img/icon/favicon.svg')}}" />
    <link rel="shortcut icon" href="{{asset('img/icon/favicon.ico')}}" />
    <link rel="apple-touch-icon" sizes="180x180" href="{{asset('img/icon/apple-touch-icon.png')}}" />
    <meta name="apple-mobile-web-app-title" content="PlayWinners Pro" />
    <link rel="manifest" href="{{asset('img/icon/site.webmanifest')}}" />

    @include('partials.theme-init')

    <title>@yield('title', $league->name)</title>
    <meta property="og:title" content="@yield('og_title', $league->name)">
    <meta property="og:description" content="@yield('og_description', 'Liga de padel en vivo')">
    @if ($league->banner_path)
    <meta property="og:image" content="{{ Storage::disk('public')->url($league->banner_path) }}">
    @endif

    @vite(['resources/css/theme.css', 'resources/js/public.js'])
</head>

<body class="public-body">

    <header class="public-hero">
        @if ($league->banner_path)
        <div class="public-hero-bg" style="background-image: url('{{ Storage::disk('public')->url($league->banner_path) }}');"></div>
        @endif
        <div class="public-hero-content">
            <div class="public-hero-title-row">
                <a href="{{ route('public.league', $league->slug) }}" class="public-hero-title">
                    <h1>{{ $league->name }}</h1>
                </a>
                <button type="button"
                    data-theme-toggle
                    class="public-hero-theme-toggle"
                    title="Cambiar tema"
                    aria-label="Cambiar tema">
                    <i class="fa-solid fa-sun theme-icon-light"></i>
                    <i class="fa-solid fa-moon theme-icon-dark"></i>
                </button>
            </div>
            <div class="public-hero-meta">
                <span class="badge text-bg-light">
                    <i class="fa-solid fa-{{ $league->format === 'pairs' ? 'people-arrows' : 'user' }} me-1"></i>
                    {{ $league->format === 'pairs' ? 'Parejas' : 'Individual' }}
                </span>
                @if ($league->status === 'completed')
                <span class="badge text-bg-light">Temporada cerrada</span>
                @endif
            </div>
        </div>
    </header>

    <div class="public-shell">

        @php
        $pages = [
        ['key' => 'inicio', 'route' => route('public.league', $league->slug), 'label' => 'Inicio', 'icon' => 'fa-house'],
        ['key' => 'calendario', 'route' => route('public.calendario', $league->slug), 'label' => 'Calendario', 'icon' => 'fa-calendar-days'],
        ['key' => 'clasificacion', 'route' => route('public.clasificacion', $league->slug),'label' => 'Clasificación', 'icon' => 'fa-ranking-star'],
        ['key' => 'jugadores', 'route' => route('public.jugadores', $league->slug), 'label' => 'Jugadores', 'icon' => 'fa-users'],
        ['key' => 'reglas', 'route' => route('public.reglas', $league->slug), 'label' => 'Reglas', 'icon' => 'fa-book-open'],
        ];
        $active = $active_page ?? 'inicio';
        @endphp

        <nav class="public-nav">
            <div class="public-nav-inner">
                @foreach ($pages as $p)
                <a href="{{ $p['route'] }}"
                    class="public-nav-link {{ $active === $p['key'] ? 'is-active' : '' }}">
                    <i class="fa-solid {{ $p['icon'] }}"></i>
                    <span>{{ $p['label'] }}</span>
                </a>
                @endforeach
            </div>
        </nav>

        <main class="public-main">
            @yield('content')
        </main>

        <footer class="public-footer fixed-bottom">
            <div class="public-footer-inner">
                <small class="text-secondary">
                    Powered by PlayWinners
                </small>
            </div>
        </footer>

    </div>


</body>

</html>