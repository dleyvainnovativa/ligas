@php
$isPairs = $league->format === \App\Models\League::FORMAT_PAIRS;
@endphp

@if (empty($rows))
<p class="text-secondary mb-0">Sin participantes en este grupo.</p>
@else
{{-- DESKTOP table --}}
<div class="d-none d-md-block">
    <div class="table-responsive">
        <table class="table standings-table mb-0">
            <thead>
                <tr>
                    <th style="width:48px;">#</th>
                    <th>{{ $isPairs ? 'Pareja' : 'Jugador' }}</th>
                    <th class="text-center">PJ</th>
                    <th class="text-center">G</th>
                    @if ($isPairs)<th class="text-center">E</th>@endif
                    <th class="text-center">P</th>
                    <th class="text-center">Sets</th>
                    <th class="text-center">Games</th>
                    @unless ($isPairs)<th class="text-center" title="No shows">NS</th>@endunless
                    @unless ($isPairs)<th class="text-center" title="Suplentes">Sup</th>@endunless

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
                    @if ($isPairs)<td class="text-center text-secondary">{{ $row['draws'] ?? 0 }}</td>@endif
                    <td class="text-center text-danger">{{ $row['losses'] }}</td>
                    <td class="text-center">
                        {{ $row['sets_for'] }}–{{ $row['sets_against'] }}
                        <small class="text-secondary">({{ sprintf('%+d', $row['sets_diff']) }})</small>
                    </td>
                    <td class="text-center">
                        {{ $row['games_for'] }}–{{ $row['games_against'] }}
                        <small class="text-secondary">({{ sprintf('%+d', $row['games_diff']) }})</small>
                    </td>
                    @unless ($isPairs)
                    <td class="text-center">
                        {{ $row['no_shows'] }}
                    </td>
                    <td class="text-center">{{ $row['suplentes'] ?? 0 }}</td>

                    @endunless
                    <td class="text-end fw-bold">{{ $row['points'] }}</td>
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
                <span class="standings-card-points">{{ $row['points'] }}</span>
            </div>
            <div class="standings-card-stats">
                <span>PJ: <strong>{{ $row['played'] }}</strong></span>
                <span class="text-success">G: <strong>{{ $row['wins'] }}</strong></span>
                @if ($isPairs)<span class="text-secondary">E: <strong>{{ $row['draws'] ?? 0 }}</strong></span>@endif
                <span class="text-danger">P: <strong>{{ $row['losses'] }}</strong></span>
            </div>
            <div class="standings-card-stats small text-secondary mt-1">
                Sets {{ $row['sets_for'] }}–{{ $row['sets_against'] }} ·
                Games {{ $row['games_for'] }}–{{ $row['games_against'] }}
                @unless ($isPairs)
                @if (($row['no_shows'] ?? 0) > 0) · NS: {{ $row['no_shows'] }} @endif
                @if (($row['suplentes'] ?? 0) > 0) · Sup: {{ $row['suplentes'] }} @endif
                @endunless
            </div>
        </div>
    </div>
    @endforeach
</div>
@endif