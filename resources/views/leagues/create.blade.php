@extends('layouts.app')

@section('title', 'Nueva liga')
@section('page-title', 'Nueva liga')

@section('content')
@include('leagues.partials._form', ['league' => $league])
@endsection