<!doctype html>
<html lang="es" data-bs-theme="light">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>PlayWinners — Organiza tu liga de pádel sin hojas de cálculo</title>
    <meta name="description" content="Plataforma para clubes de pádel en México: calendario, resultados, ascenso y descenso automático, y una página pública para tus jugadores.">
    @include("partials.favicons")
    @include("partials.meta-og")

    @include('partials.theme-init')
    @vite(['resources/css/theme.css', 'resources/css/landing.css', 'resources/js/app.js'])
</head>

<body class="landing-body">

    {{-- ===== NAV ===== --}}
    <nav class="landing-nav fixed-top">
        <div class="landing-container landing-nav-inner">
            <a href="{{ route('landing') }}" class="landing-brand">
                <img src="{{ asset('img/logo.png') }}" alt="PlayWinners" height="34">
            </a>
            <div class="landing-nav-links">
                <a href="#como-funciona">Cómo funciona</a>
                <a href="#caracteristicas">Características</a>
                <a href="#quienes-somos">Quiénes Somos</a>
                <a href="#planes">Planes</a>
                <a href="#faq">Preguntas</a>
            </div>
            <div class="landing-nav-actions">
                <button type="button" data-theme-toggle class="landing-theme-toggle" aria-label="Cambiar tema">
                    <i class="fa-solid fa-sun theme-icon-light"></i>
                    <i class="fa-solid fa-moon theme-icon-dark"></i>
                </button>
                <a href="{{ route('login') }}" class="btn btn-sm btn-outline-secondary">Iniciar Sesión</a>
            </div>
        </div>
    </nav>

    <header class="hero" style="--hero-img: url('{{ asset('img/hero.jpg') }}');">
        <div class="hero-overlay"></div>

        <div class="landing-container hero-inner my-md-5">
            <div class="hero-content">
                <h1 class="hero-title text-light">
                    Llevamos<br>
                    tus ligas de pádel<br>
                    <span class="hero-title-accent">al siguiente nivel</span>
                </h1>

                <p class="hero-sub">
                    Organiza, administra y vive la mejor experiencia<br class="d-none d-lg-inline">
                    en ligas de pádel con <strong>PlayWinners.pro</strong>
                </p>

                <ul class="hero-pills">
                    @foreach ([
                    ['fa-calendar-days', 'Gestiona', 'tus ligas'],
                    ['fa-chart-simple', 'Ranking', 'en tiempo real'],
                    ['fa-mobile-screen-button', 'Resultados', 'desde móvil'],
                    ['fa-users', 'Para jugadores', 'y organizadores'],
                    ] as [$icon, $l1, $l2])
                    <li>
                        <i class="fa-solid {{ $icon }}"></i>
                        <span>{{ $l1 }}<br>{{ $l2 }}</span>
                    </li>
                    @endforeach
                </ul>

                <div class="hero-actions">
                    <a href="#contacto" class="hero-btn hero-btn-primary">
                        Prueba gratis <i class="fa-solid fa-arrow-right"></i>
                    </a>
                    <a href="#como-funciona" class="hero-btn hero-btn-ghost">
                        Ver demo <i class="fa-solid fa-circle-play"></i>
                    </a>
                </div>
            </div>

            <aside class="hero-stats">
                @foreach ([
                ['fa-users', '+10,000', 'Jugadores'],
                ['fa-trophy', '+500', 'Ligas creadas'],
                ['fa-arrow-trend-up', '100%', 'Experiencia premium'],
                ] as [$icon, $value, $label])
                <div class="hero-stat">
                    <i class="fa-solid {{ $icon }}"></i>
                    <div>
                        <strong>{{ $value }}</strong>
                        <small>{{ $label }}</small>
                    </div>
                </div>
                @endforeach
            </aside>
        </div>

        <div class="hero-sponsors">
            <div class="landing-container hero-sponsors-inner">
                <span class="hero-sponsors-label">Confían en<br>nosotros</span>
                <div class="hero-sponsors-logos">
                    {{-- Drop logo files in public/img/sponsors/ --}}
                    @foreach (['bullpadel', 'nox', 'siux', 'head', 'joma', 'varlion', 'adidas'] as $brand)
                    <img src="{{ asset("img/sponsors/{$brand}.png") }}" alt="{{ $brand }}">
                    @endforeach
                </div>
            </div>
        </div>
    </header>

    {{-- ===== CÓMO FUNCIONA ===== --}}
    <section id="como-funciona" class="landing-section">
        <div class="landing-container">
            <div class="landing-section-head">
                <h2>Cómo funciona</h2>
                <p>De la inscripción al ascenso, en cuatro pasos.</p>
            </div>
            <div class="row g-4">
                @foreach ([
                ['1', 'fa-trophy', 'Crea tu liga', 'Define jornadas, horarios, sedes y las reglas de puntuación.'],
                ['2', 'fa-users', 'Agrega jugadores', 'Captúralos uno por uno o impórtalos desde Excel o CSV.'],
                ['3', 'fa-calendar-days', 'Programa las canchas','Arrastra y suelta para asignar día, hora y pista.'],
                ['4', 'fa-arrow-trend-up','Todo se calcula solo','Registra marcadores y el sistema ordena la tabla y mueve jugadores entre canchas.'],
                ] as [$n, $icon, $title, $text])
                <div class="col-md-6 col-lg-3">
                    <div class="landing-step">
                        <span class="landing-step-num">{{ $n }}</span>
                        <i class="fa-solid {{ $icon }}"></i>
                        <h6>{{ $title }}</h6>
                        <p>{{ $text }}</p>
                    </div>
                </div>
                @endforeach
            </div>
        </div>
    </section>

    {{-- ===== CARACTERÍSTICAS ===== --}}
    <section id="caracteristicas" class="landing-section landing-section-alt">
        <div class="landing-container">
            <div class="landing-section-head">
                <h2>Todo lo que necesitas</h2>
                <p>Diseñado con clubes reales, no adaptado de otro deporte.</p>
            </div>
            <div class="row g-4">
                @foreach ([
                ['fa-mobile-screen', 'Página pública', 'Cada liga tiene su propia página: calendario, resultados, clasificación y perfil de cada jugador.'],
                ['fa-pen-to-square', 'Marcadores desde el celular', 'Los jugadores proponen el resultado y tú solo apruebas. Sin cuentas ni contraseñas para ellos.'],
                ['fa-arrows-up-down', 'Ascenso y descenso', 'Formato king of the court: el sistema calcula quién sube y quién baja de cancha cada jornada.'],
                ['fa-file-import', 'Importa desde Excel', 'Sube tu lista de jugadores en CSV o XLSX y valida los datos antes de importar.'],
                ['fa-location-dot', 'Sedes y pistas', 'Administra varias sedes, horarios y detecta choques de calendario automáticamente.'],
                ['fa-file-pdf', 'Resumen en PDF', 'Genera un resumen de temporada listo para imprimir y pegar en el club.'],
                ] as [$icon, $title, $text])
                <div class="col-md-6 col-lg-4">
                    <div class="landing-feature">
                        <span class="landing-feature-icon"><i class="fa-solid {{ $icon }}"></i></span>
                        <h6>{{ $title }}</h6>
                        <p>{{ $text }}</p>
                    </div>
                </div>
                @endforeach
            </div>
        </div>
    </section>
    {{-- ===== QUIÉNES SOMOS ===== --}}
    <section id="quienes-somos" class="landing-section landing-about">
        <div class="landing-container">
            <div class="landing-section-head">
                <h2>¿Quiénes somos?</h2>
                <p>Organizar una liga de pádel debería ser tan emocionante como jugarla.</p>
            </div>

            <div class="row g-4 align-items-start">
                {{-- Relato principal --}}
                <div class="col-lg-7">
                    <p class="landing-about-lead">
                        En <strong>PlayWinners.pro</strong> somos una plataforma tecnológica
                        especializada en la gestión y administración de ligas de pádel, diseñada
                        para simplificar el trabajo de clubes, academias, organizadores y jugadores
                        con herramientas inteligentes y automatización.
                    </p>
                    <p>
                        Reunimos todo en un solo lugar: creación automática de partidos, Sistema
                        Americano, tablas de posiciones en tiempo real, rankings individuales y
                        estadísticas avanzadas. Así transformamos la manera de organizar competencias
                        y ofrecemos una experiencia moderna, eficiente y profesional.
                    </p>
                    <p>
                        Trabajamos para impulsar el crecimiento del pádel: fortalecer la competencia,
                        promover el juego limpio y crear una comunidad deportiva más conectada.
                    </p>

                    {{-- Misión y visión --}}
                    <div class="row g-4 mt-1">
                        @foreach ([
                        ['fa-bullseye', 'Nuestra misión', 'Brindar una plataforma innovadora que facilite la organización de ligas de pádel, optimizando cada etapa de la competencia con tecnología, eficiencia y una excelente experiencia para todos los participantes.'],
                        ['fa-eye', 'Nuestra visión', 'Ser la plataforma líder en la administración de ligas de pádel en México y Latinoamérica, reconocida por su innovación, confiabilidad y compromiso con el desarrollo del deporte.'],
                        ] as [$icon, $title, $text])
                        <div class="col-sm-6">
                            <div class="landing-about-card">
                                <span class="landing-feature-icon"><i class="fa-solid {{ $icon }}"></i></span>
                                <h6>{{ $title }}</h6>
                                <p>{{ $text }}</p>
                            </div>
                        </div>
                        @endforeach
                    </div>
                </div>

                {{-- Valores --}}
                <div class="col-lg-5">
                    <div class="landing-about-values">
                        <h6 class="landing-about-values-title">Nuestros valores</h6>
                        <ul>
                            @foreach ([
                            ['fa-heart', 'Pasión por el pádel'],
                            ['fa-lightbulb', 'Innovación constante'],
                            ['fa-handshake', 'Compromiso con nuestros usuarios'],
                            ['fa-scale-balanced', 'Transparencia y juego limpio'],
                            ['fa-rocket', 'Excelencia en el servicio'],
                            ] as [$icon, $value])
                            <li>
                                <i class="fa-solid {{ $icon }}"></i>
                                <span>{{ $value }}</span>
                            </li>
                            @endforeach
                        </ul>

                        <div class="landing-about-tagline">
                            <strong>La plataforma inteligente</strong> para organizar, administrar
                            y hacer crecer tus ligas de pádel.
                            <span>Organiza. Compite. Gana.</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    {{-- ===== PLANES ===== --}}
    <section id="planes" class="landing-section">
        <div class="landing-container">
            <div class="landing-section-head">
                <h2>Planes</h2>
                <p>Empieza gratis. Cambia de plan cuando tu liga crezca.</p>
            </div>

            <div class="row g-4 justify-content-center">
                @foreach ($tiers as $key => $plan)
                @if ($key === 'free')
                <div class="col-md-6 col-lg-4">
                    <div class="landing-plan @if($key === 'free') is-featured @endif">
                        @if ($key === 'free')
                        <span class="landing-plan-ribbon">Más popular</span>
                        @endif

                        <h3>{{ $plan['label'] }}</h3>
                        <div class="landing-plan-price">{{ $plan['price_label'] }}</div>
                        <p class="landing-plan-tagline">{{ $plan['tagline'] }}</p>

                        <ul class="landing-plan-limits">
                            <li>
                                <strong>{{ $plan['limits']['active_leagues'] ?? '∞' }}</strong>
                                {{ ($plan['limits']['active_leagues'] ?? null) === 1 ? 'liga activa' : 'ligas activas' }}
                            </li>
                            <li>
                                <strong>{{ $plan['limits']['players_per_league'] ?? '∞' }}</strong> jugadores por liga
                            </li>
                            <li>
                                <strong>{{ $plan['limits']['jornadas_per_league'] ?? '∞' }}</strong> jornadas por liga
                            </li>
                        </ul>

                        <ul class="landing-plan-features">
                            @foreach ($plan['features'] as $feature)
                            <li><i class="fa-solid fa-check"></i> {{ $feature }}</li>
                            @endforeach
                        </ul>

                        <a href="#contacto" class="btn {{ $key === 'plus' ? 'btn-primary' : 'btn-outline-secondary' }} w-100 mt-auto">
                            Solicitar {{ $plan['label'] }}
                        </a>
                    </div>
                </div>
                @endif

                @endforeach
            </div>

            <p class="text-center text-secondary small mt-4">
                Precios en pesos mexicanos. Te ayudamos a migrar tu liga actual sin costo.
            </p>
        </div>
    </section>

    {{-- ===== FAQ ===== --}}
    <section id="faq" class="landing-section landing-section-alt">
        <div class="landing-container landing-container-narrow">
            <div class="landing-section-head">
                <h2>Preguntas frecuentes</h2>
            </div>

            <div class="accordion landing-faq" id="faq-accordion">
                @foreach ([
                ['¿Mis jugadores necesitan crear una cuenta?', 'No. La página de la liga es pública: entran con el enlace y consultan calendario, resultados y clasificación. Solo tú necesitas cuenta.'],
                ['¿Tengo que instalar algo?', 'No. Todo funciona desde el navegador, en computadora y celular.'],
                ['¿Puedo cambiar de plan después?', 'Sí. Puedes subir o bajar de plan cuando quieras; tus ligas y resultados se conservan.'],
                ['¿Qué pasa cuando termina la temporada?', 'La liga se marca como completada y su página queda disponible como histórico. Puedes crear una nueva temporada desde cero.'],
                ['¿Funciona para formato de parejas?', 'Sí. Puedes elegir formato individual (king of the court con ascenso y descenso) o parejas con sistema de puntos clásico.'],
                ] as $i => [$q, $a])
                <div class="accordion-item">
                    <h2 class="accordion-header">
                        <button class="accordion-button {{ $i === 0 ? '' : 'collapsed' }}" type="button"
                            data-bs-toggle="collapse" data-bs-target="#faq-{{ $i }}">
                            {{ $q }}
                        </button>
                    </h2>
                    <div id="faq-{{ $i }}" class="accordion-collapse collapse {{ $i === 0 ? 'show' : '' }}"
                        data-bs-parent="#faq-accordion">
                        <div class="accordion-body">{{ $a }}</div>
                    </div>
                </div>
                @endforeach
            </div>
        </div>
    </section>

    {{-- ===== CTA FINAL ===== --}}
    <section id="contacto" class="landing-cta-final">
        <div class="landing-container text-center">
            <h2>¿Listo para organizar tu próxima liga?</h2>
            <p>Escríbenos y te ayudamos a montar tu primera temporada.</p>
            <div class="landing-cta-row justify-content-center">
                <a href="https://wa.me/522297450000" target="_blank" rel="noopener" class="btn btn-lg btn-whatsapp">
                    <i class="fa-brands fa-whatsapp me-2"></i> Escríbenos por WhatsApp
                </a>
                <a href="mailto:hola@PlayWinners.com" class="btn btn-lg btn-outline-primary">
                    <i class="fa-solid fa-envelope me-2"></i> Enviar correo
                </a>
            </div>
        </div>
    </section>

    {{-- ===== FOOTER ===== --}}
    <footer class="landing-footer">
        <div class="landing-container">
            <div class="d-flex flex-wrap justify-content-between align-items-center gap-3">
                <img src="{{ asset('img/logo.png') }}" alt="PlayWinners" height="28">
                @include('public.league._social-links', ['variant' => 'footer'])
            </div>
            <hr>
            <small class="text-secondary">© {{ date('Y') }} PlayWinners. Todos los derechos reservados.</small>
        </div>
    </footer>

</body>

</html>