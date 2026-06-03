<div class="ad-row" data-ad-id="{{ $ad->id }}">
    <i class="fa-solid fa-grip-vertical ad-grip text-muted" title="Arrastra para reordenar"></i>

    <div class="ad-thumb" style="background-image: url('{{ $ad->image_url }}');"></div>

    <div class="ad-fields">
        <input type="text" class="form-control form-control-sm ad-title"
            value="{{ $ad->title }}" placeholder="Título (opcional)">
        <input type="url" class="form-control form-control-sm ad-link"
            value="{{ $ad->link_url }}" placeholder="https://… (opcional)">
    </div>

    <div class="ad-actions">
        <div class="form-check form-switch mb-1">
            <input class="form-check-input ad-active" type="checkbox"
                id="ad-active-{{ $ad->id }}" @checked($ad->is_active)>
            <label class="form-check-label small text-secondary" for="ad-active-{{ $ad->id }}">
                Activo
            </label>
        </div>
        <div class="d-flex gap-1">
            <button class="btn btn-icon btn-sm replace-image" title="Reemplazar imagen">
                <i class="fa-solid fa-image"></i>
            </button>
            <button class="btn btn-icon btn-sm save-ad" title="Guardar">
                <i class="fa-solid fa-floppy-disk"></i>
            </button>
            <button class="btn btn-icon btn-sm delete-ad" title="Eliminar">
                <i class="fa-solid fa-trash"></i>
            </button>
        </div>
    </div>
</div>