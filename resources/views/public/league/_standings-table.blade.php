{{-- Desktop: full table --}}
<div class="table-responsive d-none d-md-block">
    <table class="table public-standings-table">
        <thead>
            <tr>
                <th style="width:40px;">#</th>
                <th>{{ $league->format === 'pairs' ? 'Pareja' : 'Jugador' }}</th>
                <th class="text-end">PJ</th>
                <th class="text-end">G</th>
                <th class="text-end">P</th>
                <th class="text-end">Sets G</th>
                <th class="text-end">Juegos G</th>
                <th class="text-end">Pts</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($standings as $i => $row)
            <tr>
                <td class="rank">{{ $i + 1 }}</td>
                <td><strong>{{ $row['name'] }}</strong></td>
                <td class="text-end font-mono">{{ $row['matches_played'] ?? 0 }}</td>
                <td class="text-end font-mono">{{ $row['matches_won']    ?? 0 }}</td>
                <td class="text-end font-mono">{{ $row['matches_lost']   ?? 0 }}</td>
                <td class="text-end font-mono">{{ $row['sets_won']       ?? 0 }}</td>
                <td class="text-end font-mono">{{ $row['games_won']      ?? 0 }}</td>
                <td class="text-end font-mono fw-bold">{{ $row['points'] ?? $row['games_won'] ?? 0 }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>
</div>

{{-- Mobile: card list --}}
<div class="d-md-none d-flex flex-column gap-2">
    @foreach ($standings as $i => $row)
    <div class="public-standing-card">
        <div class="public-standing-rank">{{ $i + 1 }}</div>
        <div class="public-standing-info">
            <strong>{{ $row['name'] }}</strong>
            <small class="text-muted">
                {{ $row['matches_played'] ?? 0 }} PJ ·
                {{ $row['matches_won'] ?? 0 }} G ·
                {{ $row['matches_lost'] ?? 0 }} P
                @if (($row['no_shows'] ?? 0) > 0)
                · <span class="text-danger">{{ $row['no_shows'] }} NS</span>
                @endif
                @if (($row['suplentes'] ?? 0) > 0)
                · <span class="text-danger">{{ $row['suplentes'] }} SP</span>
                @endif
            </small>
        </div>
        <div class="public-standing-points">
            <strong>{{ $row['points'] ?? $row['games_won'] ?? 0 }}</strong>
            <small>pts</small>
        </div>
    </div>
    @endforeach
</div>