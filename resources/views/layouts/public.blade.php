<!doctype html>
<html lang="es" data-bs-theme="light">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    @include("partials.favicons")
    @include("partials.meta-og")

    @include('partials.theme-init')

    <title>@yield('title', $league->name)</title>
    <meta property="og:title" content="@yield('og_title', $league->name)">
    <meta property="og:description" content="@yield('og_description', 'Liga de padel en vivo')">
    @if ($league->banner_path)
    <meta property="og:image" content="{{ Storage::disk('public')->url($league->banner_path) }}">
    @endif
    <script defer src="https://cloud.umami.is/script.js" data-website-id="ffcbc4dd-0b3e-43e8-b4d2-53a2a9bc2224"></script>
    @vite(['resources/css/theme.css', 'resources/js/public.js'])
</head>

<body class="public-body">

    {{-- Fixed topbar: brand + theme toggle (moved out of the hero). The league
         name fades in once you scroll past the hero. --}}
    {{-- Fixed topbar: brand (left), social icons + theme toggle (right). The league
         name fades in once you scroll past the hero image. --}}
    <nav class="public-topbar fixed-top" id="public-topbar">
        <div class="public-topbar-inner">
            <a href="{{ route('landing') }}" class="public-topbar-brand" aria-label="PlayWinners">
                @include('partials._brand-logo', ['height' => 30, 'alt' => 'PlayWinners'])
            </a>
            <a href="{{ route('public.league', $league->slug) }}" class="public-topbar-title">{{ $league->name }}</a>
            <div class="public-topbar-actions">
                @if ($league->whatsapp_url)
                <a href="{{ $league->whatsapp_url }}" target="_blank" rel="noopener noreferrer"
                    class="social-link" title="Unirse al grupo de WhatsApp" aria-label="WhatsApp">
                    <i class="fa-brands fa-whatsapp fa-xl"></i>
                </a>
                @endif
                @include('public.league._social-links', ['variant' => 'header'])
                <button type="button" data-theme-toggle class="public-topbar-theme-toggle"
                    title="Cambiar tema" aria-label="Cambiar tema">
                    <i class="fa-solid fa-sun theme-icon-light"></i>
                    <i class="fa-solid fa-moon theme-icon-dark"></i>
                </button>
            </div>
        </div>
    </nav>

    <div class="public-shell">

        @php
        $statusMap = [
        'active' => ['EN CURSO', 'is-live'],
        'completed' => ['Temporada cerrada', 'is-closed'],
        'archived' => ['Archivada', 'is-closed'],
        'draft' => ['Próximamente', 'is-draft'],
        ];
        [$statusLabel, $statusTone] = $statusMap[$league->status] ?? [null, 'is-draft'];
        @endphp

        {{-- Hero lives in the same capped container as the rest of the page.
             No banner → no image block (no placeholder). --}}
        <div class="public-hero-wrap">
            @if ($league->banner_path)
            <header class="public-hero-media">
                <img src="{{ Storage::disk('public')->url($league->banner_path) }}"
                    alt="{{ $league->name }}" class="public-hero-img" loading="eager">
            </header>
            @endif

            <div class="public-hero-info">
                @if ($statusLabel)
                <div class="public-hero-status {{ $statusTone }}">
                    <span class="status-dot"></span> {{ $statusLabel }}
                </div>
                @endif
                <h1 class="public-hero-title">{{ $league->name }}</h1>
                <div class="public-hero-meta">
                    <span class="badge text-bg-secondary">
                        <i class="fa-solid fa-{{ $league->format === 'pairs' ? 'people-arrows' : 'user' }} me-1"></i>
                        {{ $league->format === 'pairs' ? 'Parejas' : 'Individual' }}
                    </span>
                </div>
            </div>
        </div>

        @php
        $pages = [
        ['key' => 'inicio', 'route' => route('public.league', $league->slug), 'label' => 'Inicio', 'icon' => 'fa-house'],
        ['key' => 'calendario', 'route' => route('public.calendario', $league->slug), 'label' => 'Calendario', 'icon' => 'fa-calendar-days'],
        ['key' => 'clasificacion', 'route' => route('public.clasificacion', $league->slug),'label' => 'Clasificación', 'icon' => 'fa-ranking-star'],
        ['key' => 'jugadores', 'route' => route('public.jugadores', $league->slug), 'label' => $league->format === 'pairs' ? 'Parejas' : 'Jugadores', 'icon' => 'fa-users'],
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

        {{-- Ads — shown on every public page (league-specific + global) --}}
        @php $publicAds = $league->publicAds(); @endphp
        @if ($publicAds->isNotEmpty())
        <div class="public-ads-wrap">
            <div class="public-ads-inner">
                @include('public.league._ads-carousel', ['ads' => $publicAds])
            </div>
        </div>
        @endif


        <main class="public-main pb-5">
            @yield('content')
        </main>

        <footer class="public-footer fixed-bottom">
            <div class="public-footer-inner">
                @include('public.league._social-links', ['variant' => 'footer'])

                <small class="text-secondary">
                    Powered by PlayWinners
                </small>
            </div>
        </footer>

    </div>


</body>

</html>