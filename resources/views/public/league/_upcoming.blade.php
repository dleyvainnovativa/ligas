<section class="public-section" id="proximos">
    <div class="public-section-header">
        <h2 class="public-section-title">
            <i class="fa-regular fa-calendar"></i> Próximos partidos
        </h2>
        <span class="badge text-bg-secondary">{{ count($matches) }}</span>
    </div>

    @if (empty($matches))
    <div class="public-section-empty">No hay partidos programados próximamente.</div>
    @else
    <div class="public-section-body p-0">
        @foreach ($matches as $m)
        @include('public.league._match-card', ['m' => $m, 'showScore' => false])
        @endforeach
    </div>
    @endif
</section>