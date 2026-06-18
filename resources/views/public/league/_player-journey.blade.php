@php
// Determine the range of cancha positions to size the ladder
$positions = collect($history)->pluck('cancha_position')->filter();
$maxPos = $positions->max() ?? 1; // deepest court (highest number)
$minPos = 1; // top court is always 1
$rows = $maxPos - $minPos + 1;
@endphp

<div class="journey-chart" style="--journey-rows: {{ $rows }};">
    {{-- Y-axis labels (cancha numbers) --}}
    <div class="journey-yaxis">
        @for ($pos = $minPos; $pos <= $maxPos; $pos++)
            <div class="journey-ylabel">C{{ $pos }}</div>
    @endfor
</div>

{{-- The plotted points + connecting line --}}
<div class="journey-plot">
    @foreach ($history as $i => $h)
    @php
    // vertical position: cancha 1 at top (row 0), deeper canchas lower
    $rowIndex = $h['cancha_position'] - 1;
    @endphp
    <div class="journey-col">
        <div class="journey-point-wrap" style="--row-index: {{ $rowIndex }};">
            <div class="journey-point movement-{{ $h['movement'] ?? 'none' }}"
                title="J{{ $h['jornada'] }}: {{ $h['cancha_label'] }}, {{ $h['won'] }} ganados">
                {{ $h['won'] }}
            </div>
        </div>
        <div class="journey-xlabel">J{{ $h['jornada'] }}</div>
    </div>
    @endforeach
</div>
</div>

<div class="jstand-legend mt-3">
    <span><i class="fa-solid fa-circle text-success" style="font-size:8px;"></i> Subió</span>
    <span><i class="fa-solid fa-circle text-danger" style="font-size:8px;"></i> Bajó</span>
    <span><i class="fa-solid fa-circle text-muted" style="font-size:8px;"></i> Se mantuvo</span>
    <span class="text-muted">· El número es juegos ganados</span>
</div>