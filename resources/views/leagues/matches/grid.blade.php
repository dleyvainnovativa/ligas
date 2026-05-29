@extends('layouts.app')

@section('title', "Programar — Jornada {$jornada->number}")
@section('page-title', $league->name)

@php
$scheduler = app(\App\Services\MatchSchedulingService::class);
$dates = $scheduler->enumerateDates($jornada);
$timeSlots = collect($league->time_slots ?? [])->sort()->values()->all();
$sedes = $league->sedes()->with('pistas')->orderBy('position')->get();

// Build maps of all matches keyed by (date|slot|pista_id)
$matchesByCell = [];
$unscheduledMatches = [];
$playerNames = $league->players()->pluck('full_name', 'id');

foreach ($jornada->canchas as $cancha) {
foreach ($cancha->matches as $match) {
if ($match->date && $match->time_slot && $match->pista_id) {
$key = $match->date->toDateString()."|".$match->time_slot."|".$match->pista_id;
$matchesByCell[$key][] = $match;
} else {
$unscheduledMatches[] = $match;
}
}
}

function matchLabel($match, $playerNames) {
$a = collect($match->team_a_player_ids)->map(fn ($id) => $playerNames[$id] ?? '?')->implode(' / ');
$b = collect($match->team_b_player_ids)->map(fn ($id) => $playerNames[$id] ?? '?')->implode(' / ');
return ['a' => $a, 'b' => $b];
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
    </div>
</div>

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
    data-league-id="{{ $league->id }}"
    data-group-id="{{ $group->id }}"
    data-jornada-id="{{ $jornada->id }}"
    data-mode="{{ $league->format }}"
    data-schedule-url-template="{{ url("/leagues/{$league->id}/groups/{$group->id}/jornadas/{$jornada->id}/matches/__ID__/schedule") }}"
    data-autofit-url-template="{{ url("/leagues/{$league->id}/groups/{$group->id}/jornadas/{$jornada->id}/canchas/__ID__/auto-fit") }}">

    <div class="row g-3 h-100">
        {{-- Sidebar: unscheduled canchas --}}
        <div class="col-lg-3 overflow-auto h-100">
            <div class="card-soft p-3" style="top:80px;">
                <h6 class="mb-3">
                    <i class="fa-solid fa-table-cells text-secondary me-1"></i>
                    Canchas por programar
                </h6>

                <div id="unscheduled-canchas" class="d-flex flex-column gap-2">
                    @foreach ($jornada->canchas as $cancha)
                    @php
                    $matchesInCancha = $cancha->matches;
                    $unscheduledCount = $matchesInCancha->filter(fn ($m) => !$m->date)->count();
                    @endphp
                    @if ($matchesInCancha->isNotEmpty())
                    <div class="cancha-chip @if($unscheduledCount === 0) is-done @endif"
                        data-cancha-id="{{ $cancha->id }}"
                        data-match-count="{{ $matchesInCancha->count() }}">
                        <div class="d-flex align-items-center gap-2 mb-1">
                            <i class="fa-solid fa-table-cells text-secondary"></i>
                            <strong class="small">{{ $cancha->label }}</strong>
                            <span class="badge text-bg-secondary ms-auto">
                                {{ $matchesInCancha->count() - $unscheduledCount }}/{{ $matchesInCancha->count() }}
                            </span>
                        </div>
                        @foreach ($matchesInCancha as $m)
                        @php $labels = matchLabel($m, $playerNames); @endphp
                        <div class="match-pill @if($m->date) is-scheduled @endif"
                            draggable="true"
                            data-match-id="{{ $m->id }}"
                            data-cancha-id="{{ $cancha->id }}"
                            data-rotation="{{ $m->rotation_index }}">
                            <div class="match-pill-rot">R{{ $m->rotation_index }}</div>
                            <div class="match-pill-teams">
                                <div>{{ $labels['a'] }}</div>
                                <div class="text-muted small">vs</div>
                                <div>{{ $labels['b'] }}</div>
                            </div>
                        </div>
                        @endforeach
                        @if ($league->format !== 'pairs' && $unscheduledCount > 0)
                        <small class="text-secondary d-block mt-1">
                            <i class="fa-solid fa-circle-info me-1"></i>
                            Arrastra una rotación al primer slot — se autocompletan 3 horarios.
                        </small>
                        @endif
                    </div>
                    @endif
                    @endforeach

                    @if ($jornada->canchas->flatMap->matches->isEmpty())
                    <div class="empty-state py-3">
                        <div class="empty-state-icon">
                            <i class="fa-solid fa-circle-info"></i>
                        </div>
                        <p class="small mb-0">Forma canchas en la jornada primero. Cada cancha llena genera sus partidos.</p>
                    </div>
                    @endif
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
                                    @foreach ($cellMatches as $m)
                                    @php $labels = matchLabel($m, $playerNames); @endphp
                                    <div class="cell-match @if($m->status === 'completed') is-completed @endif"
                                        draggable="true"
                                        data-match-id="{{ $m->id }}"
                                        data-cancha-id="{{ $m->cancha_id }}"
                                        data-rotation="{{ $m->rotation_index }}">
                                        <div class="cell-match-rot">R{{ $m->rotation_index }}</div>
                                        <div class="cell-match-teams">
                                            <div>{{ $labels['a'] }}</div>
                                            <div class="text-muted small">vs</div>
                                            <div>{{ $labels['b'] }}</div>
                                        </div>
                                        @if ($m->status === 'completed')
                                        @php $tally = $m->tally(); @endphp
                                        <div class="cell-match-score">
                                            <span class="@if($m->winner === 'a') fw-bold @endif">{{ $tally['sets_a'] }}</span>
                                            <span class="text-muted mx-1">·</span>
                                            <span class="@if($m->winner === 'b') fw-bold @endif">{{ $tally['sets_b'] }}</span>
                                        </div>
                                        @endif
                                        <button class="cell-match-result" title="Resultado" data-match-id="{{ $m->id }}">
                                            <i class="fa-solid fa-pencil"></i>
                                        </button>
                                        <button class="cell-match-clear" title="Quitar de la programación">
                                            <i class="fa-solid fa-xmark"></i>
                                        </button>
                                    </div>
                                    @endforeach
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
@endsection