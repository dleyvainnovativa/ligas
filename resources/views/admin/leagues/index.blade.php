@extends('layouts.admin')

@section('title', 'Ligas — PlayWinners')

@section('content')
<div class="mb-4">
    <h1 class="mb-1">Ligas</h1>
    <p class="text-secondary mb-0">{{ $leagues->total() }} ligas en total. Ajusta los límites por liga.</p>
</div>

<form method="GET" action="{{ route('admin.leagues.index') }}" class="mb-4">
    <div class="input-group" style="max-width:420px;">
        <input type="text" name="q" class="form-control" placeholder="Buscar por nombre…" value="{{ $search }}">
        <button class="btn btn-outline-secondary" type="submit"><i class="fa-solid fa-magnifying-glass"></i></button>
    </div>
</form>

<div class="card-soft p-0 overflow-hidden">
    <div class="table-responsive">
        <table class="table mb-0 align-middle">
            <thead>
                <tr>
                    <th>Liga</th>
                    <th class="text-center">Jugadores</th>
                    <th class="text-center">Grupos</th>
                    <th class="text-center">Máx. jugadores</th>
                    <th class="text-center">Máx. jornadas</th>
                    <th class="text-center">Máx. grupos</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                @forelse ($leagues as $league)
                <tr>
                    <td>
                        <strong>{{ $league->name }}</strong>
                        @if ($league->status)
                        <span class="text-muted small d-block">{{ $league->status }}</span>
                        @endif
                    </td>
                    <td class="text-center font-mono">{{ $league->players_count }}</td>
                    <td class="text-center font-mono">{{ $league->groups_count }}</td>
                    <td class="text-center font-mono">{{ $league->max_players ?? '∞' }}</td>
                    <td class="text-center font-mono">{{ $league->max_jornadas ?? '∞' }}</td>
                    <td class="text-center font-mono">{{ $league->max_groups ?? '∞' }}</td>
                    <td class="text-end">
                        <a href="{{ route('admin.leagues.edit', $league) }}" class="btn btn-sm btn-outline-secondary">
                            <i class="fa-solid fa-sliders me-1"></i> Límites
                        </a>
                    </td>
                </tr>
                @empty
                <tr><td colspan="7" class="text-center text-muted py-4">No se encontraron ligas.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

<div class="mt-3">{{ $leagues->links() }}</div>
@endsection
