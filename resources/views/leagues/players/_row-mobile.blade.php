<div class="player-row player-card" data-player-id="{{ $player->id }}" data-layout="mobile">
    <div class="player-card-header">
        <div class="player-card-avatar">
            {{ mb_substr($player->full_name ?: '?', 0, 1) }}
        </div>
        <div class="flex-grow-1 min-w-0">
            <input type="text" class="form-control form-control-sm field-name"
                value="{{ $player->full_name }}" placeholder="Nombre">
        </div>
        <span class="player-status-dot status-{{ $player->payment_status }}"
            title="{{ $player->payment_status }}"></span>
    </div>

    <div class="player-card-body">
        <div class="player-field">
            <label><i class="fa-solid fa-envelope text-secondary"></i> Email</label>
            <input type="email" class="form-control form-control-sm field-email"
                value="{{ $player->email }}" placeholder="email@ejemplo.com">
        </div>

        <div class="player-field">
            <label><i class="fa-solid fa-phone text-secondary"></i> Teléfono</label>
            <input type="text" class="form-control form-control-sm field-phone"
                value="{{ $player->phone }}" placeholder="—">
        </div>

        <div class="row g-2">
            <div class="col-7">
                <div class="player-field mb-0">
                    <label><i class="fa-solid fa-dollar-sign text-secondary"></i> Pagado</label>
                    <div class="input-group input-group-sm">
                        <span class="input-group-text">$</span>
                        <input type="number" min="0" step="0.01"
                            class="form-control form-control-sm field-paid"
                            value="{{ $player->paid_amount }}">
                    </div>
                </div>
            </div>
            <div class="col-5">
                <div class="player-field mb-0">
                    <label><i class="fa-solid fa-circle-check text-secondary"></i> Estado</label>
                    <select class="form-select form-select-sm field-status">
                        <option value="unpaid" @selected($player->payment_status === 'unpaid')>No pagado</option>
                        <option value="partial" @selected($player->payment_status === 'partial')>Parcial</option>
                        <option value="paid" @selected($player->payment_status === 'paid')>Pagado</option>
                    </select>
                </div>
            </div>
        </div>
    </div>

    <div class="player-card-footer">
        <button class="btn btn-sm btn-outline-danger delete-player">
            <i class="fa-solid fa-trash me-1"></i> Eliminar
        </button>
        <button class="btn btn-sm btn-primary save-player">
            <i class="fa-solid fa-floppy-disk me-1"></i> Guardar
        </button>
    </div>
</div>