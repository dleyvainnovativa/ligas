<tr class="player-row" data-player-id="{{ $player->id }}" data-layout="desktop">
    <td>
        <input type="text" class="form-control form-control-sm field-name"
            value="{{ $player->full_name }}" placeholder="Nombre">
    </td>
    <td>
        <input type="email" class="form-control form-control-sm field-email"
            value="{{ $player->email }}" placeholder="email@ejemplo.com">
    </td>
    <td>
        <input type="text" class="form-control form-control-sm field-phone"
            value="{{ $player->phone }}" placeholder="—">
    </td>
    <td>
        <div class="input-group input-group-sm">
            <span class="input-group-text">$</span>
            <input type="number" min="0" step="0.01"
                class="form-control form-control-sm field-paid"
                value="{{ $player->paid_amount }}">
        </div>
    </td>
    <td>
        <select class="form-select form-select-sm field-status">
            <option value="unpaid" @selected($player->payment_status === 'unpaid')>No pagado</option>
            <option value="partial" @selected($player->payment_status === 'partial')>Parcial</option>
            <option value="paid" @selected($player->payment_status === 'paid')>Pagado</option>
        </select>
    </td>
    <td class="text-end" style="white-space:nowrap;">
        <button class="btn btn-sm btn-outline-secondary save-player" title="Guardar">
            <i class="fa-solid fa-floppy-disk"></i>
        </button>
        <button class="btn btn-sm btn-outline-danger delete-player" title="Eliminar">
            <i class="fa-solid fa-trash"></i>
        </button>
    </td>
</tr>