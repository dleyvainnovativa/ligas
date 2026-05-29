<div class="pair-row d-flex align-items-center gap-2 p-2" data-pair-id="{{ $pair->id }}"
    style="background:var(--surface-page);border:1px solid var(--border-default);border-radius:var(--radius-md);">
    <div class="chip-avatar pair-avatar"><i class="fa-solid fa-people-arrows"></i></div>
    <div class="flex-grow-1 min-w-0">
        <input type="text" class="form-control form-control-sm pair-label-input"
            value="{{ $pair->label }}" placeholder="{{ $pair->playerA?->full_name }} / {{ $pair->playerB?->full_name }}">
        <small class="text-secondary d-block mt-1 text-truncate">
            {{ $pair->playerA?->full_name }} <i class="fa-solid fa-plus mx-1" style="font-size:0.6rem;"></i> {{ $pair->playerB?->full_name }}
        </small>
    </div>
    <button class="btn btn-sm btn-outline-secondary save-pair" title="Guardar">
        <i class="fa-solid fa-floppy-disk"></i>
    </button>
    <button class="btn btn-sm btn-outline-danger dissolve-pair" title="Disolver pareja">
        <i class="fa-solid fa-link-slash"></i>
    </button>
</div>