@extends('layouts.app')

@section('title', 'Dashboard — Padel Leagues')
@section('page-title', 'Dashboard')

@section('content')
<div class="mb-5">
    <h1 class="mb-1">Hola, {{ explode(' ', $manager->name ?? $manager->email)[0] }} </h1>
    <p class="text-secondary mb-0">Tu espacio para administrar ligas de pádel.</p>
</div>

<div class="row g-3 mb-5">
    @php
    $leagueCount = $manager->leagues()->count();
    $activeCount = $manager->leagues()->where('status', 'active')->count();
    @endphp

    <div class="col-md-4">
        <a href="{{ route('leagues.index') }}" class="card-soft card-interactive p-4 d-block text-decoration-none h-100">
            <div class="d-flex align-items-center gap-3 mb-3">
                <div class="rounded-3 d-flex align-items-center justify-content-center"
                    style="width:40px;height:40px;background:var(--surface-sunken);color:var(--brand-700);">
                    <i class="fa-solid fa-trophy"></i>
                </div>
                <div class="flex-grow-1">
                    <div class="text-muted small text-uppercase fw-semibold" style="letter-spacing:0.06em;">Ligas</div>
                </div>
                <i class="fa-solid fa-arrow-right text-muted"></i>
            </div>
            <div class="d-flex align-items-baseline gap-2">
                <span class="font-mono" style="font-size:28px;font-weight:700;">{{ $leagueCount }}</span>
                <span class="text-secondary small">en total</span>
            </div>
            @if ($activeCount > 0)
            <small class="text-success">
                <i class="fa-solid fa-circle-dot" style="font-size:8px;"></i>
                {{ $activeCount }} {{ Str::plural('activa', $activeCount) }}
            </small>
            @endif
        </a>
    </div>

    <div class="col-md-4">
        <div class="card-soft p-4 h-100">
            <div class="d-flex align-items-center gap-3 mb-3">
                <div class="rounded-3 d-flex align-items-center justify-content-center"
                    style="width:40px;height:40px;background:var(--surface-sunken);color:var(--brand-700);">
                    <i class="fa-solid fa-circle-info"></i>
                </div>
                <div class="flex-grow-1">
                    <div class="text-muted small text-uppercase fw-semibold" style="letter-spacing:0.06em;">Inicio rápido</div>
                </div>
            </div>
            <p class="text-secondary small mb-3">
                Crea una liga, agrega jugadores, forma canchas y arma el calendario en minutos.
            </p>
            <a href="{{ route('leagues.create') }}" class="btn btn-primary btn-sm">
                <i class="fa-solid fa-plus me-1"></i> Nueva liga
            </a>
        </div>
    </div>

    <div class="col-md-4">
        <div class="card-soft p-4 h-100">
            <div class="d-flex align-items-center gap-3 mb-3">
                <div class="rounded-3 d-flex align-items-center justify-content-center"
                    style="width:40px;height:40px;background:var(--surface-sunken);color:var(--brand-700);">
                    <i class="fa-regular fa-lightbulb"></i>
                </div>
                <div class="flex-grow-1">
                    <div class="text-muted small text-uppercase fw-semibold" style="letter-spacing:0.06em;">Tip</div>
                </div>
            </div>
            <p class="text-secondary small mb-0">
                Cada liga tiene una <strong>URL pública</strong> para compartir con tus jugadores. Cámbiala en Configuración.
            </p>
        </div>
    </div>
</div>
@endsection