<!doctype html>
<html lang="es">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Crear cuenta — PlayWinners</title>
    @include("partials.favicons")
    @include("partials.meta-og")
    @include('partials.theme-init')
    @vite(['resources/css/theme.css', 'resources/js/app.js'])
</head>

<body>
    <div class="auth-page">
        <section class="auth-form-side">
            <button class="theme-toggle auth-toggle" data-theme-toggle aria-label="Alternar tema">
                <i class="fa-solid fa-sun sun"></i>
                <i class="fa-solid fa-moon moon"></i>
            </button>

            <div class="auth-form-card">
                <div class="auth-brand">
                    <img src="{{ asset('img/logo.png') }}" alt="Logo" class="public-stat-logo-img">
                </div>

                <h1>Crea tu cuenta</h1>
                <p class="auth-subtitle">Empieza gratis y organiza tu primera liga de pádel.</p>

                {{-- Google --}}
                <button id="google-btn" type="button" class="btn btn-outline-secondary w-100 btn-lg mb-3">
                    <i class="fa-brands fa-google me-2"></i>
                    Continuar con Google
                </button>

                <div class="auth-divider"><span>o con tu correo</span></div>

                <form id="register-form" novalidate>
                    <div class="mb-3">
                        <label class="form-label">Nombre</label>
                        <input type="text" name="name" class="form-control" autocomplete="name">
                    </div>
                    <div class="mb-3">
                        <label class="form-label required">Email</label>
                        <input type="email" name="email" class="form-control" autocomplete="email" required>
                    </div>
                    <div class="mb-4">
                        <label class="form-label required">Contraseña</label>
                        <input type="password" name="password" class="form-control" autocomplete="new-password"
                            minlength="6" required>
                        <small class="text-muted">Mínimo 6 caracteres.</small>
                    </div>

                    <button id="register-btn" type="submit" class="btn btn-primary w-100 btn-lg">
                        <i class="fa-solid fa-user-plus me-2"></i>
                        Crear cuenta gratis
                    </button>
                </form>

                <p class="auth-meta">
                    ¿Ya tienes cuenta? <a href="{{ route('login') }}">Inicia sesión</a>.
                </p>
            </div>
        </section>

        <aside class="auth-visual-side">
            <div class="auth-visual-content">
                <p class="text-muted small mb-4" style="font-weight:600; letter-spacing:0.08em; text-transform:uppercase;">
                    Plataforma para clubes
                </p>
                <h2 class="auth-visual-tagline">
                    Organiza tu liga en minutos, no en hojas de cálculo.
                </h2>
                <p class="auth-visual-desc">
                    Crea canchas, arma el calendario y comparte una página pública lista para tus jugadores.
                </p>
                <ul class="auth-visual-bullets">
                    <li><i class="fa-solid fa-check"></i> Plan gratis para empezar</li>
                    <li><i class="fa-solid fa-check"></i> Modos individual y por parejas</li>
                    <li><i class="fa-solid fa-check"></i> Standings automáticos en tiempo real</li>
                    <li><i class="fa-solid fa-check"></i> Página pública para tus jugadores</li>
                </ul>
            </div>

            <small class="text-muted" style="position:relative; z-index:1;">
                © {{ date('Y') }} PlayWinners
            </small>
        </aside>
    </div>

    <script>
        const registerForm = document.getElementById('register-form');
        const googleBtn = document.getElementById('google-btn');

        // Email / password sign-up
        registerForm.addEventListener('submit', async (e) => {
            e.preventDefault();
            const btn = document.getElementById('register-btn');
            window.app.loading.on(btn);
            try {
                const { email, password } = window.app.serializeForm(registerForm);
                const cred = await window.firebase.createUserWithEmailAndPassword(
                    window.firebase.auth, email, password
                );
                await finishRegistration(cred.user);
            } catch (err) {
                window.app.toast.error(friendlyAuthError(err));
            } finally {
                window.app.loading.off(btn);
            }
        });

        // Google sign-up
        googleBtn.addEventListener('click', async () => {
            window.app.loading.on(googleBtn);
            try {
                const provider = new window.firebase.GoogleAuthProvider();
                const cred = await window.firebase.signInWithPopup(window.firebase.auth, provider);
                await finishRegistration(cred.user);
            } catch (err) {
                window.app.toast.error(friendlyAuthError(err));
            } finally {
                window.app.loading.off(googleBtn);
            }
        });

        async function finishRegistration(user) {
            let idToken = await user.getIdToken();
            let data = await postRegister(idToken);

            if (data?.code === 'TOKEN_STALE') {
                idToken = await user.getIdToken(true);
                data = await postRegister(idToken);
            }

            if (data?.redirect) {
                window.location.href = data.redirect;
            } else {
                throw new Error(data?.error || 'No se pudo crear la cuenta.');
            }
        }

        async function postRegister(idToken) {
            const res = await fetch('{{ route('auth.register') }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                },
                body: JSON.stringify({ id_token: idToken }),
                credentials: 'same-origin',
            });
            const data = await res.json().catch(() => ({}));
            return res.ok ? data : { ...data, error: data?.error || res.statusText };
        }

        function friendlyAuthError(err) {
            const code = err.code || err.message || '';
            if (code.includes('email-already-in-use')) {
                return 'Ese email ya está registrado. Inicia sesión.';
            }
            if (code.includes('weak-password')) {
                return 'La contraseña es muy débil (mínimo 6 caracteres).';
            }
            if (code.includes('invalid-email')) {
                return 'El email no es válido.';
            }
            if (code.includes('popup-closed-by-user')) {
                return 'Cerraste la ventana de Google antes de terminar.';
            }
            if (code.includes('network')) {
                return 'Problema de conexión. Verifica tu internet.';
            }
            return err.message || 'No se pudo crear la cuenta.';
        }
    </script>
</body>

</html>
