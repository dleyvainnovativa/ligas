@extends('layouts.public')
@section('title', "Jornada {$payload['number']} — {$league->name}")

@section('content')
<section class="public-section">
    <div class="public-jornada-header">
        <a href="{{ route('public.calendario', $league->slug) }}" class="public-back-link">
            <i class="fa-solid fa-arrow-left"></i> Calendario
        </a>
        <h2>Jornada {{ $payload['number'] }}</h2>
        @if ($payload['date_display'])
        <p class="text-secondary mb-0">{{ $payload['date_display'] }}</p>
        @endif
    </div>

    @if (count($payload['groups']) > 1)
    <ul class="nav nav-pills public-tabs mb-3" role="tablist">
        @foreach ($payload['groups'] as $i => $g)
        <li class="nav-item">
            <button class="nav-link {{ $i === 0 ? 'active' : '' }}"
                data-bs-toggle="tab"
                data-bs-target="#jornada-group-{{ $i }}"
                type="button">
                {{ $g['group_name'] }}
            </button>
        </li>
        @endforeach
    </ul>
    @endif

    <div class="tab-content">
        @foreach ($payload['groups'] as $i => $g)
        <div class="tab-pane fade {{ $i === 0 ? 'show active' : '' }}" id="jornada-group-{{ $i }}">

            @if (empty($g['canchas']))
            <div class="public-empty">Aún no hay canchas en esta jornada.</div>
            @else
            <div class="cancha-selector" data-cancha-group="{{ $i }}">
                {{-- Cancha dropdown --}}
                <label class="cancha-selector-label">Cancha</label>
                <select class="form-select cancha-dropdown" data-group="{{ $i }}">
                    @foreach ($g['canchas'] as $ci => $cancha)
                    <option value="{{ $i }}-{{ $ci }}" {{ $ci === 0 ? 'selected' : '' }}>
                        {{ $cancha['label'] }}
                        @if ($cancha['status'] === 'completed') · ✓ @endif
                    </option>
                    @endforeach
                </select>
            </div>

            {{-- Cancha panels (only one visible at a time) --}}
            @foreach ($g['canchas'] as $ci => $cancha)
            <div class="cancha-panel {{ $ci === 0 ? '' : 'd-none' }}"
                data-cancha-panel="{{ $i }}-{{ $ci }}">

                {{-- Inner tabs: Standings | Partidos --}}
                <ul class="nav nav-pills cancha-inner-tabs mb-3" role="tablist">
                    <li class="nav-item">
                        <button class="nav-link active" data-bs-toggle="tab"
                            data-bs-target="#matches-{{ $i }}-{{ $ci }}" type="button">
                            <i class="fa-solid fa-table-tennis-paddle-ball me-1"></i> Partidos
                        </button>
                    </li>
                    <li class="nav-item">
                        <button class="nav-link" data-bs-toggle="tab"
                            data-bs-target="#standings-{{ $i }}-{{ $ci }}" type="button">
                            <i class="fa-solid fa-ranking-star me-1"></i> Standings
                        </button>
                    </li>
                </ul>

                <div class="tab-content">
                    {{-- Matches tab --}}
                    <div class="tab-pane fade show active" id="matches-{{ $i }}-{{ $ci }}">
                        @include('public.league._cancha-card', [
                        'm' => $cancha,
                        'showScore' => $cancha['status'] === 'completed',
                        ])
                    </div>
                    {{-- Standings tab --}}
                    <div class="tab-pane fade " id="standings-{{ $i }}-{{ $ci }}">
                        @if (!empty($cancha['breakdown']))
                        @include('public.league._cancha-standings', [
                        'cancha' => $cancha['breakdown'],
                        'complete' => $g['complete'],
                        ])
                        @else
                        <div class="public-empty">Sin resultados todavía.</div>
                        @endif
                    </div>

                </div>
            </div>
            @endforeach
            @endif
        </div>
        @endforeach
    </div>

    {{-- Prev / next --}}
    <div class="public-jornada-nav">
        @if ($payload['prev_number'])
        <a href="{{ route('public.jornada', [$league->slug, $payload['prev_number']]) }}" class="btn btn-outline-secondary">
            <i class="fa-solid fa-arrow-left me-1"></i> Jornada {{ $payload['prev_number'] }}
        </a>
        @else
        <span></span>
        @endif

        @if ($payload['next_number'])
        <a href="{{ route('public.jornada', [$league->slug, $payload['next_number']]) }}" class="btn btn-outline-secondary">
            Jornada {{ $payload['next_number'] }} <i class="fa-solid fa-arrow-right ms-1"></i>
        </a>
        @endif
    </div>
</section>

{{-- propose modal + context, as before --}}
@include('public.league._propose-modal')
@php
$proposeRounds = collect();
foreach ($payload['groups'] as $g) {
foreach ($g['canchas'] as $cancha) {
foreach ($cancha['rounds'] as $round) {
$proposeRounds->push(array_merge($round, [
'date_display' => $cancha['date_display'] ?? null,
'time_slot' => $cancha['time_slot'] ?? null,
]));
}
}
}
@endphp
@include('public.league._propose-context', ['proposeRounds' => $proposeRounds])
@endsection