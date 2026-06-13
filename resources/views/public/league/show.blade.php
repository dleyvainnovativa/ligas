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
        <div class="public-stat-value">{{ $payload['stats']['groups'] }}</div>
        <div class="public-stat-label">Divisiones</div>
    </div>
    <div class="public-stat">
        <div class="public-stat-value">{{ $payload['current_jornada'] ?? '—' }}</div>
        <div class="public-stat-label">Jornada actual</div>
    </div>
    <div class="public-stat">
        <div class="public-stat-value">{{ $payload['stats']['completion_pct'] }}%</div>
        <div class="public-stat-label">Avance</div>
    </div>
</section>

{{-- Ads carousel --}}
@if ($league->activeAds->isNotEmpty())
@include('public.league._ads-carousel', ['ads' => $league->activeAds])
@endif

{{-- Current jornada --}}
@if ($payload['current_jornada'])
<section class="public-section">
    <div class="public-section-head">
        <h2>Jornada {{ $payload['current_jornada'] }}</h2>
        <a href="{{ route('public.jornada', [$league->slug, $payload['current_jornada']]) }}" class="public-section-link">
            Ver detalle →
        </a>
    </div>

    @foreach ($payload['groups'] as $g)
    <div class="public-group-block">
        <h6 class="public-group-name">
            <i class="fa-solid fa-layer-group text-muted"></i>
            {{ $g['group']->name }}
        </h6>

        @if (empty($g['canchas']))
        <div class="public-empty">Aún no hay canchas en esta jornada.</div>
        @else
        @foreach ($g['canchas'] as $cancha)
        @include('public.league._cancha-card', ['m' => $cancha, 'showScore' => $cancha['status'] === 'completed'])
        @endforeach
        @endif
    </div>
    @endforeach
</section>
@else
<section class="public-section">
    <div class="public-empty-big">
        <i class="fa-solid fa-circle-check"></i>
        <p class="mb-0">Esta liga {{ $league->status === 'completed' ? 'ha terminado' : 'aún no comienza' }}.</p>
    </div>
</section>
@endif

{{-- Mini standings --}}
<section class="public-section">
    <div class="public-section-head">
        <h2>Top 3 por división</h2>
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
@endsection

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