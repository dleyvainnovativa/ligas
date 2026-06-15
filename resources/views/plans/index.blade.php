@extends('layouts.app')
@section('title', 'Planes')
@section('page-title', 'Planes')

@section('content')
<div class="text-center mb-5">
    <h2 class="mb-2">Elige el plan que se adapte a tu liga</h2>
    <p class="text-secondary">Sin compromisos. Cancela cuando quieras.</p>
</div>

<div class="row g-4">
    @foreach ($plans as $tierKey => $plan)
    <div class="col-md-4">
        <div class="plan-tier @if ($tierKey === 'plus') is-featured @endif">
            @if ($tierKey === 'plus')
            <div class="plan-tier-ribbon">Más popular</div>
            @endif

            <div class="plan-tier-head">
                <h3>{{ $plan['label'] }}</h3>
                <div class="plan-tier-price">{{ $plan['price_label'] }}</div>
                <p class="plan-tier-tagline">{{ $plan['tagline'] }}</p>
            </div>

            <ul class="plan-tier-limits list-unstyled">
                <li>
                    <i class="fa-solid fa-circle-check"></i>
                    <strong>{{ $plan['limits']['active_leagues'] ?? '∞' }}</strong> ligas activas
                </li>
                <li>
                    <i class="fa-solid fa-circle-check"></i>
                    <strong>{{ $plan['limits']['players_per_league'] ?? '∞' }}</strong> jugadores por liga
                </li>
                <li>
                    <i class="fa-solid fa-circle-check"></i>
                    <strong>{{ $plan['limits']['jornadas_per_league'] ?? '∞' }}</strong> jornadas por liga
                </li>
            </ul>

            <ul class="plan-tier-features list-unstyled">
                @foreach ($plan['features'] as $feature)
                <li>
                    <i class="fa-solid fa-check text-success"></i>
                    {{ $feature }}
                </li>
                @endforeach
            </ul>

            <div class="mt-auto pt-3">
                @if ($currentTier === $tierKey)
                <button class="btn btn-outline-secondary w-100" disabled>
                    Tu plan actual
                </button>
                @else
                <a href="mailto:tu-correo@ejemplo.com?subject=Solicitud%20de%20upgrade%20a%20{{ $plan['label'] }}"
                    class="btn btn-primary w-100">
                    Solicitar {{ $plan['label'] }}
                </a>
                @endif
            </div>
        </div>
    </div>
    @endforeach
</div>

<div class="text-center mt-5">
    <small class="text-secondary">
        ¿Preguntas? <a href="mailto:tu-correo@ejemplo.com">Escríbenos</a>
    </small>
</div>
@endsection