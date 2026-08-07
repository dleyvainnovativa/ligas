@extends('layouts.admin')

@section('title', 'Límites de liga — PlayWinners')

@section('content')
<div class="mb-4">
    <a href="{{ route('admin.leagues.index') }}" class="text-muted small text-decoration-none">
        <i class="fa-solid fa-arrow-left me-1"></i> Ligas
    </a>
    <h1 class="mb-0 mt-2">{{ $league->name }}</h1>
    <p class="text-secondary mb-0">Ajusta los límites de esta liga. Deja un campo vacío para «sin límite».</p>
</div>

<div class="card-soft p-4" style="max-width:640px;">
    <form method="POST" action="{{ route('admin.leagues.update', $league) }}">
        @csrf @method('PUT')

        {{-- Players --}}
        <div class="mb-4">
            <label class="form-label">Máximo de jugadores</label>
            <input type="number" name="max_players" class="form-control" min="1"
                value="{{ old('max_players', $league->max_players) }}"
                placeholder="Sin límite">
            <small class="text-muted">
                En uso: <strong>{{ $snapshot['players']['used'] }}</strong>
                @if (!$snapshot['players']['unlimited'])
                / {{ $snapshot['players']['limit'] }}
                @if ($snapshot['players']['at_limit']) <span class="text-danger">(al límite)</span>@endif
                @endif
            </small>
        </div>

        {{-- Jornadas --}}
        <div class="mb-4">
            <label class="form-label">Máximo de jornadas</label>
            <input type="number" name="max_jornadas" class="form-control" min="1"
                value="{{ old('max_jornadas', $league->max_jornadas) }}"
                placeholder="Sin límite">
            <small class="text-muted">
                En uso: <strong>{{ $snapshot['jornadas']['used'] }}</strong>
                @if (!$snapshot['jornadas']['unlimited'])
                / {{ $snapshot['jornadas']['limit'] }}
                @if ($snapshot['jornadas']['at_limit']) <span class="text-danger">(al límite)</span>@endif
                @endif
            </small>
        </div>

        {{-- Groups --}}
        <div class="mb-4">
            <label class="form-label">Máximo de grupos</label>
            <input type="number" name="max_groups" class="form-control" min="1"
                value="{{ old('max_groups', $league->max_groups) }}"
                placeholder="Sin límite">
            <small class="text-muted">
                En uso: <strong>{{ $snapshot['groups']['used'] }}</strong>
                @if (!$snapshot['groups']['unlimited'])
                / {{ $snapshot['groups']['limit'] }}
                @if ($snapshot['groups']['at_limit']) <span class="text-danger">(al límite)</span>@endif
                @endif
            </small>
        </div>

        <div class="alert alert-info small d-flex align-items-start gap-2">
            <i class="fa-solid fa-circle-info mt-1"></i>
            <span>Un campo vacío significa <strong>sin límite</strong>. Si fijas un número por debajo del uso
            actual, no se borra nada: la liga simplemente no podrá agregar más hasta quedar por debajo del tope.</span>
        </div>

        <button type="submit" class="btn btn-primary">
            <i class="fa-solid fa-floppy-disk me-1"></i> Guardar límites
        </button>
    </form>
</div>
@endsection
