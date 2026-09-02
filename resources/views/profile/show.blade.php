@extends('layouts.app')

@section('title', 'Perfil — PlayWinners')

@section('content')
<div class="mb-4">
    <h1 class="mb-1">Perfil</h1>
    <p class="text-secondary mb-0">Administra tu cuenta y contraseña.</p>
</div>

<div class="row g-4">
    {{-- Account info --}}
    <div class="col-lg-5">
        <div class="card-soft p-4 h-100">
            <h6 class="mb-3"><i class="fa-regular fa-circle-user me-1"></i> Tu cuenta</h6>

            <div class="mb-3">
                <label class="form-label small text-muted mb-1">Nombre</label>
                <div class="fw-semibold">{{ $manager->name ?? '—' }}</div>
            </div>
            <div class="mb-3">
                <label class="form-label small text-muted mb-1">Email</label>
                <div class="fw-semibold font-mono">{{ $manager->email }}</div>
            </div>
            <div class="mb-3">
                <label class="form-label small text-muted mb-1">Plan</label>
                <div>
                    <span class="plan-badge plan-badge-{{ $manager->tier }}">
                        <i class="fa-solid fa-{{ $manager->tier === 'pro' ? 'crown' : ($manager->tier === 'plus' ? 'star' : 'circle') }}"></i>
                        {{ ucfirst($manager->tier) }}
                    </span>
                    @if ($manager->tier_until)
                    <small class="text-muted ms-2">hasta {{ $manager->tier_until->format('Y-m-d') }}</small>
                    @endif
                </div>
            </div>
        </div>
    </div>

    {{-- Change password --}}
    <div class="col-lg-7">
        <div class="card-soft p-4 h-100">
            <h6 class="mb-3"><i class="fa-solid fa-key me-1"></i> Cambiar contraseña</h6>

            <form id="change-password-form" novalidate>
                <div class="mb-3">
                    <label class="form-label required">Contraseña actual</label>
                    <input type="password" name="current_password" class="form-control"
                        autocomplete="current-password" required>
                </div>
                <div class="mb-3">
                    <label class="form-label required">Nueva contraseña</label>
                    <input type="password" name="new_password" class="form-control"
                        autocomplete="new-password" minlength="6" required
                        placeholder="Mínimo 6 caracteres">
                </div>
                <div class="mb-4">
                    <label class="form-label required">Confirmar nueva contraseña</label>
                    <input type="password" name="new_password_confirm" class="form-control"
                        autocomplete="new-password" minlength="6" required>
                </div>

                <button id="change-password-btn" type="submit" class="btn btn-primary">
                    <i class="fa-solid fa-floppy-disk me-1"></i> Actualizar contraseña
                </button>
            </form>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    document.getElementById('change-password-form').addEventListener('submit', async (e) => {
        e.preventDefault();
        const form = e.target;
        const btn = document.getElementById('change-password-btn');
        const {
            current_password,
            new_password,
            new_password_confirm
        } = window.app.serializeForm(form);

        if (!current_password || !new_password) {
            window.app.toast.error('Completa todos los campos.');
            return;
        }
        if (new_password.length < 6) {
            window.app.toast.error('La nueva contraseña debe tener al menos 6 caracteres.');
            return;
        }
        if (new_password !== new_password_confirm) {
            window.app.toast.error('Las contraseñas no coinciden.');
            return;
        }

        const fb = window.firebase;
        const user = fb.auth.currentUser;
        if (!user) {
            // Firebase client session expired even though the Laravel session is alive.
            window.app.toast.error('Tu sesión expiró. Vuelve a iniciar sesión para cambiar la contraseña.');
            return;
        }

        window.app.loading.on(btn);
        try {
            // Firebase requires a recent login to change the password, so re-authenticate first.
            const cred = fb.EmailAuthProvider.credential(user.email, current_password);
            await fb.reauthenticateWithCredential(user, cred);
            await fb.updatePassword(user, new_password);

            window.app.toast.success('Contraseña actualizada correctamente.');
            form.reset();
        } catch (err) {
            const code = err.code || err.message || '';
            if (code.includes('wrong-password') || code.includes('invalid-credential')) {
                window.app.toast.error('La contraseña actual es incorrecta.');
            } else if (code.includes('weak-password')) {
                window.app.toast.error('La nueva contraseña es demasiado débil.');
            } else if (code.includes('too-many-requests')) {
                window.app.toast.error('Demasiados intentos. Intenta de nuevo en unos minutos.');
            } else if (code.includes('requires-recent-login')) {
                window.app.toast.error('Por seguridad, vuelve a iniciar sesión e intenta de nuevo.');
            } else {
                window.app.toast.error('No se pudo actualizar la contraseña.');
            }
        } finally {
            window.app.loading.off(btn);
        }
    });
</script>
@endpush