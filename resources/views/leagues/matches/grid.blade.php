@extends('layouts.app')

@section('title', "Programar — Jornada {$jornada->number}")
@section('page-title', $league->name)

@php
$editable = $jornada->isEditable();
$scheduler = app(\App\Services\MatchSchedulingService::class);
$dates = $scheduler->enumerateDates($jornada);
$timeSlots = collect($league->time_slots ?? [])->sort()->values()->all();
$sedes = $league->sedes()->with('pistas')->orderBy('position')->get();
$playerNames = $league->players()->pluck('full_name', 'id');

// Map canchas by cell
$canchasByCell = [];
$unscheduledCanchas = [];
foreach ($jornada->canchas as $cancha) {
if ($cancha->date && $cancha->time_slot && $cancha->pista_id) {
$key = $cancha->date->toDateString()."|".$cancha->time_slot."|".$cancha->pista_id;
$canchasByCell[$key] = $cancha;
} else {
$unscheduledCanchas[] = $cancha;
}
}

function canchaLabel($cancha, $playerNames) {
$cancha->loadMissing(['players', 'pairs.playerA', 'pairs.playerB']);
if ($cancha->players->isNotEmpty()) {
return $cancha->players->map(fn ($p) => $playerNames[$p->id] ?? '?')->all();
}
return $cancha->pairs->flatMap(fn ($pair) => [
$pair->playerA?->full_name ?? '?',
$pair->playerB?->full_name ?? '?',
])->all();
}

function canchaCompletedCount($cancha) {
return $cancha->rounds->where('status', \App\Models\GameMatch::STATUS_COMPLETED)->count();
}
@endphp

@section('content')
@include('leagues.partials._panel-nav', ['active' => 'groups'])

<div class="d-flex align-items-center justify-content-between mb-3 flex-wrap gap-2">
    <div>
        <small class="text-secondary">
            <a href="{{ route('leagues.groups.index', $league) }}" class="text-decoration-none text-secondary">Grupos</a>
            /
            <a href="{{ route('leagues.jornadas.index', [$league, $group]) }}" class="text-decoration-none text-secondary">{{ $group->name }}</a>
            /
            <a href="{{ route('leagues.jornadas.show', [$league, $group, $jornada]) }}" class="text-decoration-none text-secondary">Jornada #{{ $jornada->number }}</a>
            / Programar
        </small>
        <h5 class="mb-0 mt-1">Calendario de partidos</h5>
    </div>
    <div class="d-flex gap-2 flex-wrap">
        <button class="btn btn-outline-warning btn-sm" id="check-conflicts-btn"
            data-url="{{ route('leagues.matches.conflicts', [$league, $group, $jornada]) }}">
            <i class="fa-solid fa-triangle-exclamation me-1"></i> Verificar conflictos
        </button>
        @if ($editable)
        <button class="btn btn-outline-primary btn-sm" id="auto-generate-btn"
            data-url="{{ route('leagues.matches.auto-generate', [$league, $group, $jornada]) }}">
            <i class="fa-solid fa-wand-magic-sparkles me-1"></i> Auto-generar
        </button>
        @endif
    </div>
</div>

@unless ($editable)
<div class="alert alert-secondary d-flex align-items-center gap-2 mb-3">
    <i class="fa-solid fa-lock"></i>
    <div>
        Esta jornada está bloqueada porque existe una jornada posterior.
        El calendario y los resultados son de solo lectura. Para editar, elimina primero las jornadas siguientes.
    </div>
</div>
@endunless

@if (empty($timeSlots) || $sedes->isEmpty() || count($dates) === 0)
<div class="card-soft p-4">
    <div class="alert alert-warning mb-0">
        <strong>Falta configuración.</strong>
        Para programar partidos necesitas:
        <ul class="mb-0 mt-2">
            @if (empty($timeSlots)) <li>Horarios definidos en la configuración de la liga.</li> @endif
            @if ($sedes->isEmpty()) <li>Al menos una sede con pistas.</li> @endif
            @if (count($dates) === 0) <li>Días de la semana definidos en la liga (y opcionalmente una ventana en la jornada).</li> @endif
        </ul>
    </div>
</div>
@else

