@extends('layouts.public')
@section('title', "Calendario — {$league->name}")

@section('content')
<section class="public-section">
    <h2 class="mb-3">Calendario completo</h2>

    @if (empty($payload['jornadas']))
    <div class="public-empty-big">
        <i class="fa-solid fa-calendar"></i>
        <p>Aún no hay jornadas programadas.</p>
    </div>
    @else
    <div class="d-flex flex-column gap-2">
        @foreach ($payload['jornadas'] as $j)
        <a href="{{ route('public.jornada', [$league->slug, $j['number']]) }}"
            class="public-jornada-row status-{{ $j['status'] }}">
            <div class="public-jornada-number">
                <small>JORNADA</small>
                <strong>{{ $j['number'] }}</strong>
            </div>
            <div class="public-jornada-info">
                <div class="public-jornada-dates">{{ $j['date_display'] }}</div>
                <div class="public-jornada-progress-text">
                    {{ $j['done'] }} de {{ $j['total'] }} canchas terminadas
                </div>
                <div class="public-jornada-progress">
                    <span style="width: {{ $j['progress_pct'] }}%;"></span>
                </div>
            </div>
            <div class="public-jornada-status">
                @switch($j['status'])
                @case('terminada')
                <span class="badge text-bg-success">Terminada</span>
                @break
                @case('en_curso')
                <span class="badge text-bg-warning">En curso</span>
                @break
                @case('proxima')
                <span class="badge text-bg-secondary">Próxima</span>
                @break
                @default
                <span class="badge text-bg-light">Sin canchas</span>
                @endswitch
                <i class="fa-solid fa-chevron-right text-muted ms-2"></i>
            </div>
        </a>
        @endforeach
    </div>
    @endif
</section>
@endsection