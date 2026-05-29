@extends('layouts.app')

@section('title', "Standings — {$group->name}")
@section('page-title', $league->name)

@section('content')
@include('leagues.partials._panel-nav', ['active' => 'standings'])

<div class="mb-3">
    <a href="{{ route('leagues.standings.index', $league) }}" class="text-secondary text-decoration-none small">
        <i class="fa-solid fa-arrow-left me-1"></i> Todos los grupos
    </a>
</div>

<div class="card-soft p-3 p-md-4">
    <h5 class="mb-3">
        <i class="fa-solid fa-layer-group text-secondary me-1"></i>
        {{ $group->name }}
    </h5>
    @include('leagues.standings._table', ['rows' => $rows, 'league' => $league])
</div>
@endsection