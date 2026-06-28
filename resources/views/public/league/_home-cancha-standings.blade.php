@php $showArrows = $complete; @endphp

<div class="jstand-cancha tinted-cancha" style="--cancha-hue: {{ $cancha['tint_hue'] ?? 45 }};">
    <div class="jstand-cancha-head tinted-head">
        <div class="tinted-head-main">
            <span class="jstand-cancha-label">
                <i class="fa-solid fa-table-cells"></i>
                {{ $cancha['label'] }}
            </span>
            @if ($cancha['is_top'])
            <span class="jstand-tier-tag is-top">Cancha alta</span>
            @elseif ($cancha['is_bottom'])
            <span class="jstand-tier-tag is-bottom">Cancha baja</span>
            @endif
        </div>

        @if (!empty($cancha['date_display']) || !empty($cancha['pista']))
        <div class="tinted-head-meta">
            @if (!empty($cancha['date_display']))
            <span><i class="fa-solid fa-calendar-day"></i> {{ $cancha['date_display'] }}@if (!empty($cancha['time_slot'])) · {{ $cancha['time_slot'] }}@endif</span>
            @endif
            @if (!empty($cancha['pista']))
            <span><i class="fa-solid fa-location-dot"></i> {{ $cancha['pista'] }}@if (!empty($cancha['sede'])) · {{ $cancha['sede'] }}@endif</span>
            @endif
        </div>
        @endif
    </div>

    <table class="jstand-table">
        <thead>
            <tr>
                <th style="width:32px;">#</th>
                <th>Jugador</th>
                <th class="text-end">Gan.</th>
                <th class="text-end">Dif.</th>
                @if ($showArrows)<th class="text-center" style="width:48px;"></th>@endif
            </tr>
        </thead>
        <tbody>
            @foreach ($cancha['players'] as $p)
            <tr class="jstand-row movement-{{ $p['movement'] }}">
                <td class="jstand-rank">{{ $p['rank'] }}</td>
                <td class="jstand-name">{{ $p['name'] }}</td>
                <td class="text-end font-mono">{{ $p['won'] }}</td>
                <td class="text-end font-mono {{ $p['diff'] > 0 ? 'text-success' : ($p['diff'] < 0 ? 'text-danger' : 'text-muted') }}">
                    {{ $p['diff'] > 0 ? '+' : '' }}{{ $p['diff'] }}
                </td>
                @if ($showArrows)
                <td class="text-center">
                    @switch($p['movement'])
                    @case('up')
                    <span class="jstand-move up"><i class="fa-solid fa-arrow-up"></i></span>
                    @break
                    @case('down')
                    <span class="jstand-move down"><i class="fa-solid fa-arrow-down"></i></span>
                    @break
                    @default
                    <span class="jstand-move stay"><i class="fa-solid fa-minus"></i></span>
                    @endswitch
                </td>
                @endif
            </tr>
            @endforeach
        </tbody>
    </table>
</div>