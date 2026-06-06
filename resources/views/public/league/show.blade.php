@extends('layouts.public')

@section('title', "{$league->name} — Padel Leagues")

@section('og')
<meta property="og:type" content="website">
<meta property="og:title" content="{{ $league->name }}">
<meta property="og:description" content="{{ $league->description ?: 'Sigue la liga en vivo: standings, próximos partidos y resultados.' }}">
@if ($league->banner_url)
<meta property="og:image" content="{{ $league->banner_url }}">
@endif
<meta property="og:url" content="{{ url('/'.$league->slug) }}">
@endsection

@section('content')
@php
$isLive = $league->status === \App\Models\League::STATUS_ACTIVE;
$isCompleted = $league->status === \App\Models\League::STATUS_COMPLETED;
$currentJornada = $payload['current_jornada'];
$totals = $payload['totals'];
@endphp

{{-- ===== Banner ===== --}}
<header class="public-banner">
    @if ($league->banner_url)
    <div class="public-banner-image" style="background-image: url('{{ $league->banner_url }}');"></div>
    @endif
    <div class="public-banner-overlay"></div>
    <div class="public-banner-content">
        <div class="public-banner-badges">
            @if ($isLive)
            <span class="public-banner-badge is-live">
                <i class="fa-solid fa-circle-dot me-1"></i> EN CURSO
            </span>
            @elseif ($isCompleted)
            <span class="public-banner-badge">
                <i class="fa-solid fa-flag-checkered me-1"></i> COMPLETADA
            </span>
            @endif
            <span class="public-banner-badge">
                {{ $league->format === 'pairs' ? 'Parejas' : 'Individual' }}
            </span>
            <span class="public-banner-badge">
                {{ $league->num_jornadas }} jornadas
            </span>
        </div>

        <h1 class="public-banner-title text-light">{{ $league->name }}</h1>

        @if ($league->description)
        <p class="public-banner-desc">{{ \Illuminate\Support\Str::limit($league->description, 200) }}</p>
        @endif

        <div class="public-banner-actions">
            <button class="public-banner-btn is-primary" data-action="share"
                data-url="{{ url('/'.$league->slug) }}"
                data-title="{{ $league->name }}">
                <i class="fa-solid fa-share-nodes"></i> Compartir
            </button>
            @if ($currentJornada)
            <a href="#proximos" class="public-banner-btn">
                <i class="fa-solid fa-calendar-day"></i> Próximos partidos
            </a>
            @endif
            <button class="public-banner-btn" data-theme-toggle aria-label="Alternar tema">
                <i class="fa-solid fa-sun sun"></i>
                <i class="fa-solid fa-moon moon"></i>
            </button>
        </div>
    </div>
</header>

{{-- ===== Quick stats ===== --}}
<section class="public-stats">
    <div class="public-stat">
        <div class="public-stat-value">{{ $totals['players'] }}</div>
        <div class="public-stat-label">Jugadores</div>
    </div>
    <div class="public-stat">
        <div class="public-stat-value">{{ $totals['matches_played'] }}</div>
        <div class="public-stat-label">Partidos</div>
    </div>
    <div class="public-stat">
        <div class="public-stat-value">{{ $totals['jornadas'] }}</div>
        <div class="public-stat-label">Jornadas</div>
    </div>
</section>

@if ($league->activeAds->isNotEmpty())
@include('public.league._ads-carousel', ['ads' => $league->activeAds])
@endif

{{-- ===== Current jornada hero ===== --}}
@if ($currentJornada)
<div class="current-jornada">
    <div class="current-jornada-icon">
        <i class="fa-solid fa-calendar-day"></i>
    </div>
    <div class="current-jornada-text">
        <div class="small text-secondary">Ahora jugando</div>
        <div><strong>Jornada {{ $currentJornada['number'] }}</strong>
            @if (count($currentJornada['group_names']))
            — {{ implode(' · ', $currentJornada['group_names']) }}
            @endif
        </div>
    </div>
</div>
@endif

{{-- ===== Group tabs ===== --}}
@if (count($payload['groups']) > 0)
<div class="public-group-tabs">
    <ul class="nav" role="tablist">
        @foreach ($payload['groups'] as $i => $g)
        <li class="nav-item" role="presentation">
            <button class="nav-link {{ $i === 0 ? 'active' : '' }}"
                data-bs-toggle="tab"
                data-bs-target="#group-{{ $g['group']->id }}"
                type="button" role="tab">
                {{ $g['group']->name }}
            </button>
        </li>
        @endforeach
    </ul>
</div>

<div class="tab-content">
    @foreach ($payload['groups'] as $i => $g)
    <div class="tab-pane fade {{ $i === 0 ? 'show active' : '' }}"
        id="group-{{ $g['group']->id }}"
        role="tabpanel">

        @include('public.league._standings', ['rows' => $g['standings'], 'league' => $league])
        @include('public.league._upcoming', ['matches' => $g['upcoming'], 'league' => $league])
        @include('public.league._results', ['matches' => $g['recent'], 'league' => $league])
    </div>
    @endforeach
</div>
@else
<div class="public-section">
    <div class="public-section-empty">
        <i class="fa-solid fa-layer-group fa-2x text-muted mb-3 d-block"></i>
        Aún no hay grupos configurados en esta liga.
    </div>
</div>
@endif

<div class="modal fade" id="propose-modal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Proponer marcador</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="propose-modal-body">
                {{-- filled by JS --}}
            </div>
            <div class="modal-footer">
                <button class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancelar</button>
                <button class="btn btn-primary" id="propose-submit-btn">
                    <i class="fa-solid fa-paper-plane me-1"></i> Enviar propuesta
                </button>
            </div>
        </div>
    </div>
</div>

@php
$publicMatches = collect($payload['groups'])
    ->flatMap(fn ($g) => array_merge($g['upcoming'], $g['recent']))
    ->flatMap(fn ($cancha) => collect($cancha['rounds'])->map(fn ($round) => array_merge(
        $round,
        [
            'date_display' => $cancha['date_display'] ?? null,
            'time_slot'    => $cancha['time_slot'] ?? null,
        ]
    )))
    ->keyBy('id');
@endphp

<script>
</script>

<script>
    window.__publicLeagueSlug = @json($league->slug);
    window.__publicMatches = @json($publicMatches);
</script>
@endsection