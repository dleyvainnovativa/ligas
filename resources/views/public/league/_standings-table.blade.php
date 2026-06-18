{{-- Desktop: full table --}}
<div class="table-responsive d-none d-md-block">
    <table class="table public-standings-table">
        <thead>
            <tr>
                <th style="width:40px;">#</th>
                <th>Jugador</th>
                <th class="text-center">Cancha</th>
                <th class="text-end">PJ</th>
                <th class="text-end">Ganados</th>
                <th class="text-end">Perdidos</th>
                <th class="text-end">Dif.</th>
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
                <td class="text-end font-mono">{{ $row['jornadas_played'] }}</td>
                <td class="text-end font-mono fw-bold">{{ $row['won'] }}</td>
                <td class="text-end font-mono text-muted">{{ $row['lost'] }}</td>
                <td class="text-end font-mono {{ $row['diff'] > 0 ? 'text-success' : ($row['diff'] < 0 ? 'text-danger' : 'text-muted') }}">
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
                {{ $row['jornadas_played'] }} jornadas · {{ $row['won'] }}G / {{ $row['lost'] }}P
            </small>
        </div>
        <div class="public-standing-points">
            <strong class="{{ $row['diff'] > 0 ? 'text-success' : ($row['diff'] < 0 ? 'text-danger' : '') }}">
                {{ $row['diff'] > 0 ? '+' : '' }}{{ $row['diff'] }}
            </strong>
            <small>dif</small>
        </div>
    </a>
    @endforeach
</div>