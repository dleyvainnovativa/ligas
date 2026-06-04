@php
/** @var \App\Models\League $league */
/** @var string $mode 'individual' | 'pairs' */
$isPairs = $mode === 'pairs';

// Build current-group lookup
$playerGroupId = collect();
$pairGroupId = collect();
foreach ($league->groups as $g) {
foreach ($g->players as $p) $playerGroupId[$p->id] = $g->id;
foreach ($g->pairs as $pr) $pairGroupId[$pr->id] = $g->id;
}

// Unassigned roster
$unassignedPlayers = $isPairs ? collect() : $league->players->filter(fn ($p) => !$playerGroupId->has($p->id));
$unassignedPairs = $isPairs ? $league->pairs->filter(fn ($pr) => !$pairGroupId->has($pr->id)) : collect();
@endphp

<div class="groups-app"
    data-league-id="{{ $league->id }}"
    data-mode="{{ $mode }}"
    data-unassigned-count="{{ $isPairs ? $unassignedPairs->count() : $unassignedPlayers->count() }}">

    <div class="row g-3">
        {{-- Sidebar: unassigned --}}
        <div class="col-lg-4">
            <div class="card-soft p-3 h-100">
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <h6 class="mb-0">
                        <i class="fa-solid fa-inbox me-1 text-secondary"></i>
                        {{ $isPairs ? 'Parejas sin grupo' : 'Jugadores sin grupo' }}
                    </h6>
                    <span class="badge text-bg-secondary unassigned-count">
                        {{ $isPairs ? $unassignedPairs->count() : $unassignedPlayers->count() }}
                    </span>
                </div>

                <div class="roster-list" data-group-id="0">
                    @if ($isPairs)
                    @foreach ($unassignedPairs as $pair)
                    @include('leagues.groups._pair-chip', ['pair' => $pair])
                    @endforeach
                    @else
                    @foreach ($unassignedPlayers as $player)
                    @include('leagues.groups._player-chip', ['player' => $player])
                    @endforeach
                    @endif
                </div>

                @if (!$isPairs && $league->players->isEmpty())
                <div class="empty-state py-3 my-3">
                    <div class="empty-state-icon">
                        <i class="fa-solid fa-users"></i>
                    </div>
                    <p class="small mb-0">Agrega jugadores primero en la pestaña Jugadores.</p>
                </div>
                @endif
            </div>
        </div>

        {{-- Groups --}}
        <div class="col-lg-8">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h6 class="mb-0">Grupos</h6>
                <button class="btn btn-primary btn-sm" id="add-group-btn">
                    <i class="fa-solid fa-plus me-1"></i> Nuevo grupo
                </button>
            </div>

            <div id="groups-container" class="d-flex flex-column gap-3">
                @forelse ($league->groups as $group)
                @include('leagues.groups._group-card', ['group' => $group, 'mode' => $mode, 'league' => $league])
                @empty
                <div class="card-soft empty-state py-5" id="groups-empty">
                    <div class="empty-state-icon">
                        <i class="fa-solid fa-layer-group"></i>
                    </div>
                    <h6>Aún no hay grupos</h6>
                    <p class="small mb-0">Crea un grupo para empezar a organizar tu liga.</p>
                </div>
                @endforelse
            </div>
        </div>
    </div>
</div>
{{-- Chip-to-group picker --}}
<div id="chip-picker" class="cell-picker" role="dialog" aria-hidden="true" aria-modal="false">
    <div class="cell-picker-backdrop" data-action="close-chip-picker"></div>
    <div class="cell-picker-panel">
        <div class="cell-picker-handle" aria-hidden="true"></div>
        <header class="cell-picker-header">
            <div class="flex-grow-1 min-w-0">
                <div class="cell-picker-eyebrow" id="chip-picker-eyebrow">—</div>
                <h6 class="cell-picker-title mb-0" id="chip-picker-title">—</h6>
            </div>
            <button type="button" class="btn-icon btn-sm" data-action="close-chip-picker" aria-label="Cerrar">
                <i class="fa-solid fa-xmark"></i>
            </button>
        </header>
        <div class="cell-picker-body" id="chip-picker-body"></div>
    </div>
</div>