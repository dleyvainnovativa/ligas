<!doctype html>
<html lang="es">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title') — Padel Leagues</title>
    @include('partials.theme-init')
    @vite(['resources/css/theme.css'])
</head>

<body class="error-body">
    <main class="error-shell">
        <div class="error-content">
            <div class="error-brand">
                <span class="brand-mark"><i class="fa-solid fa-table-tennis-paddle-ball"></i></span>
                <span class="brand-name">Padel Leagues</span>
            </div>
            <div class="error-code">@yield('code')</div>
            <h1>@yield('title')</h1>
            <p>@yield('message')</p>
            <div class="error-actions">
                <a href="{{ url('/') }}" class="btn btn-primary">
                    <i class="fa-solid fa-house me-1"></i> Volver al inicio
                </a>
                <button onclick="history.back()" class="btn btn-outline-secondary">
                    <i class="fa-solid fa-arrow-left me-1"></i> Atrás
                </button>
            </div>
        </div>
    </main>
</body>

</html>