@php
$isPairs = $league->format === \App\Models\League::FORMAT_PAIRS;
@endphp

@if (empty($rows))
<p class="text-secondary mb-0">Sin participantes en este grupo.</p>
@else

{{-- ============ INDIVIDUAL (games-based, same data as public) ============ --}}
@unless ($isPairs)
<div class="d-none d-md-block">
    <div class="table-responsive">
        <table class="table standings-table mb-0">
            <thead>
                <tr>
                    <th style="width:48px;">#</th>
                    <th>Jugador</th>
                    <th class="text-center">Cancha</th>
                    <th class="text-center" title="Jornadas jugadas">Jornadas</th>
                    <th class="text-center" title="Sets/rondas ganadas">Sets</th>
                    <th class="text-center">Games</th>
                    <th class="text-center" title="No shows">NS</th>
                    <th class="text-center" title="Suplentes">Sup</th>
                    <th class="text-end">Pts</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($rows as $row)
                <tr class="@if($row['rank'] <= 3) is-top @endif">
                    <td class="rank-cell">{{ $row['rank'] }}</td>
                    <td class="fw-medium">{{ $row['name'] }}</td>
                    <td class="text-center">
                        @if ($row['current_position'])
                        <span class="cancha-pip">{{ $row['current_position'] }}</span>
                        @else
                        <span class="text-muted">—</span>
                        @endif
                    </td>
                    <td class="text-center font-mono">{{ $row['jornadas_played'] }}</td>
                    <td class="text-center font-mono">{{ $row['rounds'] ?? 0 }}</td>
                    <td class="text-center font-mono">
                        {{ $row['won'] }}–{{ $row['lost'] }}
                        <!-- @if (($row['penalty'] ?? 0) > 0)
                        <small class="text-danger d-block" style="font-size:10px;line-height:1;">
                            −{{ $row['penalty'] }} pen.
                        </small>
                        @endif -->
                    </td>
                    <td class="text-center font-mono {{ ($row['no_shows'] ?? 0) > 0 ? 'text-danger' : 'text-muted' }}">
                        {{ $row['no_shows'] ?? 0 }}
                    </td>
                    <td class="text-center font-mono {{ ($row['suplentes'] ?? 0) > 0 ? 'text-warning' : 'text-muted' }}">
                        {{ $row['suplentes'] ?? 0 }}
                    </td>
                    <td class="text-end font-mono fw-bold {{ $row['diff'] > 0 ? 'text-success' : ($row['diff'] < 0 ? 'text-danger' : '') }}">
                        {{ $row['diff'] > 0 ? '+' : '' }}{{ $row['diff'] }}
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>

{{-- MOBILE cards — individual --}}
<div class="d-md-none d-flex flex-column gap-2">
    @foreach ($rows as $row)
    <div class="standings-card @if($row['rank'] <= 3) is-top @endif">
        <div class="standings-card-rank">{{ $row['rank'] }}</div>
        <div class="standings-card-body">
            <div class="d-flex justify-content-between align-items-start">
                <strong>{{ $row['name'] }}</strong>
                <span class="standings-card-points {{ $row['diff'] > 0 ? 'text-success' : ($row['diff'] < 0 ? 'text-danger' : '') }}">
                    {{ $row['diff'] > 0 ? '+' : '' }}{{ $row['diff'] }}
                </span>
            </div>
            <div class="standings-card-stats small text-secondary mt-1">
                @if ($row['current_position']) Cancha {{ $row['current_position'] }} · @endif
                {{ $row['jornadas_played'] }} jornadas ·
                {{ $row['rounds'] ?? 0 }} sets ·
                {{ $row['won'] }}–{{ $row['lost'] }} games
            </div>
            @if (($row['no_shows'] ?? 0) > 0 || ($row['suplentes'] ?? 0) > 0)
            <div class="standing-flags mt-1">
                @if (($row['no_shows'] ?? 0) > 0)
                <span class="flag-pill is-ns">NS {{ $row['no_shows'] }}</span>
                @endif
                @if (($row['suplentes'] ?? 0) > 0)
                <span class="flag-pill is-sup">Sup {{ $row['suplentes'] }}</span>
                @endif
                @if (($row['penalty'] ?? 0) > 0)
                <span class="flag-pill is-pen">−{{ $row['penalty'] }}</span>
                @endif
            </div>
            @endif
        </div>
    </div>
    @endforeach
</div>
@endunless

{{-- ============ PAIRS (classic points system, unchanged) ============ --}}
@if ($isPairs)
<div class="d-none d-md-block">
    <div class="table-responsive">
        <table class="table standings-table mb-0">
            <thead>
                <tr>
                    <th style="width:48px;">#</th>
                    <th>Pareja</th>
                    <th class="text-center">PJ</th>
                    <th class="text-center">G</th>
                    <th class="text-center">E</th>
                    <th class="text-center">P</th>
                    <th class="text-center">Sets</th>
                    <th class="text-center">Games</th>
                    <th class="text-end">Pts</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($rows as $row)
                <tr class="@if($row['rank'] <= 3) is-top @endif">
                    <td class="rank-cell">{{ $row['rank'] }}</td>
                    <td class="fw-medium">{{ $row['name'] }}</td>
                    <td class="text-center">{{ $row['played'] }}</td>
                    <td class="text-center text-success">{{ $row['wins'] }}</td>
                    <td class="text-center text-secondary">{{ $row['draws'] ?? 0 }}</td>
                    <td class="text-center text-danger">{{ $row['losses'] }}</td>
                    <td class="text-center">
                        {{ $row['sets_for'] }}–{{ $row['sets_against'] }}
                        <small class="text-secondary">({{ sprintf('%+d', $row['sets_diff']) }})</small>
                    </td>
                    <td class="text-center">
                        {{ $row['games_for'] }}–{{ $row['games_against'] }}
                        <small class="text-secondary">({{ sprintf('%+d', $row['games_diff']) }})</small>
                    </td>
                    <td class="text-end fw-bold">{{ $row['points'] }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>

<div class="d-md-none d-flex flex-column gap-2">
    @foreach ($rows as $row)
    <div class="standings-card @if($row['rank'] <= 3) is-top @endif">
        <div class="standings-card-rank">{{ $row['rank'] }}</div>
        <div class="standings-card-body">
            <div class="d-flex justify-content-between align-items-start">
                <strong>{{ $row['name'] }}</strong>
                <span class="standings-card-points">{{ $row['points'] }}</span>
            </div>
            <div class="standings-card-stats">
                <span>PJ: <strong>{{ $row['played'] }}</strong></span>
                <span class="text-success">G: <strong>{{ $row['wins'] }}</strong></span>
                <span class="text-secondary">E: <strong>{{ $row['draws'] ?? 0 }}</strong></span>
                <span class="text-danger">P: <strong>{{ $row['losses'] }}</strong></span>
            </div>
            <div class="standings-card-stats small text-secondary mt-1">
                Sets {{ $row['sets_for'] }}–{{ $row['sets_against'] }} ·
                Games {{ $row['games_for'] }}–{{ $row['games_against'] }}
            </div>
        </div>
    </div>
    @endforeach
</div>
@endif

@endif