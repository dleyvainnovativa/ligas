<section class="public-section">
    <div class="public-section-header">
        <h2 class="public-section-title">
            <i class="fa-solid fa-ranking-star"></i> Tabla de posiciones
        </h2>
    </div>

    @if (empty($rows))
    <div class="public-section-empty">Sin participantes en este grupo.</div>
    @else
    <div class="public-section-body p-0">
        <div class="public-standings-list">
            @foreach ($rows as $row)
            @php $top = $row['rank'] <= 3 ? "is-top-{$row['rank']}" : '' @endphp
                <div class="public-standings-row {{ $top }}">
                <div class="public-rank">{{ $row['rank'] }}</div>
                <div class="public-name">
                    {{ $row['name'] }}
                    <span class="public-name-sub">
                        {{ $row['played'] }} PJ ·
                        {{ $row['wins'] }}G
                        @if (isset($row['draws']) && $row['draws'] > 0) · {{ $row['draws'] }}E @endif
                        · {{ $row['losses'] }}P
                        @if (($row['no_shows'] ?? 0) > 0)
                        · <span class="text-danger">{{ $row['no_shows'] }} NS</span>
                        @endif
                    </span>
                </div>
                <div class="public-points">
                    {{ $row['points'] }}
                    <span class="public-points-sub">
                        {{ sprintf('%+d', $row['sets_diff']) }} sets
                    </span>
                </div>
        </div>
        @endforeach
    </div>
    </div>
    @endif
</section>