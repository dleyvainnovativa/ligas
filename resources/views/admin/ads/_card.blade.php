@php $showLeague = $showLeague ?? false; @endphp
<div class="col-md-6 col-lg-4">
    <div class="card-soft p-3 h-100 d-flex flex-column">
        <img src="{{ asset('storage/' . $ad->image_path) }}" alt="{{ $ad->title }}"
            class="rounded-3 mb-3" style="width:100%;height:120px;object-fit:cover;">

        <div class="d-flex align-items-center gap-2 mb-1">
            @if ($ad->is_active)
            <span class="badge bg-success">Activo</span>
            @else
            <span class="badge bg-secondary">Inactivo</span>
            @endif
            <small class="text-muted">Posición {{ $ad->position }}</small>
        </div>

        <strong>{{ $ad->title ?? 'Sin título' }}</strong>

        @if ($showLeague)
        <small class="text-muted">{{ $ad->league?->name ?? '—' }}</small>
        @endif

        @if ($ad->link_url)
        <small class="text-truncate"><a href="{{ $ad->link_url }}" target="_blank" rel="noopener">{{ $ad->link_url }}</a></small>
        @endif

        <div class="mt-auto pt-3 d-flex gap-2">
            <a href="{{ route('admin.ads.edit', $ad) }}" class="btn btn-sm btn-outline-secondary flex-grow-1">
                <i class="fa-solid fa-pencil me-1"></i> Editar
            </a>
            <form method="POST" action="{{ route('admin.ads.destroy', $ad) }}"
                onsubmit="return confirm('¿Eliminar este anuncio?');">
                @csrf @method('DELETE')
                <button type="submit" class="btn btn-sm btn-outline-danger">
                    <i class="fa-solid fa-trash"></i>
                </button>
            </form>
        </div>
    </div>
</div>
