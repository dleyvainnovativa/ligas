@extends('layouts.admin')

@section('title', 'Managers — PlayWinners')

@section('content')
<div class="mb-4">
    <h1 class="mb-1">Managers</h1>
    <p class="text-secondary mb-0">{{ $managers->total() }} managers en total.</p>
</div>

{{-- Credentials surfaced once after creating a manager (admin shares them manually via WhatsApp) --}}
@if (session('new_manager_email') && session('new_manager_password'))
@php
$credEmail = session('new_manager_email');
$credPass = session('new_manager_password');
$waMessage = "Hola 👋 Tu acceso a PlayWinners:\n\n".
"🔗 " . route('login') . "\n".
"📧 Email: {$credEmail}\n".
"🔑 Contraseña: {$credPass}\n\n".
"Puedes cambiar tu contraseña una vez que inicies sesión.";
$waHref = 'https://wa.me/?text=' . rawurlencode($waMessage);
@endphp
<div class="alert alert-success d-flex flex-column gap-2 mb-4" role="alert">
    <div class="d-flex align-items-center gap-2">
        <i class="fa-solid fa-circle-check"></i>
        <strong>Manager creado — comparte las credenciales</strong>
    </div>
    <p class="mb-2 small">Estas credenciales solo se muestran una vez. Cópialas o envíalas por WhatsApp:</p>

    <div class="row g-2">
        <div class="col-md-6">
            <label class="form-label small mb-1">Email</label>
            <div class="d-flex gap-2 align-items-center">
                <input type="text" class="form-control form-control-sm font-mono" readonly
                    value="{{ $credEmail }}" id="cred-email-input">
                <button type="button" class="btn btn-sm btn-outline-primary" onclick="
                    const i = document.getElementById('cred-email-input');
                    i.select(); navigator.clipboard.writeText(i.value);
                    this.innerHTML = '<i class=\'fa-solid fa-check\'></i>';
                ">
                    <i class="fa-solid fa-copy"></i>
                </button>
            </div>
        </div>
        <div class="col-md-6">
            <label class="form-label small mb-1">Contraseña</label>
            <div class="d-flex gap-2 align-items-center">
                <input type="text" class="form-control form-control-sm font-mono" readonly
                    value="{{ $credPass }}" id="cred-pass-input">
                <button type="button" class="btn btn-sm btn-outline-primary" onclick="
                    const i = document.getElementById('cred-pass-input');
                    i.select(); navigator.clipboard.writeText(i.value);
                    this.innerHTML = '<i class=\'fa-solid fa-check\'></i>';
                ">
                    <i class="fa-solid fa-copy"></i>
                </button>
            </div>
        </div>
    </div>

    <div class="mt-2">
        <a href="{{ $waHref }}" target="_blank" rel="noopener" class="btn btn-sm btn-success">
            <i class="fa-brands fa-whatsapp me-1"></i> Enviar por WhatsApp
        </a>
    </div>
</div>
@endif

