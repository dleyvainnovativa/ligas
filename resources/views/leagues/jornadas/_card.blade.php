<div class="col-md-6 col-lg-4 jornada-card-wrapper" data-jornada-id="{{ $jornada->id }}">
    <a href="{{ route('leagues.jornadas.standings', [$league, $group, $jornada]) }}"
        class="btn btn-sm btn-outline-secondary mb-2">
        <i class="fa-solid fa-ranking-star"></i> Standings
    </a>
    <a href="{{ route('leagues.jornadas.show', [$league, $group, $jornada]) }}"
        class="card-soft p-3 d-block text-decoration-none jornada-card">
        <div class="d-flex justify-content-between align-items-start mb-2">
            <div>
                <small class="text-secondary">Jornada</small>
                <h4 class="mb-0">#{{ $jornada->number }}</h4>
            </div>
            <span class="badge rounded-pill
                @class([
                    'text-bg-secondary' => $jornada->status === 'draft',
                    'text-bg-info'      => $jornada->status === 'scheduled',
                    'text-bg-success'   => $jornada->status === 'completed',
                ])">
                @switch($jornada->status)
                @case('draft') Borrador @break
                @case('scheduled') Programada @break
                @case('completed') Completada @break
                @endswitch
            </span>
        </div>
        <div class="d-flex justify-content-between text-secondary small">
            <span><i class="fa-solid fa-table-cells me-1"></i> {{ $jornada->canchas()->count() }} canchas</span>
            @if ($jornada->window_start)
            <span><i class="fa-regular fa-calendar me-1"></i> {{ $jornada->window_start->format('d/m') }}</span>
            @endif

        </div>

    </a>
</div>