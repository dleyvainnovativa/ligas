<div class="d-flex align-items-center gap-3 p-2 mb-2 rounded border">
    <div class="ad-thumb flex-shrink-0"
        style="width:120px;height:40px;border-radius:var(--radius-sm);background:var(--surface-page) center/cover no-repeat;
               @if($ad->image_url) background-image:url('{{ $ad->image_url }}'); @endif"></div>
    <div class="flex-grow-1 min-w-0">
        <div class="fw-medium text-truncate">{{ $ad->title ?: 'Sin título' }}</div>
        @if ($ad->link_url)
        <small class="text-secondary text-truncate d-block">
            <i class="fa-solid fa-link me-1"></i>{{ $ad->link_url }}
        </small>
        @endif
    </div>
    <span class="badge {{ $ad->is_active ? 'text-bg-success' : 'text-bg-secondary' }}">
        {{ $ad->is_active ? 'Activo' : 'Inactivo' }}
    </span>
</div>
