@extends('layouts.admin')

@section('title', 'Managers — PlayWinners')

@section('content')
<div class="mb-4">
    <h1 class="mb-1">Managers</h1>
    <p class="text-secondary mb-0">{{ $managers->total() }} managers en total.</p>
</div>

{{-- Reset link surfaced after creating a manager (no SMTP yet) --}}
@if (session('reset_link'))
<div class="alert alert-info d-flex flex-column gap-2 mb-4" role="alert">
    <div class="d-flex align-items-center gap-2">
        <i class="fa-solid fa-link"></i>
        <strong>Enlace para establecer contraseña</strong>
    </div>
    <p class="mb-2 small">Cópialo y compártelo con el nuevo manager para que defina su contraseña:</p>
    <div class="d-flex gap-2 align-items-center">
        <input type="text" class="form-control form-control-sm font-mono" readonly
            value="{{ session('reset_link') }}" id="reset-link-input">
        <button type="button" class="btn btn-sm btn-outline-primary" onclick="
            const i = document.getElementById('reset-link-input');
            i.select(); navigator.clipboard.writeText(i.value);
            this.innerHTML = '<i class=\'fa-solid fa-check\'></i>';
        ">
            <i class="fa-solid fa-copy"></i>
        </button>
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
            <div class="col-md-2">
                <label class="form-label required">Plan</label>
                <select name="tier" class="form-select" required>
                    @foreach (['free', 'plus', 'pro'] as $tier)
                    <option value="{{ $tier }}" @selected(old('tier') === $tier)>{{ ucfirst($tier) }}</option>
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
            <small class="text-muted ms-2">Se crea en Firebase y se genera un enlace para su contraseña.</small>
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
                </tr>
                @empty
                <tr><td colspan="6" class="text-center text-muted py-4">Aún no hay managers.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

<div class="mt-3">
    {{ $managers->links() }}
</div>
@endsection
