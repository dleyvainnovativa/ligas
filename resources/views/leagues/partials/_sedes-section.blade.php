<div class="sedes-app" data-league-id="{{ $league->id }}">
    <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
        <div>
            <h6 class="mb-1">Sedes y pistas</h6>
            <small class="text-secondary">
                Las sedes son los clubes donde se juegan los partidos.
                Dentro de cada sede defines las pistas físicas.
            </small>
        </div>
        <button class="btn btn-primary btn-sm" id="add-sede-btn"
            data-url="{{ route('leagues.sedes.store', $league) }}">
            <i class="fa-solid fa-plus me-1"></i> Nueva sede
        </button>
    </div>

    <div id="sedes-container" class="d-flex flex-column gap-3">
        @forelse ($league->sedes as $sede)
        @include('leagues.partials._sede-card', ['sede' => $sede, 'league' => $league])
        @empty
        <div class="empty-state py-4" id="sedes-empty">
            <div class="empty-state-icon"><i class="fa-solid fa-location-dot"></i></div>
            <h6>Aún no hay sedes</h6>
            <p class="small mb-0">Agrega tu primera sede para empezar a registrar pistas.</p>
        </div>
        @endforelse
    </div>
</div>