<div class="grid-app"
    data-readonly="{{ $editable ? '0' : '1' }}"
    data-league-id="{{ $league->id }}"
    data-group-id="{{ $group->id }}"
    data-jornada-id="{{ $jornada->id }}"
    data-mode="{{ $league->format }}"
    data-schedule-url-template="{{ url("/leagues/{$league->id}/groups/{$group->id}/jornadas/{$jornada->id}/canchas/__ID__/schedule") }}"
    data-autofit-url-template="{{ url("/leagues/{$league->id}/groups/{$group->id}/jornadas/{$jornada->id}/canchas/__ID__/auto-fit") }}">

    <div class="row g-3 h-100">
        {{-- Sidebar: unscheduled canchas --}}
        <div class="col-lg-3" style="max-height: 100vh !important;overflow-y: auto;">
            <div class="card-soft p-3 grid-sidebar">
                {{-- Mobile-only summary header to toggle --}}
                <button type="button"
                    class="grid-sidebar-toggle d-lg-none"
                    data-bs-toggle="collapse"
                    data-bs-target="#grid-sidebar-body"
                    aria-expanded="false">
                    <span>
                        <i class="fa-solid fa-table-cells text-secondary me-1"></i>
                        Canchas por programar
                    </span>
                    <i class="fa-solid fa-chevron-down toggle-chevron"></i>
                </button>

                <h6 class="mb-3 d-none d-lg-block">
                    <i class="fa-solid fa-table-cells text-secondary me-1"></i>
                    Canchas por programar
                </h6>

                <div class="collapse d-lg-block" id="grid-sidebar-body">
                    <div id="unscheduled-canchas" class="d-flex flex-column gap-2">
                        @foreach ($jornada->canchas as $cancha)
                        @php
                        $isScheduled = $cancha->date && $cancha->time_slot && $cancha->pista_id;
                        $players = canchaLabel($cancha, $playerNames);
                        @endphp
                        <div class="cancha-chip @if($isScheduled) is-done @endif"
                            data-cancha-id="{{ $cancha->id }}">
                            <div class="d-flex align-items-center gap-2 mb-1">
                                <i class="fa-solid fa-table-cells text-secondary"></i>
                                <strong class="small">{{ $cancha->label }}</strong>
                                <span class="badge text-bg-{{ $isScheduled ? 'success' : 'secondary' }} ms-auto">
                                    @if ($isScheduled)
                                    Programada
                                    @else
                                    Pendiente
                                    @endif
                                </span>
                            </div>

                            <div class="cancha-pill @if($isScheduled) is-scheduled @endif"
                                @if($editable) draggable="true" @endif
                                data-cancha-id="{{ $cancha->id }}">
                                <div class="cancha-pill-players">
                                    @foreach ($players as $name)
                                    <div>{{ $name }}</div>
                                    @endforeach
                                </div>
                                <div class="cancha-pill-meta text-muted small">
                                    {{ $cancha->rounds->count() }} {{ $cancha->rounds->count() === 1 ? 'ronda' : 'rondas' }}
                                    @if ($cancha->rounds->count() > 0)
                                    · {{ canchaCompletedCount($cancha) }} completadas
                                    @endif
                                </div>
                            </div>
                        </div>
                        @endforeach

                        @if ($jornada->canchas->isEmpty())
                        <div class="empty-state py-3">
                            <div class="empty-state-icon"><i class="fa-solid fa-circle-info"></i></div>
                            <p class="small mb-0">Forma canchas en la jornada primero.</p>
                        </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>

        {{-- Grid --}}
        <div class="col-lg-9 h-100 overflow-auto">
            <div class="card-soft p-0">
                <div class="schedule-grid-wrap">
                    <table class="schedule-grid">
                        <thead>
                            <tr>
                                <th class="row-header-cell">Sede / Pista / Hora</th>
                                @foreach ($dates as $d)
                                <th class="date-header-cell">
                                    <div class="small text-secondary">{{ ['Lun','Mar','Mié','Jue','Vie','Sáb','Dom'][$d->dayOfWeekIso - 1] }}</div>
                                    <div>{{ $d->format('d/m') }}</div>
                                </th>
                                @endforeach
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($sedes as $sede)
                            <tr class="sede-divider-row">
                                <td colspan="{{ count($dates) + 1 }}">
                                    <i class="fa-solid fa-map-location-dot me-1"></i> {{ $sede->name }}
                                </td>
                            </tr>
                            @foreach ($sede->pistas as $pista)
                            @foreach ($timeSlots as $slot)
                            <tr>
                                <td class="row-header-cell">
                                    <div class="pista-name">{{ $pista->name }}</div>
                                    <div class="slot-time">{{ $slot }}</div>
                                </td>
                                @foreach ($dates as $d)
                                @php
                                $key = $d->toDateString()."|{$slot}|{$pista->id}";
                                $cellMatches = $matchesByCell[$key] ?? [];
                                @endphp
                                <td class="grid-cell"
                                    data-date="{{ $d->toDateString() }}"
                                    data-slot="{{ $slot }}"
                                    data-pista-id="{{ $pista->id }}">
                                    @php
                                    $key = $d->toDateString()."|{$slot}|{$pista->id}";
                                    $cellCancha = $canchasByCell[$key] ?? null;
                                    @endphp
                                    @if ($cellCancha)
                                    @php
                                    $players = canchaLabel($cellCancha, $playerNames);
                                    $completedCount = canchaCompletedCount($cellCancha);
                                    $totalRounds = $cellCancha->rounds->count();
                                    $allDone = $totalRounds > 0 && $completedCount === $totalRounds;
                                    @endphp
                                    <div class="cell-cancha @if($allDone) is-completed @endif"
                                        @if($editable) draggable="true" @endif
                                        data-cancha-id="{{ $cellCancha->id }}">
                                        <div class="cell-cancha-label">{{ $cellCancha->label }}</div>
                                        <div class="cell-cancha-players">
                                            @foreach ($players as $name)
                                            <div>{{ $name }}</div>
                                            @endforeach
                                        </div>
                                        <div class="cell-cancha-rounds">
                                            @foreach ($cellCancha->rounds as $round)
                                            @php $t = $round->status === 'completed' ? $round->tally() : null; @endphp
                                            <span class="round-pill {{ $round->status }}"
                                                @if($t) title="S{{ $round->rotation_index }}: {{ $t['games_a'] }}–{{ $t['games_b'] }}" @endif>
                                                S{{ $round->rotation_index }}
                                                @if ($t)
                                                <span class="round-pill-score">{{ $t['sets_a'] }}-{{ $t['sets_b'] }}</span>
                                                @endif
                                            </span>
                                            @endforeach
                                        </div>
                                        @if ($editable)
                                        <button class="cell-cancha-result" title="Resultado" data-cancha-id="{{ $cellCancha->id }}">
                                            <i class="fa-solid fa-pencil"></i>
                                        </button>
                                        <button class="cell-cancha-clear" title="Quitar de la programación">
                                            <i class="fa-solid fa-xmark"></i>
                                        </button>
                                        @endif
                                    </div>
                                    @endif
                                </td>
                                @endforeach
                            </tr>
                            @endforeach
                            @endforeach
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- Tap-to-place picker (rendered as bottom sheet on mobile, popover on desktop) --}}
<div id="cell-picker" class="cell-picker" role="dialog" aria-hidden="true" aria-modal="false">
    <div class="cell-picker-backdrop" data-action="close-picker"></div>
    <div class="cell-picker-panel">
        <div class="cell-picker-handle" aria-hidden="true"></div>
        <header class="cell-picker-header">
            <div class="flex-grow-1 min-w-0">
                <div class="cell-picker-eyebrow" id="picker-eyebrow">—</div>
                <h6 class="cell-picker-title mb-0" id="picker-title">Asignar partido</h6>
            </div>
            <button type="button" class="btn-icon btn-sm" data-action="close-picker" aria-label="Cerrar">
                <i class="fa-solid fa-xmark"></i>
            </button>
        </header>

        <div class="cell-picker-body" id="picker-body">
            {{-- filled by JS --}}
        </div>
    </div>
