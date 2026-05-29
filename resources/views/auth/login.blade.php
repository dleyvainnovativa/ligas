<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Iniciar sesión — Padel Leagues</title>
    @include('partials.theme-init')
    @vite(['resources/css/theme.css', 'resources/js/app.js'])
</head>
<body>
<div class="auth-page">
    {{-- Left: form --}}
    <section class="auth-form-side">
        <button class="theme-toggle auth-toggle" data-theme-toggle aria-label="Alternar tema">
            <i class="fa-solid fa-sun sun"></i>
            <i class="fa-solid fa-moon moon"></i>
        </button>

        <div class="auth-form-card">
            <div class="auth-brand">
                <span class="brand-mark"><i class="fa-solid fa-table-tennis-paddle-ball"></i></span>
                <span class="brand-name">Padel Leagues</span>
            </div>

            <h1>Bienvenido de vuelta</h1>
            <p class="auth-subtitle">Accede para administrar tus ligas de pádel.</p>

            <form id="login-form" novalidate>
                <div class="mb-3">
                    <label class="form-label required">Email</label>
                    <input type="email" name="email" class="form-control" autocomplete="email" required autofocus>
                </div>

                <div class="mb-4">
                    <label class="form-label required d-flex justify-content-between align-items-center">
                        <span>Contraseña</span>
                        <a href="#" class="text-muted small text-decoration-none" tabindex="-1">¿Olvidaste tu contraseña?</a>
                    </label>
                    <input type="password" name="password" class="form-control" autocomplete="current-password" required>
                </div>

                <button id="login-btn" type="submit" class="btn btn-primary w-100 btn-lg">
                    <i class="fa-solid fa-arrow-right-to-bracket me-2"></i>
                    Entrar
                </button>
            </form>

            <p class="auth-meta">
                Acceso para managers. Los jugadores ven la liga en su URL pública.
            </p>
        </div>
    </section>

    {{-- Right: marketing/visual side --}}
    <aside class="auth-visual-side">
        <div class="auth-visual-content">
            <p class="text-muted small mb-4" style="font-weight:600; letter-spacing:0.08em; text-transform:uppercase;">
                Plataforma para clubes
            </p>
            <h2 class="auth-visual-tagline">
                La forma más simple de organizar tu liga.
            </h2>
            <p class="auth-visual-desc">
                Drag-and-drop para formar canchas, calendario interactivo, standings en vivo y página pública lista para compartir.
            </p>
            <ul class="auth-visual-bullets">
                <li><i class="fa-solid fa-check"></i> Modos individual y por parejas</li>
                <li><i class="fa-solid fa-check"></i> Divisiones con promoción y descenso</li>
                <li><i class="fa-solid fa-check"></i> Standings automáticos en tiempo real</li>
                <li><i class="fa-solid fa-check"></i> Página pública para tus jugadores</li>
            </ul>
        </div>

        <small class="text-muted" style="position:relative; z-index:1;">
            © {{ date('Y') }} Padel Leagues
        </small>
    </aside>
</div>

<script>
    document.getElementById('login-form').addEventListener('submit', async (e) => {
        e.preventDefault();
        const form = e.target;
        const btn  = document.getElementById('login-btn');
        window.app.loading.on(btn);
        try {
            const { email, password } = window.app.serializeForm(form);
            const cred = await window.firebase.signInWithEmailAndPassword(window.firebase.auth, email, password);
            const idToken = await cred.user.getIdToken();
            const data = await window.app.api.post('{{ route('auth.session') }}', { id_token: idToken });
            window.location.href = data.redirect;
        } catch (err) {
            const msg = friendlyAuthError(err);
            window.app.toast.error(msg);
        } finally {
            window.app.loading.off(btn);
        }
    });

    function friendlyAuthError(err) {
        const code = err.code || '';
        if (code.includes('user-not-found') || code.includes('invalid-credential') || code.includes('wrong-password')) {
            return 'Email o contraseña incorrectos.';
        }
        if (code.includes('too-many-requests')) {
            return 'Demasiados intentos. Intenta de nuevo en unos minutos.';
        }
        if (code.includes('network')) {
            return 'Problema de conexión. Verifica tu internet.';
        }
        return err.message || 'No se pudo iniciar sesión.';
    }
</script>
</body>
</html>