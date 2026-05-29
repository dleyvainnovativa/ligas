@extends('layouts.app')

@section('title', "Configuración — {$league->name}")
@section('page-title', $league->name)

@section('content')
@include('leagues.partials._panel-nav', ['active' => 'settings'])
@include('leagues.partials._form', ['league' => $league])
@endsection