@php
    $eg = (int) ($row['extra_games'] ?? 0);
    $es = (int) ($row['extra_sets'] ?? 0);
@endphp
@if ($eg !== 0 || $es !== 0)
    @if ($eg !== 0)
    <span class="badge {{ $eg > 0 ? 'text-bg-success' : 'text-bg-danger' }} font-mono"
        title="Puntos extra en games">{{ $eg > 0 ? '+' : '' }}{{ $eg }}g</span>
    @endif
    @if ($es !== 0)
    <span class="badge {{ $es > 0 ? 'text-bg-success' : 'text-bg-danger' }} font-mono"
        title="Puntos extra en sets">{{ $es > 0 ? '+' : '' }}{{ $es }}s</span>
    @endif
@else
    <span class="text-muted">—</span>
@endif
