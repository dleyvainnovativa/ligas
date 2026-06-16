@php
// Show arrows only when complete AND not the first jornada doesn't matter here —
// movement is forward-looking, so it's valid as soon as results exist.
// We gate on completion so arrows reflect final results.
$showArrows = $isComplete;
@endphp

<div class="jornada-standings">
    @foreach ($breakdown as $cancha)
    <div class="jstand-cancha">
        <div class="jstand-cancha-head">
            <span class="jstand-cancha-label">
                <i class="fa-solid fa-table-cells text-muted me-1"></i>
                {{ $cancha['label'] }}
            </span>
            @if ($cancha['is_top'])
            <span class="jstand-tier-tag is-top">Cancha alta</span>
            @elseif ($cancha['is_bottom'])
            <span class="jstand-tier-tag is-bottom">Cancha baja</span>
            @endif
        </div>

        <table class="jstand-table">
            <thead>
                <tr>
                    <th style="width:36px;">#</th>
                    <th>Jugador</th>
                    <th class="text-end">Ganados</th>
                    <th class="text-end">Dif.</th>
                    @if ($showArrows)
                    <th class="text-center" style="width:60px;">Mov.</th>
                    @endif
                </tr>
            </thead>
            <tbody>
                @foreach ($cancha['players'] as $p)
                <tr class="jstand-row movement-{{ $p['movement'] }}">
                    <td class="jstand-rank">{{ $p['rank'] }}</td>
                    <td class="jstand-name">{{ $playerNames[$p['player_id']] ?? '—' }}</td>
                    <td class="text-end font-mono">{{ $p['won'] }}</td>
                    <td class="text-end font-mono {{ $p['diff'] > 0 ? 'text-success' : ($p['diff'] < 0 ? 'text-danger' : 'text-muted') }}">
                        {{ $p['diff'] > 0 ? '+' : '' }}{{ $p['diff'] }}
                    </td>
                    @if ($showArrows)
                    <td class="text-center">
                        @switch($p['movement'])
                        @case('up')
                        <span class="jstand-move up" title="Sube de cancha">
                            <i class="fa-solid fa-arrow-up"></i>
                        </span>
                        @break
                        @case('down')
                        <span class="jstand-move down" title="Baja de cancha">
                            <i class="fa-solid fa-arrow-down"></i>
                        </span>
                        @break
                        @default
                        <span class="jstand-move stay" title="Se mantiene">
                            <i class="fa-solid fa-minus"></i>
                        </span>
                        @endswitch
                    </td>
                    @endif
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    @endforeach

    @if ($showArrows)
    <div class="jstand-legend">
        <span><i class="fa-solid fa-arrow-up text-success"></i> Sube a la cancha de arriba</span>
        <span><i class="fa-solid fa-arrow-down text-danger"></i> Baja a la cancha de abajo</span>
        <span><i class="fa-solid fa-minus text-muted"></i> Se mantiene</span>
    </div>
    @endif
</div>