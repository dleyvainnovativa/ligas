<!doctype html>
<html>

<head>
    <meta charset="utf-8">
    <style>
        @page {
            margin: 28px 32px;
        }

        body {
            font-family: 'DejaVu Sans', sans-serif;
            font-size: 10px;
            color: #1a1a1a;
        }

        .header {
            border-bottom: 3px solid #edc35f;
            padding-bottom: 10px;
            margin-bottom: 16px;
        }

        .header h1 {
            font-size: 20px;
            margin: 0 0 4px 0;
        }

        .header .meta {
            font-size: 9px;
            color: #666;
        }

        .group-block {
            page-break-before: always;
        }

        .group-block:first-of-type {
            page-break-before: avoid;
        }

        h2.group-title {
            font-size: 15px;
            margin: 0 0 10px 0;
            padding: 6px 10px;
            background: #f4f4f5;
            border-left: 4px solid #edc35f;
        }

        h3.section-title {
            font-size: 12px;
            margin: 14px 0 6px 0;
            color: #333;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 10px;
        }

        th {
            background: #f4f4f5;
            font-size: 8.5px;
            text-transform: uppercase;
            letter-spacing: 0.4px;
            padding: 5px 6px;
            border-bottom: 1.5px solid #ddd;
            text-align: left;
        }

        td {
            padding: 5px 6px;
            border-bottom: 1px solid #eee;
            font-size: 9.5px;
        }

        .tc {
            text-align: center;
        }

        .tr {
            text-align: right;
        }

        .rank {
            color: #888;
            font-weight: bold;
            width: 24px;
        }

        .pos {
            color: #2e7d32;
            font-weight: bold;
        }

        .neg {
            color: #c62828;
            font-weight: bold;
        }

        .muted {
            color: #999;
        }

        .jornada-head {
            font-size: 11px;
            font-weight: bold;
            margin: 12px 0 4px 0;
            padding: 4px 8px;
            background: #fafafa;
            border-left: 3px solid #ccc;
        }

        .cancha-head {
            font-size: 9px;
            color: #555;
            margin: 8px 0 3px 0;
            font-weight: bold;
        }

        .cancha-meta {
            font-weight: normal;
            color: #888;
        }

        .standings-final th {
            background: #edc35f;
            color: #2a2a2a;
        }

        .footer-note {
            margin-top: 18px;
            font-size: 8px;
            color: #999;
            text-align: center;
            border-top: 1px solid #eee;
            padding-top: 6px;
        }
    </style>
</head>

