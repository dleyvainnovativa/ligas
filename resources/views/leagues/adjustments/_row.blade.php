@php
    $amount = (int) $adj->amount;
    $sign   = $amount > 0 ? '+' : '';
    $unitLabel = $adj->unit === \App\Models\ScoreAdjustment::UNIT_SET ? 'sets' : 'games';
    $toneClass = $amount >= 0 ? 'text-bg-success' : 'text-bg-danger';
@endphp
<div class="adjustment-row d-flex align-items-center gap-3 p-2 border rounded"
    data-adjustment-id="{{ $adj->id }}">
    <div class="flex-grow-1 min-w-0">
        <div class="fw-medium text-truncate">{{ $adj->subject_name }}</div>
        @if ($adj->note)
        <small class="text-secondary text-truncate d-block">{{ $adj->note }}</small>
        @endif
    </div>
    <span class="badge {{ $toneClass }} font-mono">{{ $sign }}{{ $amount }} {{ $unitLabel }}</span>
    <button class="btn btn-icon btn-sm delete-adjustment" title="Eliminar">
        <i class="fa-solid fa-trash"></i>
    </button>
</div>
