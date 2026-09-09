@extends('layouts.public')
@section('title', "Clasificación — {$league->name}")

@section('content')
<section class="public-section">
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
        <h2 class="mb-0">Clasificación</h2>

        @if (!empty($jornada_numbers))
        <div class="clasif-jornada-picker">
            <label for="clasif-jornada" class="form-label small text-muted mb-1 d-block">Ver</label>
            <select id="clasif-jornada" class="form-select form-select-sm"
                onchange="if (this.value) { window.location = this.value; }">
                <option value="{{ route('public.clasificacion', $league->slug) }}"
                    @selected($selected===null)>Tabla General</option>
                @foreach ($jornada_numbers as $n)
                <option value="{{ route('public.clasificacion', $league->slug) }}?jornada={{ $n }}"
                    @selected($selected===$n)>Jornada {{ $n }}</option>
                @endforeach
            </select>
        </div>
        @endif
    </div>

    @if ($payload['mode'] === 'jornada')
    {{-- Single jornada: one flat leaderboard across all groups --}}
    <div class="clasif-scope-note mb-3">
        <i class="fa-solid fa-flag-checkered me-1"></i>
        Clasificación de la <strong>Jornada {{ $payload['jornada'] }}</strong> (todos los grupos)
    </div>
    @if (empty($payload['standings']))
    <div class="public-empty">Aún no hay datos para esta jornada.</div>
    @else
    @include('public.league._standings-table', ['standings' => $payload['standings']])
    @endif
    @else
    {{-- Resumen: per-group season standings (tabbed) --}}
    @if (count($payload['groups']) > 1)
    <ul class="nav nav-pills public-tabs mb-3" role="tablist">
        @foreach ($payload['groups'] as $i => $g)
        <li class="nav-item">
            <button class="nav-link {{ $i === 0 ? 'active' : '' }}"
                data-bs-toggle="tab"
                data-bs-target="#group-tab-{{ $g['group']->id }}"
                type="button">
                {{ $g['group']->name }}
            </button>
        </li>
        @endforeach
    </ul>
    @endif

    <div class="tab-content">
        @foreach ($payload['groups'] as $i => $g)
        <div class="tab-pane fade {{ $i === 0 ? 'show active' : '' }}" id="group-tab-{{ $g['group']->id }}">
            @if (empty($g['standings']))
            <div class="public-empty">Aún no hay datos para esta división.</div>
            @else
            @include('public.league._standings-table', ['standings' => $g['standings']])
            @endif
        </div>
        @endforeach
    </div>
    @endif
</section>
@endsection