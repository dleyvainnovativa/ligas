<div class="roster-chip pair-chip" data-pair-id="{{ $pair->id }}">
    <div class="chip-avatar pair-avatar">
        <i class="fa-solid fa-people-arrows"></i>
    </div>
    <div class="chip-info">
        <div class="chip-name">{{ $pair->display_name }}</div>
        @if ($pair->label)
        <small class="text-secondary chip-sub">{{ $pair->playerA?->full_name }} / {{ $pair->playerB?->full_name }}</small>
        @endif
    </div>
    <i class="fa-solid fa-grip-vertical chip-grip text-muted"></i>
</div>