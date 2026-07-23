{{-- Desktop: full table --}}
<div class="table-responsive d-none d-md-block">
    <table class="table public-standings-table">
        <thead>
            <tr>
                <th style="width:40px;">#</th>
                <th>Jugador</th>
                <th class="text-center">Cancha</th>
                <th class="text-center" title="Jornadas jugadas">Jornadas</th>
                <th class="text-center">Sets</th>
                <th class="text-center">Games</th>
                <th class="text-center" title="No shows">NS</th>
                <th class="text-center" title="Suplentes">Sup</th>
                <th class="text-end">Pts</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($standings as $i => $row)
            <tr>
                <td class="rank">{{ $i + 1 }}</td>
                <td>
                    <a href="{{ route('public.jugador', [$league->slug, $row['player_id']]) }}"
                        class="standings-player-link">
                        {{ $row['name'] }}
                    </a>
                </td>
                <td class="text-center">
                    @if ($row['current_position'])
                    <span class="cancha-pip">{{ $row['current_position'] }}</span>
                    @else
                    <span class="text-muted">—</span>
                    @endif
                </td>
                <td class="text-center font-mono">{{ $row['jornadas_played'] }}</td>
                <td class="text-center font-mono">{{ $row['rounds'] }}</td>
                <td class="text-center font-mono">
                    {{ $row['won'] }}–{{ $row['lost'] }}
                    <!-- @if (($row['penalty'] ?? 0) > 0)
                    <small class="text-danger d-block" style="font-size:10px;line-height:1;">
                        {{ $row['won_raw'] }} − {{ $row['penalty'] }}
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

{{-- Mobile: card list --}}
<div class="d-md-none d-flex flex-column gap-2">
    @foreach ($standings as $i => $row)
    <a href="{{ route('public.jugador', [$league->slug, $row['player_id']]) }}"
        class="public-standing-card">
        <div class="public-standing-rank">{{ $i + 1 }}</div>
        <div class="public-standing-info">
            <strong>{{ $row['name'] }}</strong>
            <small class="text-muted">
                @if ($row['current_position']) Cancha {{ $row['current_position'] }} · @endif
                {{ $row['rounds'] }} Sets · {{ $row['won'] }}–{{ $row['lost'] }} games
            </small>
            @if (($row['no_shows'] ?? 0) > 0 || ($row['suplentes'] ?? 0) > 0)
            <small class="standing-flags">
                @if (($row['no_shows'] ?? 0) > 0)
                <span class="flag-pill is-ns">NS {{ $row['no_shows'] }}</span>
                @endif
                @if (($row['suplentes'] ?? 0) > 0)
                <span class="flag-pill is-sup">Sup {{ $row['suplentes'] }}</span>
                @endif
                @if (($row['penalty'] ?? 0) > 0)
                <span class="flag-pill is-pen">−{{ $row['penalty'] }}</span>
                @endif
            </small>
            @endif
        </div>
        <div class="public-standing-points">
            <strong class="{{ $row['diff'] > 0 ? 'text-success' : ($row['diff'] < 0 ? 'text-danger' : '') }}">
                {{ $row['diff'] > 0 ? '+' : '' }}{{ $row['diff'] }}
            </strong>
            <small>pts</small>
        </div>
    </a>
    @endforeach
</div>