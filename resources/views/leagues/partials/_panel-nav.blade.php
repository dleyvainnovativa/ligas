@php
$tabs = [
['key' => 'overview', 'route' => route('leagues.show', $league), 'label' => 'Resumen', 'icon' => 'fa-chart-line'],
['key' => 'players', 'route' => route('leagues.players.index', $league), 'label' => 'Jugadores', 'icon' => 'fa-users'],
['key' => 'groups', 'route' => route('leagues.groups.index', $league), 'label' => 'Grupos', 'icon' => 'fa-layer-group'],
['key' => 'standings', 'route' => route('leagues.standings.index', $league), 'label' => 'Standings', 'icon' => 'fa-ranking-star'],
['key' => 'settings', 'route' => route('leagues.edit', $league), 'label' => 'Configuración', 'icon' => 'fa-gear'],
];
$active = $active ?? 'overview';
@endphp
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
        </div>
        <div class="panel-meta">
            {{ $league->num_jornadas }} jornadas · ${{ number_format($league->cost, 0) }}
            · <a href="{{ url('/'.$league->slug) }}" target="_blank" class="text-muted text-decoration-none">
                /{{ $league->slug }} <i class="fa-solid fa-arrow-up-right-from-square" style="font-size:9px;"></i>
            </a>
        </div>
    </div>
    <a href="{{ route('leagues.index') }}" class="btn btn-icon btn-sm" title="Volver a ligas">
        <i class="fa-solid fa-arrow-left"></i>
    </a>
</div>

<ul class="nav nav-tabs panel-tabs mb-4" role="tablist">
    @foreach ($tabs as $tab)
    <li class="nav-item">
        <a class="nav-link {{ $active === $tab['key'] ? 'active' : '' }}" href="{{ $tab['route'] }}">
            <i class="fa-solid {{ $tab['icon'] }} me-2"></i>{{ $tab['label'] }}
        </a>
    </li>
    @endforeach
</ul>