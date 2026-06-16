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
    <a href="{{ route('public.jornada.standings', [$league->slug, $payload['number']]) }}"
        class="btn btn-sm btn-outline-primary mb-2">
        <i class="fa-solid fa-ranking-star me-1"></i> Ver standings de esta jornada
    </a>

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
        <div class="tab-pane fade {{ $i === 0 ? 'show active' : '' }}"
            id="jornada-group-{{ $i }}"
            data-cancha-section>

            @if (empty($g['canchas']))
            <div class="public-empty">Aún no hay canchas en esta jornada.</div>
            @else
            {{-- Quick-jump pill bar (scroll-spy) --}}
            @if (count($g['canchas']) > 3)
            <div class="cancha-pillbar" data-scrollspy>
                @foreach ($g['canchas'] as $cancha)
                @php
                $statusClass = match($cancha['status']) {
                'completed' => 'is-completed',
                'scheduled' => 'is-scheduled',
                default => '',
                };
                @endphp
                <a href="#cancha-{{ $cancha['id'] }}" class="cancha-pill {{ $statusClass }}">
                    @if ($cancha['status'] === 'completed')
                    <i class="fa-solid fa-check"></i>
                    @elseif ($cancha['status'] === 'scheduled')
                    <i class="fa-regular fa-circle"></i>
                    @else
                    <i class="fa-solid fa-circle-question"></i>
                    @endif
                    <span>{{ $cancha['label'] }}</span>
                </a>
                @endforeach
            </div>
            @endif

            {{-- Cards --}}
            <div class="cancha-cards-list">
                @foreach ($g['canchas'] as $cancha)
                <div id="cancha-{{ $cancha['id'] }}" class="cancha-anchor">
                    @include('public.league._cancha-card', ['m' => $cancha, 'showScore' => $cancha['status'] === 'completed'])
                </div>
                @endforeach
            </div>
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
@endsection