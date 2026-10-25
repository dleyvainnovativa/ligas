@php
$isPairs = $league->format === \App\Models\League::FORMAT_PAIRS;
$subjectLabel = $isPairs ? 'Pareja' : 'Jugador';
$hasExtra = collect($rows)->contains(fn($r) => ($r['extra_games'] ?? 0) != 0 || ($r['extra_sets'] ?? 0) != 0);
@endphp

@if (empty($rows))
<p class="text-secondary mb-0">Sin {{ $isPairs ? 'parejas' : 'participantes' }} en este grupo.</p>
@else

{{-- King-of-the-court leaderboard (games-based). Same layout for singles and
     doubles — in doubles each row is a pair that moves as a unit. --}}
<div class="d-none d-md-block">
    <div class="table-responsive">
        <table class="table standings-table mb-0">
            <thead>
                <tr>
                    <th style="width:48px;">#</th>
                    <th>{{ $subjectLabel }}</th>
                    <th class="text-center">Cancha</th>
                    <th class="text-center" title="Jornadas jugadas">Jornadas</th>
                    <th class="text-center" title="Sets/rondas ganadas">Sets</th>
                    <th class="text-center">Games</th>
                    <th class="text-center" title="No shows">NS</th>
                    <th class="text-center" title="Suplentes">Sup</th>
                    @if ($hasExtra)
                    <th class="text-center" title="Puntos extra (bono/corrección manual)">Extra</th>
                    @endif
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
                    <td class="text-center font-mono">{{ $row['won'] }}–{{ $row['lost'] }}</td>
                    <td class="text-center font-mono {{ ($row['no_shows'] ?? 0) > 0 ? 'text-danger' : 'text-muted' }}">
                        {{ $row['no_shows'] ?? 0 }}
                    </td>
                    <td class="text-center font-mono {{ ($row['suplentes'] ?? 0) > 0 ? 'text-warning' : 'text-muted' }}">
                        {{ $row['suplentes'] ?? 0 }}
                    </td>
                    @if ($hasExtra)
                    <td class="text-center">
                        @include('leagues.standings._extra-badges', ['row' => $row])
                    </td>
                    @endif
                    <td class="text-end font-mono fw-bold {{ $row['diff'] > 0 ? 'text-success' : ($row['diff'] < 0 ? 'text-danger' : '') }}">
                        {{ $row['diff'] > 0 ? '+' : '' }}{{ $row['diff'] }}
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>

{{-- MOBILE cards --}}
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
            @if (($row['extra_games'] ?? 0) != 0 || ($row['extra_sets'] ?? 0) != 0)
            <div class="standing-flags mt-1">
                <span class="small text-secondary me-1">Extra:</span>
                @include('leagues.standings._extra-badges', ['row' => $row])
            </div>
            @endif
        </div>
    </div>
    @endforeach
</div>

@endif
