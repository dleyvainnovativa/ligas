@php
$ogTitle = $ogTitle ?? 'PlayWinners — Organiza tu liga de pádel sin hojas de cálculo';
$ogDescription = $ogDescription ?? 'Plataforma para clubes de pádel en México: calendario, resultados, ascenso y descenso automático, y una página pública para tus jugadores.';
$ogImage = $ogImage ?? asset('img/og.jpg');
$ogUrl = $ogUrl ?? url()->current();
$ogType = $ogType ?? 'website';
@endphp

{{-- Open Graph --}}
<meta property="og:type" content="{{ $ogType }}">
<meta property="og:site_name" content="PlayWinners">
<meta property="og:title" content="{{ $ogTitle }}">
<meta property="og:description" content="{{ $ogDescription }}">
<meta property="og:url" content="{{ $ogUrl }}">
<meta property="og:image" content="{{ $ogImage }}">
<meta property="og:image:width" content="1200">
<meta property="og:image:height" content="630">
<meta property="og:image:alt" content="PlayWinners — plataforma para ligas de pádel">
<meta property="og:locale" content="es_MX">

{{-- Twitter Card --}}
<meta name="twitter:card" content="summary_large_image">
<meta name="twitter:title" content="{{ $ogTitle }}">
<meta name="twitter:description" content="{{ $ogDescription }}">
<meta name="twitter:image" content="{{ $ogImage }}">