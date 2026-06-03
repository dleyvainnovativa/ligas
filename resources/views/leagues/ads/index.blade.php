@extends('layouts.app')

@section('title', "Anuncios — {$league->name}")
@section('page-title', $league->name)

@section('content')
@include('leagues.partials._panel-nav', ['active' => 'ads'])

<div class="card-soft p-4 mb-3"
    id="ads-app"
    data-league-id="{{ $league->id }}">

    <div class="d-flex justify-content-between align-items-start mb-3 flex-wrap gap-2">
        <div>
            <h6 class="mb-1">Anuncios</h6>
            <small class="text-secondary">
                Imágenes horizontales que se muestran en la página pública de la liga.
                Recomendación: 1200×400 px (proporción 3:1).
            </small>
        </div>
        <button class="btn btn-primary btn-sm" id="add-ad-btn">
            <i class="fa-solid fa-plus me-1"></i> Nuevo anuncio
        </button>
    </div>

    <input type="file" id="ad-file-input" accept="image/png,image/jpeg,image/webp" class="d-none">

    <div id="ads-list" class="d-flex flex-column gap-2">
        @forelse ($league->ads as $ad)
        @include('leagues.ads._row', ['ad' => $ad])
        @empty
        <div class="empty-state py-4" id="ads-empty">
            <div class="empty-state-icon"><i class="fa-solid fa-rectangle-ad"></i></div>
            <h6>Aún no hay anuncios</h6>
            <p class="small mb-0">Sube tu primer anuncio para mostrarlo en la página pública.</p>
        </div>
        @endforelse
    </div>
</div>
@endsection