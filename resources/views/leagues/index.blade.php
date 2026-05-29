@extends('layouts.app')

@section('title', 'Ligas — Padel Leagues')
@section('page-title', 'Ligas')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h4 class="mb-1">Mis ligas</h4>
        <p class="text-secondary mb-0">Administra todas tus ligas en un solo lugar.</p>
    </div>
    <a href="{{ route('leagues.create') }}" class="btn btn-primary">
        <i class="fa-solid fa-plus me-1"></i> Nueva liga
    </a>
</div>

@if ($leagues->isEmpty())
<div class="card-soft p-5 empty-state">
    <div class="empty-state-icon">
        <i class="fa-solid fa-trophy"></i>
    </div>
    <h6>Aún no tienes ligas</h6>
    <p class="small mb-3">Crea tu primera liga para comenzar a organizar partidos.</p>
    <a href="{{ route('leagues.create') }}" class="btn btn-primary">
        <i class="fa-solid fa-plus me-1"></i> Crear mi primera liga
    </a>
</div>
@else
<div class="row g-3">
    @foreach ($leagues as $league)
    <div class="col-md-6 col-lg-4">
        <a href="{{ route('leagues.show', $league) }}" class="card-soft card-interactive d-block text-decoration-none h-100 overflow-hidden">
            <div style="aspect-ratio: 16/9; background:var(--surface-sunken) center/cover no-repeat
                    @if($league->banner_url) url('{{ $league->banner_url }}') @endif;
                    position:relative;">
                @unless ($league->banner_url)
                <div class="d-flex align-items-center justify-content-center h-100">
                    <i class="fa-solid fa-trophy text-muted" style="font-size:32px;"></i>
                </div>
                @endunless
                <div style="position:absolute;top:10px;right:10px;display:flex;gap:4px;">
                    <span class="badge text-bg-{{ $league->status === 'active' ? 'success' : ($league->status === 'completed' ? 'info' : 'secondary') }}"
                        style="backdrop-filter:blur(8px);">
                        {{ ucfirst($league->status) }}
                    </span>
                </div>
            </div>
            <div class="p-4">
                <div class="d-flex align-items-center gap-2 mb-2">
                    <span class="badge text-bg-secondary">
                        {{ $league->format === 'pairs' ? 'Parejas' : 'Individual' }}
                    </span>
                </div>
                <h5 class="mb-1 text-truncate">{{ $league->name }}</h5>
                <div class="panel-meta">
                    {{ $league->num_jornadas }} jornadas · ${{ number_format($league->cost, 0) }}
                </div>
            </div>
        </a>
    </div>
    @endforeach
</div>
@endif
@endsection