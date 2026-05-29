<section class="public-section">
    <div class="public-section-header">
        <h2 class="public-section-title">
            <i class="fa-solid fa-flag-checkered"></i> Resultados recientes
        </h2>
        <span class="badge text-bg-secondary">{{ count($matches) }}</span>
    </div>

    @if (empty($matches))
    <div class="public-section-empty">Aún no hay resultados.</div>
    @else
    <div class="public-section-body p-0">
        @foreach ($matches as $m)
        @include('public.league._match-card', ['m' => $m, 'showScore' => true])
        @endforeach
    </div>
    @endif
</section>