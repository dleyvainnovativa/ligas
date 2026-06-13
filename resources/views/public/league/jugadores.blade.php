@extends('layouts.public')
@section('title', "Jugadores — {$league->name}")

@section('content')
<section class="public-section">
    <h2 class="mb-3">Jugadores</h2>

    <div class="public-players-controls" id="players-controls">
        <input type="search" id="player-search"
            class="form-control"
            placeholder="Buscar jugador…"
            autocomplete="off">

        <select id="group-filter" class="form-select">
            <option value="">Todas las divisiones</option>
            @foreach ($payload['group_names'] as $name)
            <option value="{{ $name }}">{{ $name }}</option>
            @endforeach
        </select>

        <select id="sort-by" class="form-select">
            <option value="name">Nombre A-Z</option>
            <option value="points">Puntos (más a menos)</option>
            <option value="matches">Partidos jugados</option>
        </select>
    </div>

    <div class="text-muted small mb-3">
        <span id="player-count">{{ $payload['total'] }}</span> jugadores
    </div>

    <div id="players-list" class="d-flex flex-column gap-2">
        @foreach ($payload['players'] as $p)
        <div class="public-player-row"
            data-name="{{ Str::lower($p['name']) }}"
            data-group="{{ $p['group_name'] }}"
            data-points="{{ $p['points'] ?? 0 }}"
            data-matches="{{ $p['matches_played'] ?? 0 }}">
            <div class="public-player-avatar">{{ Str::substr($p['name'], 0, 1) }}</div>
            <div class="public-player-info">
                <strong>{{ $p['name'] }}</strong>
                @if ($p['group_name'])
                <small class="text-muted">{{ $p['group_name'] }}</small>
                @endif
            </div>
            <div class="public-player-stat">
                <strong>{{ $p['points'] ?? 0 }}</strong>
                <small>pts</small>
            </div>
        </div>
        @endforeach
    </div>

    <div id="players-empty" class="public-empty mt-3" style="display:none;">
        No se encontraron jugadores.
    </div>
</section>

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
                if (sortKey === 'points') return parseInt(b.dataset.points) - parseInt(a.dataset.points);
                if (sortKey === 'matches') return parseInt(b.dataset.matches) - parseInt(a.dataset.matches);
                return a.dataset.name.localeCompare(b.dataset.name);
            });

            // Detach all, re-attach visible in order
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
@endsection