<body>

    <div class="header">
        <h1>{{ $league->name }}</h1>
        <div class="meta">
            {{ $league->format === 'pairs' ? 'Parejas' : 'Individual' }}
            · {{ $league->num_jornadas }} jornadas
            · Generado el {{ $generated_at }}
        </div>
    </div>

    @foreach ($groups as $g)
    <div class="group-block">
        <h2 class="group-title">{{ $g['name'] }}</h2>

        {{-- FINAL STANDINGS FIRST (most important, top of the page) --}}
        <h3 class="section-title">Clasificación</h3>
        <table class="standings-final">
            <thead>
                <tr>
                    <th class="rank">#</th>
                    <th>Jugador</th>
                    <th class="tc">Cancha</th>
                    <th class="tc">PJ</th>
                    <th class="tc">G</th>
                    <th class="tc">P</th>
                    <th class="tc">Games</th>
                    <th class="tc">NS</th>
                    <th class="tc">Sup</th>
                    <th class="tr">Pts</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($g['standings'] as $i => $row)
                <tr>
                    <td class="rank">{{ $i + 1 }}</td>
                    <td><strong>{{ $row['name'] }}</strong></td>
                    <td class="tc">{{ $row['current_position'] ?? '—' }}</td>
                    <td class="tc">{{ $row['jornadas_played'] }}</td>
                    <td class="tc">{{ $row['rounds'] ?? 0 }}</td>
                    <td class="tc">{{ $row['rounds_lost'] ?? 0 }}</td>
                    <td class="tc">
                        {{ $row['won'] }}–{{ $row['lost'] }}
                        @if (($row['penalty'] ?? 0) > 0)
                        <span class="neg">({{ $row['won_raw'] }}−{{ $row['penalty'] }})</span>
                        @endif
                    </td>
                    <td class="tc {{ ($row['no_shows'] ?? 0) > 0 ? 'neg' : 'muted' }}">{{ $row['no_shows'] ?? 0 }}</td>
                    <td class="tc {{ ($row['suplentes'] ?? 0) > 0 ? '' : 'muted' }}">{{ $row['suplentes'] ?? 0 }}</td>
                    <td class="tr {{ $row['diff'] > 0 ? 'pos' : ($row['diff'] < 0 ? 'neg' : '') }}">
                        {{ $row['diff'] > 0 ? '+' : '' }}{{ $row['diff'] }}
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>

        {{-- PER-JORNADA RESULTS --}}
        <h3 class="section-title">Resultados por jornada</h3>

        @forelse ($g['jornadas'] as $j)
        <div class="jornada-head">
            Jornada {{ $j['number'] }}
            @unless ($j['complete']) <span class="cancha-meta">(en curso)</span> @endunless
        </div>

        @foreach ($j['canchas'] as $c)
        <div class="cancha-head">
            {{ $c['label'] }}
            <span class="cancha-meta">
                @if ($c['date_display']) · {{ $c['date_display'] }} @endif
                @if ($c['time_slot']) {{ $c['time_slot'] }} @endif
                @if ($c['pista']) · {{ $c['pista'] }}@if ($c['sede']), {{ $c['sede'] }}@endif @endif
            </span>
        </div>
        <table>
            <thead>
                <tr>
                    <th class="rank">#</th>
                    <th>Jugador</th>
                    <th class="tc">G</th>
                    <th class="tc">P</th>
                    <th class="tc">Games</th>
                    <th class="tc">NS</th>
                    <th class="tc">Sup</th>
                    <th class="tr">Pts</th>
                    <th class="tc">Mov</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($c['players'] as $p)
                <tr>
                    <td class="rank">{{ $p['rank'] }}</td>
                    <td>{{ $p['name'] }}</td>
                    <td class="tc">{{ $p['rounds'] ?? 0 }}</td>
                    <td class="tc">{{ $p['rounds_lost'] ?? 0 }}</td>
                    <td class="tc">
                        {{ $p['won'] }}–{{ $p['lost'] }}
                        @if (($p['penalty'] ?? 0) > 0)
                        <span class="neg">({{ $p['won_raw'] }}−{{ $p['penalty'] }})</span>
                        @endif
                    </td>
                    <td class="tc {{ ($p['no_shows'] ?? 0) > 0 ? 'neg' : 'muted' }}">{{ $p['no_shows'] ?? 0 }}</td>
                    <td class="tc {{ ($p['suplentes'] ?? 0) > 0 ? '' : 'muted' }}">{{ $p['suplentes'] ?? 0 }}</td>
                    <td class="tr {{ $p['diff'] > 0 ? 'pos' : ($p['diff'] < 0 ? 'neg' : '') }}">
                        {{ $p['diff'] > 0 ? '+' : '' }}{{ $p['diff'] }}
                    </td>
                    <td class="tc">
                        @if ($j['complete'])
                        @if ($p['movement'] === 'up') <span class="pos">▲</span>
                        @elseif ($p['movement'] === 'down') <span class="neg">▼</span>
                        @else <span class="muted">–</span>
                        @endif
                        @else
                        <span class="muted">–</span>
                        @endif
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
        @endforeach
        @empty
        <p class="muted">Sin jornadas registradas.</p>
        @endforelse
    </div>
    @endforeach

    <div class="footer-note">
        {{ $league->name }} · Generado el {{ $generated_at }}
    </div>

</body>

</html>