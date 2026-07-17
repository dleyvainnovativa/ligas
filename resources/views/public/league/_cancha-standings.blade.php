@php $showArrows = $complete; @endphp

<div class="jstand-cancha" style="border:0;">
    @if ($cancha['is_top'] || $cancha['is_bottom'])
    <div class="mb-2">
        @if ($cancha['is_top'])
        <span class="jstand-tier-tag is-top">Cancha alta</span>
        @elseif ($cancha['is_bottom'])
        <span class="jstand-tier-tag is-bottom">Cancha baja</span>
        @endif
    </div>
    @endif

    {{-- Desktop --}}
    <div class="table-responsive d-none d-md-block">
        <table class="jstand-table">
            <thead>
                <tr>
                    <th style="width:32px;">#</th>
                    <th>Jugador</th>
                    <th class="text-center">Sets</th>
                    <th class="text-center">Games</th>
                    <th class="text-center" title="No shows">NS</th>
                    <th class="text-center" title="Suplentes">Sup</th>
                    <th class="text-end">Pts</th>
                    @if ($showArrows)<th class="text-center" style="width:48px;"></th>@endif
                </tr>
            </thead>
            <tbody>
                @foreach ($cancha['players'] as $p)
                <tr class="jstand-row movement-{{ $p['movement'] }}">
                    <td class="jstand-rank">{{ $p['rank'] }}</td>
                    <td class="jstand-name">{{ $p['name'] }}</td>
                    <td class="text-center font-mono">{{ $p['rounds'] }}</td>
                    <td class="text-center font-mono">
                        {{ $p['won'] }}–{{ $p['lost'] }}
                        @if (($p['penalty'] ?? 0) > 0)
                        <small class="text-danger d-block" style="font-size:10px;line-height:1;">
                            {{ $p['won_raw'] }} − {{ $p['penalty'] }}
                        </small>
                        @endif
                    </td>
                    <td class="text-center font-mono {{ ($p['no_shows'] ?? 0) > 0 ? 'text-danger' : 'text-muted' }}">
                        {{ $p['no_shows'] ?? 0 }}
                    </td>
                    <td class="text-center font-mono {{ ($p['suplentes'] ?? 0) > 0 ? 'text-warning' : 'text-muted' }}">
                        {{ $p['suplentes'] ?? 0 }}
                    </td>
                    <td class="text-end font-mono fw-bold {{ $p['diff'] > 0 ? 'text-success' : ($p['diff'] < 0 ? 'text-danger' : 'text-muted') }}">
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

    {{-- Mobile --}}
    <div class="d-md-none d-flex flex-column gap-2">
        @foreach ($cancha['players'] as $p)
        <div class="jstand-mobile-row movement-{{ $p['movement'] }}">
            <span class="jstand-mobile-rank">{{ $p['rank'] }}</span>
            <div class="jstand-mobile-info">
                <strong>{{ $p['name'] }}</strong>
                <small class="text-muted">{{ $p['won'] }}–{{ $p['lost'] }} games</small>
                @if (($p['no_shows'] ?? 0) > 0 || ($p['suplentes'] ?? 0) > 0)
                <small class="standing-flags">
                    @if (($p['no_shows'] ?? 0) > 0)
                    <span class="flag-pill is-ns">NS {{ $p['no_shows'] }}</span>
                    @endif
                    @if (($p['suplentes'] ?? 0) > 0)
                    <span class="flag-pill is-sup">Sup {{ $p['suplentes'] }}</span>
                    @endif
                    @if (($p['penalty'] ?? 0) > 0)
                    <span class="flag-pill is-pen">−{{ $p['penalty'] }}</span>
                    @endif
                </small>
                @endif
            </div>
            <div class="jstand-mobile-pts">
                <strong class="{{ $p['diff'] > 0 ? 'text-success' : ($p['diff'] < 0 ? 'text-danger' : '') }}">
                    {{ $p['diff'] > 0 ? '+' : '' }}{{ $p['diff'] }}
                </strong>
                @if ($showArrows)
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
                @endif
            </div>
        </div>
        @endforeach
    </div>

    @if ($showArrows)
    <div class="jstand-legend mt-2">
        <span><i class="fa-solid fa-arrow-up text-success"></i> Sube</span>
        <span><i class="fa-solid fa-arrow-down text-danger"></i> Baja</span>
        <span><i class="fa-solid fa-minus text-muted"></i> Se mantiene</span>
    </div>
    @endif
</div>