@php
$isPairs = $mode === 'pairs';
$occupants = $isPairs ? $cancha->pairs : $cancha->players;
$max = $isPairs ? \App\Models\Cancha::MAX_PAIRS : \App\Models\Cancha::MAX_PLAYERS;
$count = $occupants->count();
$full = $count >= $max;
@endphp
<div class="col-md-6 cancha-card-wrapper">
    <div class="cancha-card card-soft @if($full) is-full @endif"
        data-cancha-id="{{ $cancha->id }}"
        data-max="{{ $max }}">
        <div class="cancha-header">
            <div class="d-flex align-items-center gap-2 flex-grow-1 min-w-0">
                <i class="fa-solid fa-table-cells text-secondary"></i>
                <input type="text" class="form-control form-control-sm cancha-label" value="{{ $cancha->label }}">
            </div>
            <span class="badge text-bg-secondary cancha-count">{{ $count }}/{{ $max }}</span>
            <button class="btn btn-sm btn-outline-secondary save-cancha" title="Guardar">
                <i class="fa-solid fa-floppy-disk"></i>
            </button>
            <button class="btn btn-sm btn-outline-danger delete-cancha" title="Eliminar cancha">
                <i class="fa-solid fa-trash"></i>
            </button>
        </div>
        <div class="roster-list cancha-roster" data-cancha-id="{{ $cancha->id }}">
            @if ($isPairs)
            @foreach ($cancha->pairs as $pair)
            @include('leagues.groups._pair-chip', ['pair' => $pair])
            @endforeach
            @else
            @foreach ($cancha->players as $player)
            @include('leagues.groups._player-chip', ['player' => $player])
            @endforeach
            @endif
        </div>
    </div>
</div>