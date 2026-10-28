@php
/** @var \App\Models\League $league */
$playersInPairs = $league->pairs->flatMap(fn ($p) => [$p->player_a_id, $p->player_b_id])->all();
$availablePlayers = $league->players->reject(fn ($p) => in_array($p->id, $playersInPairs, true))->values();
$statusOptions = [
    \App\Models\Player::STATUS_UNPAID  => 'No pagado',
    \App\Models\Player::STATUS_PARTIAL => 'Parcial',
    \App\Models\Player::STATUS_PAID    => 'Pagado',
];
@endphp

<div class="card-soft p-3 p-md-4" id="pairs-reg-app" data-league-id="{{ $league->id }}">
    <div class="d-flex justify-content-between align-items-start mb-3 flex-wrap gap-2">
        <div>
            <h6 class="mb-1">Parejas</h6>
            <small class="text-secondary">
                <span id="pairs-count">{{ $league->pairs->count() }}</span> parejas registradas
                @if ($availablePlayers->count() > 0)
                · <span id="loose-count">{{ $availablePlayers->count() }}</span> jugador(es) sin pareja
                @endif
            </small>
        </div>
        <div class="d-flex gap-2 flex-wrap">
            @if ($availablePlayers->count() >= 2)
            <button class="btn btn-outline-primary btn-sm" id="pair-existing-btn">
                <i class="fa-solid fa-link me-1"></i> Usar jugadores existentes
            </button>
            @endif
            <button class="btn btn-primary btn-sm" id="add-pair-full-btn">
                <i class="fa-solid fa-plus me-1"></i> Agregar pareja
            </button>
        </div>
    </div>

    <div id="pairs-list" class="d-flex flex-column gap-2">
        @forelse ($league->pairs as $pair)
        @include('leagues.groups._pair-row', ['pair' => $pair])
        @empty
        <div class="empty-state py-4" id="pairs-empty">
            <div class="empty-state-icon"><i class="fa-solid fa-people-arrows"></i></div>
            <h6>Aún no hay parejas</h6>
            <p class="small mb-0">Registra una pareja con los datos de ambos jugadores.</p>
        </div>
        @endforelse
    </div>
</div>

{{-- Combined registration: two players at once --}}
<div class="modal fade" id="new-pair-full-modal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <form id="new-pair-full-form">
                <div class="modal-header">
                    <h5 class="modal-title">Nueva pareja</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="row g-3">
                        @foreach (['a' => 'Jugador 1', 'b' => 'Jugador 2'] as $key => $title)
                        <div class="col-md-6">
                            <div class="border rounded p-3 h-100">
                                <h6 class="small text-secondary mb-3">
                                    <i class="fa-solid fa-user me-1"></i> {{ $title }}
                                </h6>
                                <div class="mb-2">
                                    <label class="form-label small">Nombre *</label>
                                    <input type="text" name="{{ $key }}_full_name" class="form-control form-control-sm" required maxlength="120">
                                </div>
                                <div class="mb-2">
                                    <label class="form-label small">Email</label>
                                    <input type="email" name="{{ $key }}_email" class="form-control form-control-sm" maxlength="160">
                                </div>
                                <div class="mb-2">
                                    <label class="form-label small">Teléfono</label>
                                    <input type="text" name="{{ $key }}_phone" class="form-control form-control-sm" maxlength="40">
                                </div>
                                <div class="row g-2">
                                    <div class="col-6">
                                        <label class="form-label small">Pagado</label>
                                        <input type="number" name="{{ $key }}_paid_amount" class="form-control form-control-sm"
                                            min="0" step="0.01" value="0">
                                    </div>
                                    <div class="col-6">
                                        <label class="form-label small">Estado</label>
                                        <select name="{{ $key }}_payment_status" class="form-select form-select-sm">
                                            @foreach ($statusOptions as $val => $txt)
                                            <option value="{{ $val }}">{{ $txt }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                </div>
                            </div>
                        </div>
                        @endforeach
                    </div>
                    <div class="mt-3">
                        <label class="form-label small">Nombre del equipo (opcional)</label>
                        <input type="text" name="label" class="form-control form-control-sm" maxlength="80" placeholder="ej. Los Rayos">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary" id="create-pair-full-btn">
                        <i class="fa-solid fa-check me-1"></i> Registrar pareja
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- Secondary: pair two already-registered players (substitutions, re-pairing) --}}
@if ($availablePlayers->count() >= 2)
<div class="modal fade" id="pair-existing-modal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Emparejar jugadores existentes</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label class="form-label small">Jugador 1</label>
                    <select class="form-select" id="pair-existing-a">
                        @foreach ($availablePlayers as $p)
                        <option value="{{ $p->id }}">{{ $p->full_name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="mb-3">
                    <label class="form-label small">Jugador 2</label>
                    <select class="form-select" id="pair-existing-b">
                        @foreach ($availablePlayers as $p)
                        <option value="{{ $p->id }}">{{ $p->full_name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="mb-0">
                    <label class="form-label small">Nombre del equipo (opcional)</label>
                    <input type="text" class="form-control" id="pair-existing-label" placeholder="ej. Los Rayos">
                </div>
            </div>
            <div class="modal-footer">
                <button class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancelar</button>
                <button class="btn btn-primary" id="create-pair-existing-btn">
                    <i class="fa-solid fa-check me-1"></i> Crear pareja
                </button>
            </div>
        </div>
    </div>
</div>
@endif
