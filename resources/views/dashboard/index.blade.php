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

    @if ($leagueCount === 0)
    {{-- First-run state --}}
    <div class="col-12">
        <div class="card-soft p-4 p-md-5 onboarding-card">
            <div class="onboarding-badge">
                <i class="fa-solid fa-sparkles"></i> Empezar
            </div>
            <h2 class="mb-2">Crea tu primera liga</h2>
            <p class="text-secondary mb-4" style="max-width: 56ch;">
                En cuatro pasos tienes todo listo: crea la liga con sus reglas y horarios,
                agrega jugadores (o impórtalos desde CSV), forma grupos y arma el calendario.
            </p>
            <div class="onboarding-steps">
                <div class="onboarding-step">
                    <div class="step-number">1</div>
                    <div>
                        <strong>Configura la liga</strong>
                        <small class="d-block text-muted">Nombre, formato, jornadas, horarios, sedes y pistas.</small>
                    </div>
                </div>
                <div class="onboarding-step">
                    <div class="step-number">2</div>
                    <div>
                        <strong>Agrega jugadores</strong>
                        <small class="d-block text-muted">Manualmente o importando un CSV.</small>
                    </div>
                </div>
                <div class="onboarding-step">
                    <div class="step-number">3</div>
                    <div>
                        <strong>Forma grupos y canchas</strong>
                        <small class="d-block text-muted">Arrastra jugadores a grupos y canchas por jornada.</small>
                    </div>
                </div>
                <div class="onboarding-step">
                    <div class="step-number">4</div>
                    <div>
                        <strong>Programa y comparte</strong>
                        <small class="d-block text-muted">Acomoda los partidos en el calendario y comparte el link público.</small>
                    </div>
                </div>
            </div>
            <div class="mt-4">
                <a href="{{ route('leagues.create') }}" class="btn btn-primary btn-lg">
                    <i class="fa-solid fa-plus me-1"></i> Crear mi primera liga
                </a>
            </div>
        </div>
    </div>
    @else
    {{-- Regular dashboard cards (existing) --}}
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

    {{-- ...rest as before... --}}
    @endif
</div>
@endsection