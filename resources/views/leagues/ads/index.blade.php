@extends('layouts.app')

@section('title', "Anuncios — {$league->name}")
@section('page-title', $league->name)

@section('content')
@include('leagues.partials._panel-nav', ['active' => 'ads'])

<div class="card-soft p-4 mb-3" id="ads-settings-app"
    data-url="{{ route('leagues.ads.settings', $league) }}">

    <div class="mb-3">
        <h6 class="mb-1">Anuncios</h6>
        <small class="text-secondary">
            Los anuncios los sube y asigna el administrador de PlayWinners. Aquí puedes ver
            los que aparecen en tu liga y decidir si quieres mostrar los anuncios de la red.
        </small>
    </div>

    {{-- Global ads toggle --}}
    <div class="d-flex align-items-center justify-content-between p-3 rounded border mb-4">
        <div class="me-3">
            <strong class="d-block">Mostrar anuncios globales</strong>
            <small class="text-secondary">
                Anuncios de la red PlayWinners que aparecen en todas las ligas. Desactívalos si
                solo quieres mostrar los anuncios exclusivos de tu liga.
            </small>
        </div>
        <div class="form-check form-switch m-0" style="min-width:48px;">
            <input class="form-check-input" type="checkbox" role="switch" id="ads-global-toggle"
                style="width:3rem;height:1.5rem;" @checked($league->ads_show_global)>
        </div>
    </div>

    {{-- League-exclusive ads --}}
    <div class="mb-4">
        <h6 class="small text-secondary mb-2">
            <i class="fa-solid fa-star me-1"></i> Exclusivos de esta liga
        </h6>
        @forelse ($leagueAds as $ad)
        @include('leagues.ads._preview-row', ['ad' => $ad])
        @empty
        <div class="text-secondary small">
            El administrador no ha asignado anuncios exclusivos a esta liga.
        </div>
        @endforelse
    </div>

    {{-- Global ads --}}
    <div id="global-ads-block" class="{{ $league->ads_show_global ? '' : 'opacity-50' }}">
        <h6 class="small text-secondary mb-2">
            <i class="fa-solid fa-globe me-1"></i> Globales (de la red)
            <span id="global-ads-state" class="badge {{ $league->ads_show_global ? 'text-bg-success' : 'text-bg-secondary' }} ms-1">
                {{ $league->ads_show_global ? 'Visibles' : 'Ocultos' }}
            </span>
        </h6>
        @forelse ($globalAds as $ad)
        @include('leagues.ads._preview-row', ['ad' => $ad])
        @empty
        <div class="text-secondary small">No hay anuncios globales activos por ahora.</div>
        @endforelse
    </div>
</div>

<script>
    (function () {
        const app = document.getElementById('ads-settings-app');
        const toggle = document.getElementById('ads-global-toggle');
        if (!app || !toggle) return;

        const block = document.getElementById('global-ads-block');
        const state = document.getElementById('global-ads-state');

        toggle.addEventListener('change', async () => {
            const on = toggle.checked;
            try {
                const { message } = await window.app.api.put(app.dataset.url, { ads_show_global: on });
                window.app.toast.success(message || 'Guardado');
                block.classList.toggle('opacity-50', !on);
                state.textContent = on ? 'Visibles' : 'Ocultos';
                state.className = 'badge ms-1 ' + (on ? 'text-bg-success' : 'text-bg-secondary');
            } catch (err) {
                toggle.checked = !on; // revert on failure
                window.app.toast.error(err.message);
            }
        });
    })();
</script>
@endsection
