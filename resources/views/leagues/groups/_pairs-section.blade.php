@php
/** @var \App\Models\League $league */
$playersInPairs = $league->pairs->flatMap(fn ($p) => [$p->player_a_id, $p->player_b_id])->all();
$availablePlayers = $league->players->reject(fn ($p) => in_array($p->id, $playersInPairs, true))->values();
@endphp

<div class="card-soft p-3 p-md-4 mb-3" id="pairs-app"
    data-league-id="{{ $league->id }}">
    <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
        <div>
            <h6 class="mb-1">Parejas</h6>
            <small class="text-secondary">
                <span id="pairs-count">{{ $league->pairs->count() }}</span> parejas ·
                <span id="available-count">{{ $availablePlayers->count() }}</span> jugadores sin pareja
            </small>
        </div>
        <button class="btn btn-primary btn-sm" id="new-pair-btn"
            @disabled($availablePlayers->count() < 2)>
                <i class="fa-solid fa-plus me-1"></i> Nueva pareja
        </button>
    </div>

    <div id="pairs-list" class="d-flex flex-column gap-2">
        @forelse ($league->pairs as $pair)
        @include('leagues.groups._pair-row', ['pair' => $pair])
        @empty
        <div class="empty-state py-4" id="pairs-empty">
            <div class="empty-state-icon">
                <i class="fa-solid fa-people-arrows"></i>
            </div>
            <p class="small mb-0">Aún no hay parejas. Crea una para empezar.</p>
        </div>
        @endforelse
    </div>
</div>

<div class="modal fade" id="new-pair-modal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Nueva pareja</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label class="form-label small">Jugador A</label>
                    <select class="form-select" id="pair-player-a">
                        @foreach ($availablePlayers as $p)
                        <option value="{{ $p->id }}">{{ $p->full_name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="mb-3">
                    <label class="form-label small">Jugador B</label>
                    <select class="form-select" id="pair-player-b">
                        @foreach ($availablePlayers as $p)
                        <option value="{{ $p->id }}">{{ $p->full_name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="mb-0">
                    <label class="form-label small">Nombre (opcional)</label>
                    <input type="text" class="form-control" id="pair-label" placeholder="ej. Los Rayos">
                </div>
            </div>
            <div class="modal-footer">
                <button class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancelar</button>
                <button class="btn btn-primary" id="create-pair-btn">
                    <i class="fa-solid fa-check me-1"></i> Crear pareja
                </button>
            </div>
        </div>
    </div>
</div>