{{-- Add manager --}}
<div class="card-soft p-4 mb-4">
    <h6 class="mb-3"><i class="fa-solid fa-user-plus me-1"></i> Agregar manager</h6>
    <form method="POST" action="{{ route('admin.managers.store') }}">
        @csrf
        <div class="row g-3">
            <div class="col-md-4">
                <label class="form-label required">Email</label>
                <input type="email" name="email" class="form-control" value="{{ old('email') }}" required>
            </div>
            <div class="col-md-4">
                <label class="form-label">Nombre</label>
                <input type="text" name="name" class="form-control" value="{{ old('name') }}">
            </div>
            <div class="col-md-4">
                <label class="form-label required">Contraseña</label>
                <div class="d-flex gap-2 align-items-center">
                    <input type="text" name="password" id="manager-password" class="form-control font-mono"
                        value="{{ old('password') }}" minlength="6" required
                        placeholder="Mínimo 6 caracteres">
                    <button type="button" class="btn btn-outline-secondary" title="Generar" onclick="
                        const chars = 'ABCDEFGHJKLMNPQRSTUVWXYZabcdefghijkmnpqrstuvwxyz23456789';
                        let p = '';
                        for (let k = 0; k < 10; k++) p += chars[Math.floor(Math.random()*chars.length)];
                        document.getElementById('manager-password').value = p;
                    ">
                        <i class="fa-solid fa-dice"></i>
                    </button>
                </div>
            </div>
            <div class="col-md-2">
                <label class="form-label required">Plan</label>
                <select name="tier" class="form-select" required>
                    @foreach (['free', 'plus', 'pro'] as $tier)
                    <option value="{{ $tier }}" @selected(old('tier')===$tier)>{{ ucfirst($tier) }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label">Vigencia (tier_until)</label>
                <input type="date" name="tier_until" class="form-control" value="{{ old('tier_until') }}">
            </div>
        </div>
        <div class="mt-3">
            <button type="submit" class="btn btn-primary">
                <i class="fa-solid fa-plus me-1"></i> Crear manager
            </button>
            <small class="text-muted ms-2">Se crea en Firebase con la contraseña asignada. Compártela manualmente por WhatsApp.</small>
        </div>
    </form>
</div>

{{-- List --}}
<div class="card-soft p-0 overflow-hidden">
    <div class="table-responsive">
        <table class="table mb-0 align-middle">
            <thead>
                <tr>
                    <th>Email</th>
                    <th>Nombre</th>
                    <th>Plan</th>
                    <th>Vigencia</th>
                    <th>Rol</th>
                    <th>Registrado</th>
                    <th class="text-end">Acciones</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($managers as $m)
                <tr>
                    <td>{{ $m->email }}</td>
                    <td>{{ $m->name ?? '—' }}</td>
                    <td>
                        <span class="plan-badge plan-badge-{{ $m->tier }}">
                            <i class="fa-solid fa-{{ $m->tier === 'pro' ? 'crown' : ($m->tier === 'plus' ? 'star' : 'circle') }}"></i>
                            {{ ucfirst($m->tier) }}
                        </span>
                    </td>
                    <td class="font-mono small">{{ $m->tier_until?->format('Y-m-d') ?? '∞' }}</td>
                    <td>
                        @if ($m->role === 'admin')
                        <span class="badge bg-warning text-dark">admin</span>
                        @else
                        <span class="text-muted small">manager</span>
                        @endif
                    </td>
                    <td class="font-mono small text-muted">{{ $m->created_at?->format('Y-m-d') }}</td>
                    <td class="text-end">
                        <button type="button" class="btn btn-sm btn-outline-primary"
                            data-bs-toggle="modal" data-bs-target="#edit-manager-{{ $m->id }}">
                            <i class="fa-solid fa-pen"></i>
                        </button>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="7" class="text-center text-muted py-4">Aún no hay managers.</td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

<div class="mt-3">
    {{ $managers->links() }}
</div>

{{-- Edit-plan modals (kept outside the table for valid markup) --}}
@foreach ($managers as $m)
<div class="modal fade" id="edit-manager-{{ $m->id }}" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <form method="POST" action="{{ route('admin.managers.update', $m) }}" class="modal-content">
            @csrf
            @method('PUT')
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="fa-solid fa-user-gear me-1"></i> Editar plan
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
            </div>
            <div class="modal-body">
                <p class="small text-muted mb-3">
                    {{ $m->name ? $m->name . ' — ' : '' }}{{ $m->email }}
                </p>
                <div class="mb-3">
                    <label class="form-label required">Plan</label>
                    <select name="tier" class="form-select" required>
                        @foreach (['free', 'plus', 'pro'] as $tier)
                        <option value="{{ $tier }}" @selected($m->tier === $tier)>{{ ucfirst($tier) }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="mb-1">
                    <label class="form-label">Vigencia (tier_until)</label>
                    <input type="date" name="tier_until" class="form-control"
                        value="{{ $m->tier_until?->format('Y-m-d') }}">
                    <small class="text-muted">Déjalo vacío para vigencia ilimitada (∞).</small>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancelar</button>
                <button type="submit" class="btn btn-primary">
                    <i class="fa-solid fa-floppy-disk me-1"></i> Guardar
                </button>
            </div>
        </form>
    </div>
</div>
@endforeach
@endsection