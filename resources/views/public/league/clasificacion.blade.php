@extends('layouts.public')
@section('title', "Clasificación — {$league->name}")

@section('content')
<section class="public-section">
    <h2 class="mb-3">Clasificación</h2>

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
</section>
@endsection