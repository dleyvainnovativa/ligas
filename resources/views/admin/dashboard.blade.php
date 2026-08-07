@extends('layouts.admin')

@section('title', 'Panel de administración — PlayWinners')

@section('content')
<div class="mb-5">
    <h1 class="mb-1">Panel de administración</h1>
    <p class="text-secondary mb-0">Resumen de managers y planes en la plataforma.</p>
</div>

<div class="row g-3 mb-4">
    <div class="col-md-4">
        <div class="card-soft p-4 h-100">
            <div class="text-muted small text-uppercase fw-semibold" style="letter-spacing:0.06em;">Managers</div>
            <div class="d-flex align-items-baseline gap-2 mt-2">
                <span class="font-mono" style="font-size:32px;font-weight:700;">{{ $managerCount }}</span>
                <span class="text-secondary small">registrados</span>
            </div>
        </div>
    </div>

    <div class="col-md-4">
        <div class="card-soft p-4 h-100">
            <div class="text-muted small text-uppercase fw-semibold" style="letter-spacing:0.06em;">Admins</div>
            <div class="d-flex align-items-baseline gap-2 mt-2">
                <span class="font-mono" style="font-size:32px;font-weight:700;">{{ $adminCount }}</span>
                <span class="text-secondary small">con acceso</span>
            </div>
        </div>
    </div>

    <div class="col-md-4">
        <a href="{{ route('admin.managers') }}" class="card-soft card-interactive p-4 d-block text-decoration-none h-100">
            <div class="d-flex align-items-center justify-content-between">
                <div class="text-muted small text-uppercase fw-semibold" style="letter-spacing:0.06em;">Gestionar</div>
                <i class="fa-solid fa-arrow-right text-muted"></i>
            </div>
            <div class="mt-2 fw-semibold">Agregar o revisar managers</div>
        </a>
    </div>
</div>

<div class="card-soft p-4">
    <h6 class="mb-3">Managers por plan</h6>
    <div class="d-flex gap-4 flex-wrap">
        @foreach (['free', 'plus', 'pro'] as $tier)
        <div>
            <div class="plan-badge plan-badge-{{ $tier }}">
                <i class="fa-solid fa-{{ $tier === 'pro' ? 'crown' : ($tier === 'plus' ? 'star' : 'circle') }}"></i>
                {{ ucfirst($tier) }}
            </div>
            <div class="font-mono mt-2" style="font-size:24px;font-weight:700;">{{ $byTier[$tier] ?? 0 }}</div>
        </div>
        @endforeach
    </div>
</div>
@endsection
