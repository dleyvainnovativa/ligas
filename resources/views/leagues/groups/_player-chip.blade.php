<div class="roster-chip" data-player-id="{{ $player->id }}">
    <div class="chip-avatar">{{ mb_substr($player->full_name ?: '?', 0, 1) }}</div>
    <div class="chip-info">
        <div class="chip-name">{{ $player->full_name }}</div>
        @if ($player->phone || $player->email)
        <small class="text-secondary chip-sub">{{ $player->phone ?: $player->email }}</small>
        @endif
    </div>
    <i class="fa-solid fa-grip-vertical chip-grip text-muted"></i>
</div>