@php
$tabs = [
['key' => 'settings', 'route' => route('leagues.edit', $league), 'label' => 'Configuración', 'icon' => 'fa-gear'],
['key' => 'players', 'route' => route('leagues.players.index', $league), 'label' => 'Jugadores', 'icon' => 'fa-users'],
['key' => 'groups', 'route' => route('leagues.jornadas.index', [$league, $league->groups()->first()]), 'label' => 'Jornadas', 'icon' => 'fa-layer-group'],
['key' => 'overview', 'route' => route('leagues.show', $league), 'label' => 'Resumen', 'icon' => 'fa-chart-line'],
['key' => 'standings', 'route' => route('leagues.standings.index', $league), 'label' => 'Standings', 'icon' => 'fa-ranking-star'],
['key' => 'ads', 'route' => route('leagues.ads.index', $league), 'label' => 'Anuncios', 'icon' => 'fa-rectangle-ad'],
];
$active = $active ?? 'settings';
@endphp
<div class="d-flex align-items-center gap-3 mb-3">

    <a href="{{ route('leagues.index') }}" class="btn btn-sm" title="Volver a ligas">
        <i class="fa-solid fa-arrow-left"></i> Regresar
    </a>
</div>
<div class="league-panel-header">
    <div class="panel-thumb {{ $league->banner_url ? 'has-image' : '' }}"
        @if($league->banner_url) style="background-image: url('{{ $league->banner_url }}');" @endif>
        @unless ($league->banner_url)
        <div class="d-flex align-items-center justify-content-center h-100">
            <i class="fa-solid fa-trophy text-muted"></i>
        </div>
        @endunless
    </div>
    <div class="flex-grow-1 min-w-0">
        <div class="d-flex align-items-center gap-2 mb-1 flex-wrap">
            <h5 class="text-truncate">{{ $league->name }}</h5>
            <span class="badge text-bg-secondary">
                {{ $league->format === 'pairs' ? 'Parejas' : 'Individual' }}
            </span>
            <span class="badge text-bg-{{ $league->status === 'active' ? 'success' : ($league->status === 'completed' ? 'info' : 'secondary') }}">
                {{ ucfirst($league->status) }}
            </span>
            @if (($pendingProposalsCount ?? 0) > 0)
            <span class="badge text-bg-warning d-inline-flex align-items-center gap-1">
                <i class="fa-solid fa-circle-exclamation"></i>
                {{ $pendingProposalsCount }} {{ Str::plural('propuesta', $pendingProposalsCount) }}
            </span>
            @endif

            @php
            $tiers = app(\App\Services\TierService::class);
            $leagueSnapshot = $tiers->leagueSnapshot($league);
            @endphp

            @foreach (['players' => 'Jugadores', 'jornadas' => 'Jornadas'] as $key => $label)
            @php
            $used = $leagueSnapshot[$key]['used'];
            $limit = $leagueSnapshot[$key]['limit'];
            $atLimit = $limit !== null && $used >= $limit;
            $nearLimit = $limit !== null && $used >= $limit * 0.8;
            @endphp
            <span class="usage-chip @if ($atLimit) is-at-limit @elseif ($nearLimit) is-near-limit @endif">
                <i class="fa-solid fa-{{ $key === 'players' ? 'users' : 'calendar-day' }}"></i>
                {{ $used }}@if ($limit !== null) / {{ $limit }}@endif
            </span>
            @endforeach
        </div>
        <div class="panel-meta">
            {{ $league->num_jornadas }} jornadas · ${{ number_format($league->cost, 0) }}
            · <a href="{{ url('/'.$league->slug) }}" target="_blank" class="text-muted text-decoration-none">
                /{{ $league->slug }} <i class="fa-solid fa-arrow-up-right-from-square" style="font-size:9px;"></i>
            </a>
        </div>
    </div>
</div>

<ul class="nav nav-pills league-panel-nav mb-4 flex-nowrap">
    @foreach ($tabs as $tab)
    <li class="nav-item">
        <a href="{{ $tab['route'] }}"
            class="nav-link {{ $active === $tab['key'] ? 'active' : '' }}">
            <i class="fa-solid {{ $tab['icon'] }} me-1"></i>
            <span>{{ $tab['label'] }}</span>
        </a>
    </li>
    @endforeach
</ul>