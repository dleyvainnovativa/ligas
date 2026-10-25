@extends('layouts.app')

@section('title', "Puntos extra — {$league->name}")
@section('page-title', $league->name)

@section('content')
@include('leagues.partials._panel-nav', ['active' => 'adjustments'])

<div class="card-soft p-4 mb-3"
    id="adjustments-app"
    data-league-id="{{ $league->id }}"
    data-is-pairs="{{ $isPairs ? '1' : '0' }}">

    <div class="mb-3">
        <h6 class="mb-1">Puntos extra</h6>
        <small class="text-secondary">
            Ajustes que suman (o restan) {{ $isPairs ? 'a una pareja' : 'a un jugador' }}
            <strong>solo en la tabla general</strong>. No afectan el cálculo de ascensos y
            descensos por jornada. Un <strong>game</strong> mueve los puntos (diferencia);
            un <strong>set</strong> mueve el desempate de sets ganados.
        </small>
    </div>

    @if (($isPairs ? $pairs->isEmpty() : $players->isEmpty()))
    <div class="empty-state py-4">
        <div class="empty-state-icon"><i class="fa-solid fa-star-half-stroke"></i></div>
        <h6>Aún no hay {{ $isPairs ? 'parejas' : 'jugadores' }}</h6>
        <p class="small mb-0">
            Agrega {{ $isPairs ? 'parejas' : 'jugadores' }} a la liga antes de asignar puntos extra.
        </p>
    </div>
    @else

    {{-- Add form --}}
    <form id="adjustment-form" class="row g-2 align-items-end mb-4">
        @csrf
        <div class="col-md-4">
            <label class="form-label small">{{ $isPairs ? 'Pareja' : 'Jugador' }}</label>
            @if ($isPairs)
            <select name="pair_id" class="form-select form-select-sm" required>
                <option value="">Selecciona…</option>
                @foreach ($pairs as $pair)
                <option value="{{ $pair->id }}">{{ $pair->display_name }}</option>
                @endforeach
            </select>
            @else
            <select name="player_id" class="form-select form-select-sm" required>
                <option value="">Selecciona…</option>
                @foreach ($players as $player)
                <option value="{{ $player->id }}">{{ $player->full_name }}</option>
                @endforeach
            </select>
            @endif
        </div>
        <div class="col-md-2">
            <label class="form-label small">Unidad</label>
            <select name="unit" class="form-select form-select-sm" required>
                <option value="game">Game</option>
                <option value="set">Set</option>
            </select>
        </div>
        <div class="col-md-2">
            <label class="form-label small">
                Cantidad
                <i class="fa-solid fa-circle-info text-secondary ms-1"
                    title="Positivo suma, negativo resta. No puede ser 0."></i>
            </label>
            <input type="number" name="amount" class="form-control form-control-sm"
                step="1" min="-999" max="999" placeholder="+5 / -3" required>
        </div>
        <div class="col-md-3">
            <label class="form-label small">Nota (opcional)</label>
            <input type="text" name="note" class="form-control form-control-sm"
                maxlength="255" placeholder="Motivo del ajuste">
        </div>
        <div class="col-md-1 d-grid">
            <button type="submit" class="btn btn-primary btn-sm" title="Agregar">
                <i class="fa-solid fa-plus"></i>
            </button>
        </div>
    </form>

    {{-- Existing adjustments --}}
    <div id="adjustments-list" class="d-flex flex-column gap-2">
        @forelse ($adjustments as $adj)
        @include('leagues.adjustments._row', ['adj' => $adj])
        @empty
        <div class="empty-state py-4" id="adjustments-empty">
            <div class="empty-state-icon"><i class="fa-solid fa-star-half-stroke"></i></div>
            <h6>Sin puntos extra todavía</h6>
            <p class="small mb-0">Asigna un bono o corrección para que aparezca en la tabla general.</p>
        </div>
        @endforelse
    </div>
    @endif
</div>
@endsection
