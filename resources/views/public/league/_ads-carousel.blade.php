<section class="public-ads">
    <div id="public-ads-carousel" class="carousel slide" data-bs-ride="carousel" data-bs-interval="5000">
        @if ($ads->count() > 1)
        <div class="carousel-indicators">
            @foreach ($ads as $i => $ad)
            <button type="button"
                data-bs-target="#public-ads-carousel"
                data-bs-slide-to="{{ $i }}"
                @if ($i===0) class="active" aria-current="true" @endif
                aria-label="Anuncio {{ $i + 1 }}">
            </button>
            @endforeach
        </div>
        @endif

        <div class="carousel-inner">
            @foreach ($ads as $i => $ad)
            <div class="carousel-item @if ($i === 0) active @endif">
                @if ($ad->link_url)
                <a href="{{ $ad->link_url }}" target="_blank" rel="noopener noreferrer">
                    <img src="{{ $ad->image_url }}" alt="{{ $ad->title ?? 'Anuncio' }}" class="d-block w-100 public-ad-image">
                </a>
                @else
                <img src="{{ $ad->image_url }}" alt="{{ $ad->title ?? 'Anuncio' }}" class="d-block w-100 public-ad-image">
                @endif
            </div>
            @endforeach
        </div>

        @if ($ads->count() > 1)
        <button class="carousel-control-prev" type="button" data-bs-target="#public-ads-carousel" data-bs-slide="prev">
            <span class="carousel-control-prev-icon" aria-hidden="true"></span>
            <span class="visually-hidden">Anterior</span>
        </button>
        <button class="carousel-control-next" type="button" data-bs-target="#public-ads-carousel" data-bs-slide="next">
            <span class="carousel-control-next-icon" aria-hidden="true"></span>
            <span class="visually-hidden">Siguiente</span>
        </button>
        @endif
    </div>
</section>