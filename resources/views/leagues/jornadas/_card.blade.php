@php
$editable = $jornada->isEditable();
$latest = $jornada->isLatest();
@endphp

<div class="col-md-6 col-lg-4 jornada-card-wrapper {{ $editable ? '' : 'is-frozen' }}"
    data-jornada-id="{{ $jornada->id }}">

    <div class="d-flex align-items-center gap-2 mb-2 flex-wrap">
        <a href="{{ route('leagues.jornadas.standings', [$league, $group, $jornada]) }}"
            class="btn btn-sm btn-outline-secondary">
            <i class="fa-solid fa-ranking-star"></i> Standings
        </a>

        @unless ($editable)
        <span class="badge text-bg-secondary d-inline-flex align-items-center gap-1"
            title="Bloqueada: existe una jornada posterior">
            <i class="fa-solid fa-lock"></i> Bloqueada
        </span>
        @endunless

        @if ($latest)
        <button type="button"
            class="btn btn-sm btn-outline-danger ms-auto delete-jornada-btn"
            data-url="{{ route('leagues.jornadas.destroy', [$league, $group, $jornada]) }}"
            data-number="{{ $jornada->number }}"
            title="Eliminar jornada">
            <i class="fa-solid fa-trash"></i>
        </button>
        @endif
    </div>

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