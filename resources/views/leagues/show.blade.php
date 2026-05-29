@extends('layouts.app')

@section('title', $league->name)
@section('page-title', $league->name)

@section('content')
@include('leagues.partials._panel-nav', ['active' => 'overview'])

<div class="row g-3">
    <div class="col-md-3">
        <div class="card-soft p-3">
            <small class="text-secondary d-block">Jugadores</small>
            <h4 class="mb-0">{{ $league->players()->count() }}</h4>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card-soft p-3">
            <small class="text-secondary d-block">Sedes</small>
            <h4 class="mb-0">{{ $league->sedes()->count() }}</h4>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card-soft p-3">
            <small class="text-secondary d-block">Pistas</small>
            <h4 class="mb-0">{{ $league->pistas()->count() }}</h4>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card-soft p-3">
            <small class="text-secondary d-block">Pagado</small>
            <h4 class="mb-0">${{ number_format($league->players()->sum('paid_amount'), 0) }}</h4>
        </div>
    </div>
</div>

@php
$standings = app(\App\Services\StandingsService::class);
@endphp

@if ($league->groups()->exists())
<div class="card-soft p-3 p-md-4 mt-3">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h6 class="mb-0"><i class="fa-solid fa-ranking-star text-secondary me-1"></i> Standings</h6>
        <a href="{{ route('leagues.standings.index', $league) }}" class="btn btn-sm btn-outline-secondary">
            Ver todos <i class="fa-solid fa-arrow-right ms-1"></i>
        </a>
    </div>
    <div class="row g-3">
        @foreach ($league->groups as $g)
        @php $rows = $standings->forGroup($g); @endphp
        <div class="col-md-6">
            <div class="mini-standings">
                <h6 class="mini-standings-title">{{ $g->name }}</h6>
                @if (empty($rows))
                <p class="text-secondary small mb-0">Sin participantes</p>
                @else
                <ol class="mini-standings-list">
                    @foreach (array_slice($rows, 0, 5) as $row)
                    <li>
                        <span class="mini-rank">{{ $row['rank'] }}</span>
                        <span class="mini-name">{{ $row['name'] }}</span>
                        <span class="mini-pts">{{ $row['points'] }}</span>
                    </li>
                    @endforeach
                </ol>
                @endif
            </div>
        </div>
        @endforeach
    </div>
</div>
@endif

<div class="row g-3 mt-1" id="sedes">
    <div class="col-12">
        @include('leagues.partials._sedes', ['league' => $league->load('sedes.pistas')])
    </div>
</div>
@endsection