</div>

{{-- First-time hint --}}
<div id="picker-hint" class="picker-hint" hidden>
    <i class="fa-solid fa-hand-pointer"></i>
    <span>Toca cualquier celda para asignar un partido.</span>
    <button type="button" class="btn-icon btn-sm" data-action="dismiss-hint" aria-label="Entendido">
        <i class="fa-solid fa-xmark"></i>
    </button>
</div>
@endif

{{-- Conflicts modal --}}
<div class="modal fade" id="result-modal" tabindex="-1">
    <div class="modal-dialog modal-lg modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Resultado del partido</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="result-modal-body">
                <div class="text-center py-4 text-secondary">
                    <span class="spinner-border spinner-border-sm me-2"></span>Cargando…
                </div>
            </div>
            <div class="modal-footer">
                <button class="btn btn-outline-danger me-auto" id="result-clear-btn">
                    <i class="fa-solid fa-rotate-left me-1"></i> Borrar resultado
                </button>
                <button class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancelar</button>
                <button class="btn btn-primary" id="result-save-btn">
                    <i class="fa-solid fa-floppy-disk me-1"></i> Guardar
                </button>
            </div>
        </div>
    </div>
</div>


{{-- Conflicts modal --}}
<div class="modal fade" id="conflicts-modal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Conflictos detectados</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div id="conflicts-body"></div>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="auto-generate-modal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Auto-generar calendario</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p>
                    Se asignarán automáticamente todos los partidos pendientes a horarios
                    disponibles, evitando conflictos de jugadores y respetando que cada cancha
                    juegue sus rotaciones en horarios consecutivos en la misma pista
                    (modo individual).
                </p>

                <div class="form-check mb-3">
                    <input class="form-check-input" type="checkbox" id="ag-clear-existing">
                    <label class="form-check-label" for="ag-clear-existing">
                        Borrar la programación actual antes de generar
                    </label>
                    <div class="form-text">
                        Si lo dejas sin marcar, los partidos ya programados manualmente
                        se mantienen y solo se asignan los pendientes.
                    </div>
                </div>

                <div class="alert alert-info mb-0 small d-flex gap-2">
                    <i class="fa-solid fa-circle-info mt-1"></i>
                    <div>
                        El algoritmo es aleatorio. Si el resultado no te gusta, puedes
                        correrlo de nuevo o ajustar manualmente después.
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancelar</button>
                <button class="btn btn-primary" id="ag-confirm-btn">
                    <i class="fa-solid fa-wand-magic-sparkles me-1"></i> Generar
                </button>
            </div>
        </div>
    </div>
</div>
@endsection