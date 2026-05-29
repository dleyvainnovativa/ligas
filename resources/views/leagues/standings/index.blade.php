@extends('layouts.app')

@section('title', "Standings — {$league->name}")
@section('page-title', $league->name)

@section('content')
@include('leagues.partials._panel-nav', ['active' => 'standings'])

@if (empty($tables))
<div class="card-soft empty-state py-5">
    <div class="empty-state-icon">
        <i class="fa-solid fa-ranking-star"></i>
    </div>
    <h6>Sin grupos todavía</h6>
    <p class="small mb-0">Crea al menos un grupo y juega algunos partidos para ver standings.</p>
</div>
@endif

@foreach ($tables as $t)
<div class="card-soft p-3 p-md-4 mb-3">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h6 class="mb-0">
            <i class="fa-solid fa-layer-group text-secondary me-1"></i>
            {{ $t['group']->name }}
        </h6>
        <a href="{{ route('leagues.standings.group', [$league, $t['group']]) }}"
            class="btn btn-sm btn-outline-secondary">
            <i class="fa-solid fa-arrow-up-right-from-square me-1"></i> Detalle
        </a>
    </div>
    @include('leagues.standings._table', ['rows' => $t['rows'], 'league' => $league])
</div>
@endforeach
@endsection