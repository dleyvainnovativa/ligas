@php
    $eg = (int) ($row['extra_games'] ?? 0);
    $es = (int) ($row['extra_sets'] ?? 0);
    $notes = $row['extra_notes'] ?? [];
    // Tooltip text: each motive with its amount, e.g. "+5 games · Campeón relámpago".
    $tip = collect($notes)
        ->map(fn ($n) => trim(($n['label'] ?? '') . ' · ' . ($n['note'] ?? ''), ' ·'))
        ->filter()->implode("\n");
@endphp
@if ($eg !== 0 || $es !== 0)
    <span @if ($tip !== '') title="{{ $tip }}" @endif>
        @if ($eg !== 0)
        <span class="badge {{ $eg > 0 ? 'text-bg-success' : 'text-bg-danger' }} font-mono">{{ $eg > 0 ? '+' : '' }}{{ $eg }}g</span>
        @endif
        @if ($es !== 0)
        <span class="badge {{ $es > 0 ? 'text-bg-success' : 'text-bg-danger' }} font-mono">{{ $es > 0 ? '+' : '' }}{{ $es }}s</span>
        @endif
    </span>
    @if (!empty($notes))
    <span class="d-block small text-secondary text-truncate" style="max-width:180px;" title="{{ $tip }}">
        <i class="fa-solid fa-circle-info me-1" style="font-size:0.7rem;"></i>{{ collect($notes)->pluck('note')->implode(' · ') }}
    </span>
    @endif
@else
    <span class="text-muted">—</span>
@endif
