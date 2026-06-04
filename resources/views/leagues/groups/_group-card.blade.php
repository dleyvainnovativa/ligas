@php
/** @var \App\Models\Group $group */
$isPairs = $mode === 'pairs';
$count = $isPairs ? $group->pairs->count() : $group->players->count();
@endphp
<div class="group-card card-soft" data-group-id="{{ $group->id }}">
    <div class="group-card-header">
        <div class="flex-grow-1 min-w-0 d-flex align-items-center gap-2">
            <i class="fa-solid fa-layer-group text-secondary"></i>
            <input type="text" class="form-control form-control-sm group-name" value="{{ $group->name }}">
        </div>
        <span class="badge text-bg-secondary group-count">{{ $count }}</span>
        <button class="btn btn-sm btn-outline-primary auto-fill-group" title="Auto-asignar pendientes a este grupo"
            data-url="{{ route('leagues.groups.auto-fill', [$league, $group]) }}">
            <i class="fa-solid fa-wand-magic-sparkles"></i>
        </button>
        <button class="btn btn-sm btn-outline-secondary save-group" title="Guardar nombre">
            <i class="fa-solid fa-floppy-disk"></i>
        </button>
        <button class="btn btn-sm btn-outline-danger delete-group" title="Eliminar grupo">
            <i class="fa-solid fa-trash"></i>
        </button>
    </div>
    <div class="roster-list group-roster" data-group-id="{{ $group->id }}">
        @if ($isPairs)
        @foreach ($group->pairs as $pair)
        @include('leagues.groups._pair-chip', ['pair' => $pair])
        @endforeach
        @else
        @foreach ($group->players as $player)
        @include('leagues.groups._player-chip', ['player' => $player])
        @endforeach
        @endif
    </div>
    <div class="group-card-footer">
        <a href="{{ route('leagues.jornadas.index', [$league, $group]) }}"
            class="btn btn-sm btn-outline-primary w-100">
            <i class="fa-solid fa-calendar-day me-1"></i> Jornadas de este grupo
        </a>
    </div>
</div>