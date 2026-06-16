@extends('layouts.app')

@section('title', "Jornadas — {$group->name} — {$league->name}")
@section('page-title', $league->name)

@section('content')
@include('leagues.partials._panel-nav', ['active' => 'groups'])

<div class="d-flex align-items-center justify-content-between mb-3 flex-wrap gap-2">
    <div>
        <small class="text-secondary">
            <a href="{{ route('leagues.groups.index', $league) }}" class="text-decoration-none text-secondary">
                <i class="fa-solid fa-arrow-left me-1"></i> Grupos
            </a>
            / {{ $group->name }}
        </small>
        <h5 class="mb-0 mt-1">Jornadas — {{ $group->name }}</h5>
    </div>

    <button class="btn btn-primary btn-sm" id="add-jornada-btn"
        data-url="{{ route('leagues.jornadas.store', [$league, $group]) }}">
        <i class="fa-solid fa-plus me-1"></i> Nueva jornada
    </button>
</div>

<div id="jornadas-list" class="row g-3">
    @forelse ($group->jornadas as $jornada)
    @include('leagues.jornadas._card', [
    'league' => $league, 'group' => $group, 'jornada' => $jornada
    ])
    @empty
    <div class="col-12">
        <div class="card-soft empty-state py-5" id="jornadas-empty">
            <div class="empty-state-icon">
                <i class="fa-solid fa-calendar-day"></i>
            </div>
            <h6>Aún no hay jornadas</h6>
            <p class="small mb-0">Crea la primera jornada para empezar a formar canchas.</p>
        </div>
    </div>
    @endforelse
</div>
@endsection