@extends('layouts.public')
@section('title', "{$payload['name']} — {$league->name}")

@section('content')
<section class="public-section">
    <a href="{{ route('public.jugadores', $league->slug) }}" class="public-back-link">
        <i class="fa-solid fa-arrow-left"></i> Jugadores
    </a>

    {{-- Header --}}
    <div class="player-profile-head">
        <div class="player-profile-avatar">{{ \Illuminate\Support\Str::substr($payload['name'], 0, 1) }}</div>
        <div>
            <h2 class="mb-1">{{ $payload['name'] }}</h2>
            @if ($payload['group_name'])
            <span class="badge text-bg-secondary">{{ $payload['group_name'] }}</span>
            @endif
            @if ($payload['totals']['current_position'])
            <span class="badge text-bg-light">
                Cancha actual: {{ $payload['totals']['current_position'] }}
            </span>
            @endif
        </div>
    </div>

    {{-- Summary stats --}}
    <div class="player-stats-grid">
        <div class="player-stat">
            <div class="player-stat-value">{{ $payload['totals']['jornadas_played'] }}</div>
            <div class="player-stat-label">Jornadas</div>
        </div>
        <div class="player-stat">
            <div class="player-stat-value">{{ $payload['totals']['won'] }}</div>
            <div class="player-stat-label">Juegos ganados</div>
        </div>
        <div class="player-stat">
            <div class="player-stat-value {{ $payload['totals']['diff'] > 0 ? 'text-success' : ($payload['totals']['diff'] < 0 ? 'text-danger' : '') }}">
                {{ $payload['totals']['diff'] > 0 ? '+' : '' }}{{ $payload['totals']['diff'] }}
            </div>
            <div class="player-stat-label">Diferencia</div>
        </div>
        <div class="player-stat">
            <div class="player-stat-value">{{ $payload['totals']['best_position'] ?? '—' }}</div>
            <div class="player-stat-label">Mejor cancha</div>
        </div>
    </div>
</section>

{{-- Cancha journey --}}
@if (count($payload['history']) > 0)
<section class="public-section">
    <h2 class="mb-3">Recorrido por canchas</h2>
    @include('public.league._player-journey', ['history' => $payload['history']])
</section>

{{-- Per-jornada table --}}
<section class="public-section">
    <h2 class="mb-3">Detalle por jornada</h2>
    <div class="table-responsive">
        <table class="table player-history-table">
            <thead>
                <tr>
                    <th>Jornada</th>
                    <th>Cancha</th>
                    <th class="text-end">Pos.</th>
                    <th class="text-end">Ganados</th>
                    <th class="text-end">Dif.</th>
                    <th class="text-center">Mov.</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($payload['history'] as $h)
                <tr>
                    <td><strong>J{{ $h['jornada'] }}</strong></td>
                    <td>{{ $h['cancha_label'] }}</td>
                    <td class="text-end font-mono">{{ $h['rank'] }}°</td>
                    <td class="text-end font-mono">{{ $h['won'] }}</td>
                    <td class="text-end font-mono {{ $h['diff'] > 0 ? 'text-success' : ($h['diff'] < 0 ? 'text-danger' : 'text-muted') }}">
                        {{ $h['diff'] > 0 ? '+' : '' }}{{ $h['diff'] }}
                    </td>
                    <td class="text-center">
                        @if ($h['movement'] === 'up')
                        <span class="jstand-move up"><i class="fa-solid fa-arrow-up"></i></span>
                        @elseif ($h['movement'] === 'down')
                        <span class="jstand-move down"><i class="fa-solid fa-arrow-down"></i></span>
                        @elseif ($h['movement'] === 'stay')
                        <span class="jstand-move stay"><i class="fa-solid fa-minus"></i></span>
                        @else
                        <span class="text-muted small">—</span>
                        @endif
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</section>
@else
<section class="public-section">
    <div class="public-empty-big">
        <i class="fa-solid fa-chart-line"></i>
        <p class="mb-0">Este jugador aún no tiene jornadas registradas.</p>
    </div>
</section>
@endif
@endsection