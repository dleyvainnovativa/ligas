@extends('layouts.public')
@section('title', $league->name)

@section('content')
{{-- Stats strip --}}
<section class="public-stats">
    <div class="public-stat">
        <div class="public-stat-value">{{ $payload['stats']['players'] }}</div>
        <div class="public-stat-label">Jugadores</div>
    </div>


    <div class="public-stat">
        <div class="public-stat-value">{{ $payload['current_jornada'] ?? '—' }}</div>
        <div class="public-stat-label">Jornada actual</div>
    </div>
    <div class="public-stat">
        <div class="public-stat-value">{{ $payload['stats']['completion_pct'] }}%</div>
        <div class="public-stat-label">Avance</div>
    </div>
    <a href="#" class="public-stat public-stat-logo">
        <img src="{{ asset('img/logo.png') }}" alt="Logo" class="public-stat-logo-img">
    </a>
</section>

{{-- Current jornada — standings view --}}
@if ($payload['current_jornada'])
<section class="public-section">
    <div class="public-section-head">
        <h2>Jornada {{ $payload['current_jornada'] }}</h2>
        <a href="{{ route('public.jornada', [$league->slug, $payload['current_jornada']]) }}" class="public-section-link">
            Ver partidos →
        </a>
    </div>

    @foreach ($payload['groups'] as $g)
    <div class="public-group-block">
        <h6 class="public-group-name">
            <i class="fa-solid fa-layer-group text-muted"></i>
            {{ $g['group']->name }}
        </h6>

        @if (empty($g['breakdown']))
        <div class="public-empty">Aún no hay canchas en esta jornada.</div>
        @else
        @foreach ($g['breakdown'] as $cancha)
        @include('public.league._home-cancha-standings', [
        'cancha' => $cancha,
        'complete' => $g['jornada_done'],
        ])
        @endforeach
        @endif
    </div>
    @endforeach
</section>
@else
<section class="public-section">
    <div class="public-empty-big">
        @switch($payload['no_current_reason'] ?? null)
        @case('completed')
        <i class="fa-solid fa-flag-checkered"></i>
        <p class="mb-0">Esta liga ha terminado. ¡Gracias por participar!</p>
        @break

        @case('not_started')
        <i class="fa-solid fa-hourglass-start"></i>
        <p class="mb-0">Esta liga aún no comienza. Pronto se publicarán las jornadas.</p>
        @break

        @case('no_pending')
        <i class="fa-solid fa-circle-check"></i>
        <p class="mb-0">No hay más jornadas asignadas por el momento. Vuelve pronto para la siguiente.</p>
        @break

        @default
        <i class="fa-solid fa-circle-info"></i>
        <p class="mb-0">No hay información disponible en este momento.</p>
        @endswitch
    </div>
</section>
@endif

{{-- Mini standings --}}
<section class="public-section">
    <div class="public-section-head">
        <h2>Top 3</h2>
        <a href="{{ route('public.clasificacion', $league->slug) }}" class="public-section-link">
            Clasificación completa →
        </a>
    </div>
    <div class="row g-3">
        @foreach ($payload['groups'] as $g)
        <div class="col-md-6">
            <div class="public-leaderboard-card">
                <h6 class="mb-2">{{ $g['group']->name }}</h6>
                @if (empty($g['top_3']))
                <small class="text-muted">Sin datos todavía.</small>
                @else
                <ol class="public-podium">
                    @foreach ($g['top_3'] as $i => $row)
                    <li>
                        <span class="public-podium-rank">{{ $i + 1 }}</span>
                        <span class="public-podium-name">{{ $row['name'] }}</span>
                        <span class="public-podium-pts">{{ $row['points'] ?? $row['games_won'] ?? 0 }} pts</span>
                    </li>
                    @endforeach
                </ol>
                @endif
            </div>
        </div>
        @endforeach
    </div>
</section>
@include('public.league._propose-modal')

@php
// Flatten all rounds from the current jornada's canchas into a map
// keyed by round_id, with date/time context attached.
$proposeRounds = collect();
foreach ($payload['groups'] as $g) {
foreach ($g['canchas'] as $cancha) {
foreach ($cancha['rounds'] as $round) {
$proposeRounds->push(array_merge($round, [
'date_display' => $cancha['date_display'] ?? null,
'time_slot' => $cancha['time_slot'] ?? null,
]));
}
}
}
@endphp
@include('public.league._propose-context', ['proposeRounds' => $proposeRounds])
@endsection