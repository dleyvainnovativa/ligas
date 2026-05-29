@extends('layouts.app')

@section('title', "Grupos — {$league->name}")
@section('page-title', $league->name)

@section('content')
@include('leagues.partials._panel-nav', ['active' => 'groups'])

@if ($league->format === \App\Models\League::FORMAT_PAIRS)
@include('leagues.groups._pairs-section', ['league' => $league])
@include('leagues.groups._groups-section', ['league' => $league, 'mode' => 'pairs'])
@else
@include('leagues.groups._groups-section', ['league' => $league, 'mode' => 'individual'])
@endif
@endsection