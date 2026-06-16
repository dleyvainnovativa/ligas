@extends('layouts.public')
@section('title', "Jornada {$payload['number']} Standings — {$league->name}")

@section('content')
<section class="public-section">
    <div class="public-jornada-header">
        <a href="{{ route('public.jornada', [$league->slug, $payload['number']]) }}" class="public-back-link">
            <i class="fa-solid fa-arrow-left"></i> Jornada {{ $payload['number'] }}
        </a>
        <h2>Standings — Jornada {{ $payload['number'] }}</h2>
    </div>

    @if (count($payload['groups']) > 1)
    <ul class="nav nav-pills public-tabs mb-3" role="tablist">
        @foreach ($payload['groups'] as $i => $g)
        <li class="nav-item">
            <button class="nav-link {{ $i === 0 ? 'active' : '' }}"
                data-bs-toggle="tab"
                data-bs-target="#jstand-group-{{ $i }}"
                type="button">
                {{ $g['group_name'] }}
            </button>
        </li>
        @endforeach
    </ul>
    @endif

    <div class="tab-content">
        @foreach ($payload['groups'] as $i => $g)
        <div class="tab-pane fade {{ $i === 0 ? 'show active' : '' }}" id="jstand-group-{{ $i }}">
            @if (empty($g['breakdown']))
            <div class="public-empty">Aún no hay canchas en esta jornada.</div>
            @else
            @include('public.league._jornada-standings-body', [
            'breakdown' => $g['breakdown'],
            'complete' => $g['complete'],
            ])
            @endif
        </div>
        @endforeach
    </div>
</section>
@endsection