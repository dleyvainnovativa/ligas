@extends('layouts.app')

@section('title', "Jornada {$jornada->number} — {$group->name}")
@section('page-title', $league->name)

@php
$isPairs = $league->format === \App\Models\League::FORMAT_PAIRS;

// Build lookup of "who's already in some cancha of this jornada"
$assignedPlayerIds = collect();
$assignedPairIds = collect();
foreach ($jornada->canchas as $c) {
foreach ($c->players as $p) $assignedPlayerIds->push($p->id);
foreach ($c->pairs as $pr) $assignedPairIds->push($pr->id);
}

$availablePlayers = $isPairs ? collect() : $group->players()->get()->reject(fn ($p) => $assignedPlayerIds->contains($p->id))->values();
$availablePairs = $isPairs ? $group->pairs()->get()->reject(fn ($pr) => $assignedPairIds->contains($pr->id))->values() : collect();
@endphp

@section('content')
@include('leagues.partials._panel-nav', ['active' => 'groups'])

<div class="d-flex align-items-center justify-content-between mb-3 flex-wrap gap-2">
    <div>
        <small class="text-secondary">
            <a href="{{ route('leagues.groups.index', $league) }}" class="text-decoration-none text-secondary">Grupos</a>
            /
            <a href="{{ route('leagues.jornadas.index', [$league, $group]) }}" class="text-decoration-none text-secondary">{{ $group->name }}</a>
            / Jornada #{{ $jornada->number }}
        </small>
        <h5 class="mb-0 mt-1">Jornada #{{ $jornada->number }}</h5>
    </div>

    <div class="d-flex gap-2 flex-wrap">
        <button class="btn btn-outline-primary btn-sm" id="auto-fill-btn"
            data-url="{{ route('leagues.jornadas.auto-fill', [$league, $group, $jornada]) }}">
            <i class="fa-solid fa-shuffle me-1"></i> Auto-asignar
        </button>
        <a href="{{ route('leagues.matches.grid', [$league, $group, $jornada]) }}"
            class="btn btn-outline-primary btn-sm">
            <i class="fa-solid fa-calendar-days me-1"></i> Programar partidos
        </a>
        <button class="btn btn-primary btn-sm" id="add-cancha-btn"
            data-url="{{ route('leagues.canchas.store', [$league, $group, $jornada]) }}">
            <i class="fa-solid fa-plus me-1"></i> Nueva cancha
        </button>
    </div>
</div>

<div class="card-soft p-3 mb-3">
    <form id="jornada-window-form"
        data-url="{{ route('leagues.jornadas.update', [$league, $group, $jornada]) }}">
        @csrf
        @method('PUT')
        <div class="row g-2 align-items-end">
            <div class="col-md-4">
                <label class="form-label small">Inicio de ventana</label>
                <input type="date" name="window_start" class="form-control form-control-sm"
                    value="{{ $jornada->window_start?->toDateString() }}">
            </div>
            <div class="col-md-4">
                <label class="form-label small">Fin de ventana</label>
                <input type="date" name="window_end" class="form-control form-control-sm"
                    value="{{ $jornada->window_end?->toDateString() }}">
            </div>
            <div class="col-md-4 d-grid">
                <button type="submit" class="btn btn-sm btn-outline-primary">
                    <i class="fa-solid fa-floppy-disk me-1"></i> Guardar fechas
                </button>
            </div>
        </div>
        <small class="text-secondary d-block mt-2">
            Si lo dejas vacío, se usarán los próximos 14 días filtrados por los días de la liga.
        </small>
    </form>
</div>

<div class="canchas-app"
    data-league-id="{{ $league->id }}"
    data-group-id="{{ $group->id }}"
    data-jornada-id="{{ $jornada->id }}"
    data-mode="{{ $isPairs ? 'pairs' : 'individual' }}"
    data-assign-url="{{ route('leagues.canchas.assign', [$league, $group, $jornada]) }}"
    data-cancha-url-template="{{ url("/leagues/{$league->id}/groups/{$group->id}/jornadas/{$jornada->id}/canchas") }}">

    <div class="row g-3">
        {{-- Sidebar: roster pool --}}
        <div class="col-lg-4">
            <div class="card-soft p-3 h-100">
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <h6 class="mb-0">
                        <i class="fa-solid fa-inbox me-1 text-secondary"></i>
                        {{ $isPairs ? 'Parejas disponibles' : 'Jugadores disponibles' }}
                    </h6>
                    <span class="badge text-bg-secondary pool-count">
                        {{ $isPairs ? $availablePairs->count() : $availablePlayers->count() }}
                    </span>
                </div>

                <div class="roster-list cancha-pool" data-cancha-id="0">
                    @if ($isPairs)
                    @foreach ($availablePairs as $pair)
                    @include('leagues.groups._pair-chip', ['pair' => $pair])
                    @endforeach
                    @else
                    @foreach ($availablePlayers as $player)
                    @include('leagues.groups._player-chip', ['player' => $player])
                    @endforeach
                    @endif
                </div>
            </div>
        </div>

        {{-- Canchas --}}
        <div class="col-lg-8">
            <div id="canchas-container" class="row g-3">
                @forelse ($jornada->canchas as $cancha)
                @include('leagues.jornadas._cancha-card', [
                'cancha' => $cancha, 'mode' => $isPairs ? 'pairs' : 'individual',
                'league' => $league, 'group' => $group, 'jornada' => $jornada,
                ])
                @empty
                <div class="col-12">
                    <div class="card-soft empty-state py-5" id="canchas-empty">
                        <div class="empty-state-icon">
                            <i class="fa-solid fa-table-cells"></i>
                        </div>
                        <h6>Sin canchas todavía</h6>
                        <p class="small mb-0">Crea una cancha manualmente o usa Auto-asignar.</p>
                    </div>
                </div>
                @endforelse
            </div>
        </div>
    </div>
</div>
@endsection