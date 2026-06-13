<div class="sede-card card-soft" data-sede-id="{{ $sede->id }}">
    <div class="sede-card-header">
        <div class="flex-grow-1 min-w-0 d-flex align-items-center gap-2">
            <i class="fa-solid fa-location-dot text-secondary"></i>
            <input type="text" class="form-control form-control-sm sede-name"
                value="{{ $sede->name }}" placeholder="Nombre de la sede" maxlength="120">
        </div>
        <input type="text" class="form-control form-control-sm sede-address"
            value="{{ $sede->address }}" placeholder="Dirección (opcional)"
            style="max-width: 240px;" maxlength="200">
        <button class="btn btn-sm btn-outline-secondary save-sede"
            data-url="{{ route('leagues.sedes.update', [$league, $sede]) }}"
            title="Guardar sede">
            <i class="fa-solid fa-floppy-disk"></i>
        </button>
        <button class="btn btn-sm btn-outline-danger delete-sede"
            data-url="{{ route('leagues.sedes.destroy', [$league, $sede]) }}"
            title="Eliminar sede">
            <i class="fa-solid fa-trash"></i>
        </button>
    </div>

    <div class="sede-card-body">
        <div class="d-flex justify-content-between align-items-center mb-2">
            <small class="text-secondary fw-semibold text-uppercase" style="letter-spacing:0.05em;">
                <i class="fa-solid fa-table-cells me-1"></i>
                Pistas
            </small>
            <button class="btn btn-sm btn-outline-primary add-pista"
                data-url="{{ route('leagues.pistas.store', [$league, $sede]) }}">
                <i class="fa-solid fa-plus me-1"></i> Nueva pista
            </button>
        </div>

        <div class="pistas-list" data-sede-id="{{ $sede->id }}">
            @forelse ($sede->pistas as $pista)
            <div class="pista-row" data-pista-id="{{ $pista->id }}">
                <i class="fa-solid fa-table-cells text-muted small"></i>
                <input type="text" class="form-control form-control-sm pista-name"
                    value="{{ $pista->name }}" placeholder="Nombre de la pista" maxlength="80">
                <select class="form-select form-select-sm pista-surface" style="max-width: 140px;">
                    @foreach (['indoor' => 'Indoor', 'outdoor' => 'Outdoor', 'cristal' => 'Cristal', 'panoramic' => 'Panorámica'] as $val => $label)
                    <option value="{{ $val }}" @selected($pista->surface === $val)>{{ $label }}</option>
                    @endforeach
                </select>
                <button class="btn btn-sm btn-link save-pista p-1"
                    data-url="{{ route('leagues.pistas.update', [$league, $sede, $pista]) }}"
                    title="Guardar pista">
                    <i class="fa-solid fa-floppy-disk"></i>
                </button>
                <button class="btn btn-sm btn-link text-danger delete-pista p-1"
                    data-url="{{ route('leagues.pistas.destroy', [$league, $sede, $pista]) }}"
                    title="Eliminar pista">
                    <i class="fa-solid fa-xmark"></i>
                </button>
            </div>
            @empty
            <div class="pista-empty text-muted small py-2 px-2">
                Aún no hay pistas en esta sede.
            </div>
            @endforelse
        </div>
    </div>
</div>