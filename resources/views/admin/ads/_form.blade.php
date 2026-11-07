@php
    $isGlobal = $ad ? $ad->is_global : false;
    $currentScope = old('scope', $isGlobal || ! $ad ? 'global' : 'league');
    $selectedLeagues = collect(old('league_ids', $ad ? $ad->leagues->pluck('id')->all() : []))
        ->map(fn ($id) => (string) $id)->all();
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
                @checked($currentScope === 'league')> Ligas específicas
        </label>
    </div>
</div>

<div class="mb-3" id="league-select-wrap" style="{{ $currentScope === 'league' ? '' : 'display:none;' }}">
    <label class="form-label">Ligas</label>
    <div class="d-flex justify-content-between align-items-center mb-2">
        <small class="text-muted">Selecciona una o varias ligas donde se mostrará el anuncio.</small>
        <div class="d-flex gap-2">
            <button type="button" class="btn btn-sm btn-link p-0" data-league-all>Todas</button>
            <button type="button" class="btn btn-sm btn-link p-0 text-muted" data-league-none>Ninguna</button>
        </div>
    </div>
    <div class="border rounded-3 p-2" style="max-height:260px;overflow-y:auto;">
        @forelse ($leagues as $league)
        <label class="d-flex align-items-center gap-2 py-1 px-1 rounded league-check-row">
            <input type="checkbox" name="league_ids[]" value="{{ $league->id }}"
                @checked(in_array((string) $league->id, $selectedLeagues, true))>
            <span>{{ $league->name }}</span>
        </label>
        @empty
        <small class="text-muted">No hay ligas.</small>
        @endforelse
    </div>
    @error('league_ids') <div class="text-danger small mt-1">{{ $message }}</div> @enderror
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
        const boxes = () => wrap.querySelectorAll('input[name="league_ids[]"]');
        wrap.querySelector('[data-league-all]')?.addEventListener('click', function () {
            boxes().forEach(b => b.checked = true);
        });
        wrap.querySelector('[data-league-none]')?.addEventListener('click', function () {
            boxes().forEach(b => b.checked = false);
        });
    })();
</script>
