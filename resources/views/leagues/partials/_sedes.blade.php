<div class="card-soft p-4" id="sedes-app" data-league-id="{{ $league->id }}">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <div>
            <h6 class="mb-1">Sedes y pistas</h6>
            <small class="text-secondary">Cada sede agrupa las pistas físicas donde se jugarán los partidos.</small>
        </div>
        <button class="btn btn-primary btn-sm" id="add-sede-btn">
            <i class="fa-solid fa-plus me-1"></i> Nueva sede
        </button>
    </div>

    <div id="sedes-list">
        @forelse ($league->sedes as $sede)
        <div class="sede-row" data-sede-id="{{ $sede->id }}">
            <div class="d-flex align-items-center gap-2 mb-2">
                <input type="text" class="form-control form-control-sm sede-name" value="{{ $sede->name }}">
                <input type="text" class="form-control form-control-sm sede-address" placeholder="Dirección (opcional)" value="{{ $sede->address }}">
                <button class="btn btn-sm btn-outline-secondary save-sede" title="Guardar">
                    <i class="fa-solid fa-floppy-disk"></i>
                </button>
                <button class="btn btn-sm btn-outline-danger delete-sede" title="Eliminar">
                    <i class="fa-solid fa-trash"></i>
                </button>
            </div>
            <div class="pistas-list ms-3 mb-3">
                @foreach ($sede->pistas as $pista)
                <div class="pista-row d-flex align-items-center gap-2 mb-1" data-pista-id="{{ $pista->id }}">
                    <i class="fa-solid fa-square-parking text-secondary"></i>
                    <input type="text" class="form-control form-control-sm pista-name" style="max-width:240px;" value="{{ $pista->name }}">
                    <button class="btn btn-sm btn-link save-pista p-0" title="Guardar">
                        <i class="fa-solid fa-check text-success"></i>
                    </button>
                    <button class="btn btn-sm btn-link delete-pista p-0 ms-1" title="Eliminar">
                        <i class="fa-solid fa-xmark text-danger"></i>
                    </button>
                </div>
                @endforeach
                <div class="d-flex gap-2 mt-1">
                    <input type="text" class="form-control form-control-sm new-pista-name" placeholder="Nueva pista…" style="max-width:240px;">
                    <button class="btn btn-sm btn-outline-primary add-pista">
                        <i class="fa-solid fa-plus me-1"></i> Agregar pista
                    </button>
                </div>
            </div>
        </div>
        @empty
        <div class="empty-state py-4">
            <div class="empty-state-icon">
                <i class="fa-solid fa-map-location-dot"></i>
            </div>
            <h6>Aún no hay sedes</h6>
            <p class="small mb-0">Agrega tu primera sede para empezar.</p>
        </div>
        @endforelse
    </div>
</div>