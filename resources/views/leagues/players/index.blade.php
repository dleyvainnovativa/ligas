@extends('layouts.app')

@section('title', "Jugadores — {$league->name}")
@section('page-title', $league->name)

@section('content')
@include('leagues.partials._panel-nav', ['active' => 'players'])

<div class="card-soft p-3 p-md-4" id="players-app"
    data-league-id="{{ $league->id }}"
    data-league-cost="{{ $league->cost }}">

    {{-- Header --}}
    <div class="players-header mb-3">
        <div class="players-header-info">
            <h6 class="mb-1">Jugadores</h6>
            <small class="text-secondary">
                <span id="players-count">{{ $players->count() }}</span> en total
            </small>
        </div>
        <div class="players-header-actions">
            <input type="search" id="players-search" class="form-control form-control-sm"
                placeholder="Buscar…">
            <button class="btn btn-outline-primary btn-sm mt-2" id="import-csv-btn">
                <i class="fa-solid fa-file-upload me-1"></i>
                <span class="d-none d-sm-inline">Importar</span>
                <span class="d-sm-none">Importar</span>
            </button>
            <button class="btn btn-primary btn-sm mt-2" id="add-player-btn">
                <i class="fa-solid fa-plus me-1"></i>
                <span class="d-none d-sm-inline">Agregar jugador</span>
                <span class="d-sm-none">Agregar</span>
            </button>
        </div>
    </div>

    {{-- DESKTOP: table --}}
    <div class="d-none d-lg-block">
        <div class="table-responsive">
            <table class="table align-middle mb-0 players-table">
                <thead>
                    <tr>
                        <th>Nombre</th>
                        <th>Email</th>
                        <th>Teléfono</th>
                        <th class="text-end" style="width:160px;">Pagado</th>
                        <th style="width:140px;">Estado</th>
                        <th style="width:110px;"></th>
                    </tr>
                </thead>
                <tbody id="players-tbody-desktop">
                    @forelse ($players as $player)
                    @include('leagues.players._row-desktop', ['player' => $player])
                    @empty
                    <tr id="players-empty-desktop">
                        <td colspan="6" class="text-center py-4 text-secondary">
                            Aún no hay jugadores. Agrega uno o importa un CSV.
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    {{-- MOBILE/TABLET: stacked cards --}}
    <div class="d-lg-none">
        <div id="players-tbody-mobile" class="players-cards">
            @forelse ($players as $player)
            @include('leagues.players._row-mobile', ['player' => $player])
            @empty
            <div id="players-empty-mobile" class="text-center py-4 text-secondary">
                Aún no hay jugadores. Agrega uno o importa un CSV.
            </div>
            @endforelse
        </div>
    </div>
</div>

@include('leagues.players._import-modal', ['league' => $league])
@endsection