@extends('layouts.public')
@section('title', ($league->format === \App\Models\League::FORMAT_PAIRS ? 'Parejas' : 'Jugadores') . " — {$league->name}")

@section('content')
<section class="public-section">

@if ($league->format === \App\Models\League::FORMAT_PAIRS)
{{-- ================= DOUBLES: pairs grouped by division ================= --}}
    <h2 class="mb-3">Parejas</h2>

    <div class="public-players-controls" id="pairs-controls">
        <input type="search" id="pair-search" class="form-control"
            placeholder="Buscar pareja o jugador…" autocomplete="off">
    </div>

    <div class="text-muted small mb-3">
        <span id="pair-count">{{ $payload['total'] }}</span> parejas
    </div>

    @php $multi = count($payload['groups']) > 1; @endphp

    @forelse ($payload['groups'] as $g)
    <div class="pairs-division" data-division>
        @if ($multi)
        <h3 class="pairs-division-title">
            <i class="fa-solid fa-layer-group text-secondary me-1"></i>{{ $g['group_name'] }}
        </h3>
        @endif
        <div class="d-flex flex-column gap-2">
            @forelse ($g['pairs'] as $i => $p)
            <div class="public-player-row pairs-row"
                data-search="{{ Str::lower(($p['name'] ?? '') . ' ' . ($p['player_a'] ?? '') . ' ' . ($p['player_b'] ?? '')) }}">
                <div class="public-player-avatar">{{ $i + 1 }}</div>
                <div class="public-player-info">
                    <strong>{{ $p['name'] }}</strong>
                    <small class="text-muted">
                        {{ $p['player_a'] ?? '?' }} <i class="fa-solid fa-plus mx-1" style="font-size:0.6rem;"></i> {{ $p['player_b'] ?? '?' }}
                        @if ($p['current_position']) · Cancha {{ $p['current_position'] }}@endif
                    </small>
                </div>
                <div class="public-player-stat">
                    <strong>{{ $p['won'] }}</strong>
                    <small>ganados</small>
                </div>
            </div>
            @empty
            <div class="text-muted small">Sin parejas en esta división.</div>
            @endforelse
        </div>
    </div>
    @empty
    <div class="public-empty">Aún no hay parejas registradas.</div>
    @endforelse

    <div id="pairs-empty" class="public-empty mt-3" style="display:none;">
        No se encontraron parejas.
    </div>

    <script>
        (function () {
            const search = document.getElementById('pair-search');
            const rows = Array.from(document.querySelectorAll('.pairs-row'));
            const divisions = Array.from(document.querySelectorAll('.pairs-division'));
            const empty = document.getElementById('pairs-empty');
            const count = document.getElementById('pair-count');
            if (!search) return;

            search.addEventListener('input', () => {
                const q = search.value.trim().toLowerCase();
                let visible = 0;
                rows.forEach(r => {
                    const show = !q || (r.dataset.search || '').includes(q);
                    r.style.display = show ? '' : 'none';
                    if (show) visible++;
                });
                // Hide a division whose rows are all filtered out
                divisions.forEach(d => {
                    const anyVisible = d.querySelector('.pairs-row:not([style*="display: none"])');
                    d.style.display = anyVisible ? '' : 'none';
                });
                count.textContent = visible;
                empty.style.display = visible === 0 ? '' : 'none';
            });
        })();
    </script>

@else
{{-- ================= SINGLES: individual players ================= --}}
    <h2 class="mb-3">Jugadores</h2>

    <div class="public-players-controls" id="players-controls">
        <input type="search" id="player-search"
            class="form-control"
            placeholder="Buscar jugador…"
            autocomplete="off">

        <select id="group-filter" class="form-select d-none">
            <option value="">Todas las divisiones</option>
            @foreach ($payload['group_names'] as $name)
            <option value="{{ $name }}">{{ $name }}</option>
            @endforeach
        </select>

        {{-- sort dropdown --}}
        <select id="sort-by" class="form-select">
            <option value="name">Nombre A-Z</option>
            <option value="won">Juegos ganados</option>
            <option value="diff">Diferencia</option>
            <option value="jornadas">Jornadas jugadas</option>
        </select>
    </div>

    <div class="text-muted small mb-3">
        <span id="player-count">{{ $payload['total'] }}</span> jugadores
    </div>

    <div id="players-list" class="d-flex flex-column gap-2">
        @foreach ($payload['players'] as $p)
        <a href="{{ route('public.jugador', [$league->slug, $p['id']]) }}"
            class="public-player-row"
            data-name="{{ Str::lower($p['name']) }}"
            data-group="{{ $p['group_name'] }}"
            data-won="{{ $p['won'] }}"
            data-diff="{{ $p['diff'] }}"
            data-jornadas="{{ $p['jornadas_played'] }}">
            <div class="public-player-avatar">{{ Str::substr($p['name'], 0, 1) }}</div>
            <div class="public-player-info">
                <strong>{{ $p['name'] }}</strong>
                <small class="text-muted">
                    @if ($p['group_name']){{ $p['group_name'] }}@endif
                    @if ($p['current_position']) · Cancha {{ $p['current_position'] }}@endif
                </small>
            </div>
            <div class="public-player-stat">
                <strong>{{ $p['won'] }}</strong>
                <small>ganados</small>
            </div>
        </a>
        @endforeach
    </div>

    <div id="players-empty" class="public-empty mt-3" style="display:none;">
        No se encontraron jugadores.
    </div>

    <script>
        (function() {
            const list = document.getElementById('players-list');
            const search = document.getElementById('player-search');
            const filter = document.getElementById('group-filter');
            const sortBy = document.getElementById('sort-by');
            const empty = document.getElementById('players-empty');
            const count = document.getElementById('player-count');
            const allRows = Array.from(list.querySelectorAll('.public-player-row'));

            function apply() {
                const q = search.value.trim().toLowerCase();
                const group = filter.value;
                const sortKey = sortBy.value;

                let visible = allRows.filter(row => {
                    if (q && !row.dataset.name.includes(q)) return false;
                    if (group && row.dataset.group !== group) return false;
                    return true;
                });

                visible.sort((a, b) => {
                    if (sortKey === 'won') return parseInt(b.dataset.won) - parseInt(a.dataset.won);
                    if (sortKey === 'diff') return parseInt(b.dataset.diff) - parseInt(a.dataset.diff);
                    if (sortKey === 'jornadas') return parseInt(b.dataset.jornadas) - parseInt(a.dataset.jornadas);
                    return a.dataset.name.localeCompare(b.dataset.name);
                });

                allRows.forEach(r => r.remove());
                visible.forEach(r => list.appendChild(r));

                count.textContent = visible.length;
                empty.style.display = visible.length === 0 ? '' : 'none';
            }

            search.addEventListener('input', apply);
            filter.addEventListener('change', apply);
            sortBy.addEventListener('change', apply);
        })();
    </script>
@endif

</section>
@endsection
