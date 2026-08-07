@extends('layouts.admin')

@section('title', 'Anuncios — PlayWinners')

@section('content')
<div class="d-flex justify-content-between align-items-center flex-wrap gap-3 mb-4">
    <div>
        <h1 class="mb-1">Anuncios</h1>
        <p class="text-secondary mb-0">Anuncios globales (todas las ligas) e individuales por liga.</p>
    </div>
    <a href="{{ route('admin.ads.create') }}" class="btn btn-primary">
        <i class="fa-solid fa-plus me-1"></i> Nuevo anuncio
    </a>
</div>

{{-- Global --}}
<h6 class="mb-3"><i class="fa-solid fa-globe me-1"></i> Globales</h6>
<div class="row g-3 mb-5">
    @forelse ($globalAds as $ad)
    @include('admin.ads._card', ['ad' => $ad])
    @empty
    <div class="col-12"><p class="text-muted">No hay anuncios globales.</p></div>
    @endforelse
</div>

{{-- Per league --}}
<h6 class="mb-3"><i class="fa-solid fa-trophy me-1"></i> Por liga</h6>
<div class="row g-3">
    @forelse ($leagueAds as $ad)
    @include('admin.ads._card', ['ad' => $ad, 'showLeague' => true])
    @empty
    <div class="col-12"><p class="text-muted">No hay anuncios por liga.</p></div>
    @endforelse
</div>
@endsection
