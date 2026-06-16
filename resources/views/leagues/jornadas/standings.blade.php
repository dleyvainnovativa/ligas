@extends('layouts.app')
@section('title', "Jornada {$jornada->number} — Standings")
@section('page-title', $league->name)

@section('content')
@include('leagues.partials._panel-nav', ['active' => 'groups'])

<div class="d-flex align-items-center gap-3 mb-3 flex-wrap">
    <a href="{{ route('leagues.jornadas.index', [$league, $group]) }}" class="btn btn-sm btn-outline-secondary">
        <i class="fa-solid fa-arrow-left me-1"></i> Jornadas
    </a>
    <h5 class="mb-0">Jornada {{ $jornada->number }} · {{ $group->name }}</h5>
    @if (!$isComplete)
    <span class="badge text-bg-warning">En curso — los resultados pueden cambiar</span>
    @endif
</div>

@if (empty($breakdown))
<div class="card-soft p-5 text-center text-muted">
    <i class="fa-solid fa-table-cells" style="font-size:32px;opacity:0.4;"></i>
    <p class="mt-2 mb-0">Aún no hay canchas en esta jornada.</p>
</div>
@else
@include('leagues.jornadas._standings-body', [
'breakdown' => $breakdown,
'playerNames' => $playerNames,
'isComplete' => $isComplete,
'jornadaNumber' => $jornada->number,
])
@endif
@endsection