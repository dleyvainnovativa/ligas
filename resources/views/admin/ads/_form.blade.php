@php
    $currentScope = old('scope', $ad && $ad->league_id ? 'league' : 'global');
    $currentLeague = old('league_id', $ad->league_id ?? null);
@endphp

<div class="mb-3">
    <label class="form-label required">Alcance</label>
    <div class="d-flex gap-3">
        <label class="d-flex align-items-center gap-2">
            <input type="radio" name="scope" value="global" data-scope-toggle
                @checked($currentScope === 'global')> Global (todas las ligas)
        </label>
        <label class="d-flex align-items-center gap-2">
            <input type="radio" name="scope" value="league" data-scope-toggle
                @checked($currentScope === 'league')> Liga específica
        </label>
    </div>
</div>

<div class="mb-3" id="league-select-wrap" style="{{ $currentScope === 'league' ? '' : 'display:none;' }}">
    <label class="form-label">Liga</label>
    <select name="league_id" class="form-select">
        <option value="">Selecciona una liga…</option>
        @foreach ($leagues as $league)
        <option value="{{ $league->id }}" @selected((string) $currentLeague === (string) $league->id)>
            {{ $league->name }}
        </option>
        @endforeach
    </select>
</div>

<div class="mb-3">
    <label class="form-label">Título</label>
    <input type="text" name="title" class="form-control" value="{{ old('title', $ad->title ?? '') }}">
</div>

<div class="mb-3">
    <label class="form-label">URL de destino</label>
    <input type="url" name="link_url" class="form-control" value="{{ old('link_url', $ad->link_url ?? '') }}"
        placeholder="https://…">
</div>

<div class="mb-3">
    <label class="form-label {{ $ad ? '' : 'required' }}">Imagen</label>
    @if ($ad && $ad->image_path)
    <div class="mb-2">
        <img src="{{ asset('storage/' . $ad->image_path) }}" alt=""
            class="rounded-3" style="height:80px;object-fit:cover;">
    </div>
    @endif
    <input type="file" name="image" class="form-control" accept="image/*" {{ $ad ? '' : 'required' }}>
    <small class="text-muted">JPG/PNG, máx. 2 MB.@if ($ad) Déjalo vacío para conservar la actual.@endif</small>
</div>

<div class="row g-3 mb-3">
    <div class="col-6">
        <label class="form-label">Posición</label>
        <input type="number" name="position" class="form-control" min="0"
            value="{{ old('position', $ad->position ?? 0) }}">
    </div>
    <div class="col-6 d-flex align-items-end">
        <label class="d-flex align-items-center gap-2">
            <input type="checkbox" name="is_active" value="1"
                @checked(old('is_active', $ad->is_active ?? true))> Activo
        </label>
    </div>
</div>

<script>
    (function () {
        const wrap = document.getElementById('league-select-wrap');
        document.querySelectorAll('[data-scope-toggle]').forEach(function (radio) {
            radio.addEventListener('change', function () {
                wrap.style.display = this.value === 'league' ? '' : 'none';
            });
        });
    })();
</script>
