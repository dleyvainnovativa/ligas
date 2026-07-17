@php
/** @var \App\Models\League $league */
$isEdit = $league->exists;
$action = $isEdit ? route('leagues.update', $league) : route('leagues.store');
$method = $isEdit ? 'PUT' : 'POST';

$oldDays = old('days_of_week', $league->days_of_week ?? []);
$oldSlots = old('time_slots', $league->time_slots ?? []);

$dayLabels = [
'mon' => 'Lun', 'tue' => 'Mar', 'wed' => 'Mié', 'thu' => 'Jue',
'fri' => 'Vie', 'sat' => 'Sáb', 'sun' => 'Dom',
];
$isEditing = isset($league) && $league->exists;
@endphp

<form action="{{ $action }}" method="POST" enctype="multipart/form-data" id="league-form">
    @csrf
    @method($method)

    <ul class="nav nav-tabs mb-4" role="tablist">
        @if ($isEditing)
        <li class="nav-item">
            <button class="nav-link active" data-bs-toggle="tab" data-bs-target="#tab-venues" type="button">
                Sedes y pistas
            </button>
        </li>
        <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#tab-basics" type="button">Básicos</button></li>
        @else
        <li class="nav-item"><button class="nav-link active" data-bs-toggle="tab" data-bs-target="#tab-basics" type="button">Básicos</button></li>

        @endif
        <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#tab-schedule" type="button">Calendario</button></li>
        <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#tab-rules" type="button">Reglas</button></li>
        <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#tab-branding" type="button">Branding</button></li>
    </ul>

    <div class="tab-content">

        @if ($isEditing)
        <div class="tab-pane fade show active" id="tab-venues" role="tabpanel">
            @include('leagues.partials._sedes', ['league' => $league->load('sedes.pistas')])
        </div>
        @endif
        {{-- TAB: Básicos --}}
        <div class="tab-pane fade {{ $isEditing == true ? '':'show active'}}" id="tab-basics">
            <div class="card-soft p-4 mb-3">
                <div class="row g-3">
                    <div class="col-md-8">
                        <label class="form-label small">Nombre de la liga</label>
                        <input type="text" name="name" class="form-control @error('name') is-invalid @enderror"
                            value="{{ old('name', $league->name) }}" required>
                        @error('name') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                    <div class="col-md-4">
                        <label class="form-label small">Formato</label>
                        <select name="format" class="form-select @error('format') is-invalid @enderror">
                            <option value="individual" @selected(old('format', $league->format) === 'individual')>Individual</option>
                            <option value="pairs" @selected(old('format', $league->format) === 'pairs')>Parejas</option>
                        </select>
                        @error('format') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>

                    <div class="col-md-8">
                        <label class="form-label small">Slug público <span class="text-secondary">(URL)</span></label>
                        <div class="input-group">
                            <span class="input-group-text small text-secondary">{{ url('/') }}/</span>
                            <input type="text" name="slug" class="form-control @error('slug') is-invalid @enderror"
                                value="{{ old('slug', $league->slug) }}"
                                placeholder="ej. liga-otono-2026">
                            @error('slug') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                        <small class="text-secondary">Se genera automáticamente si lo dejas vacío.</small>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label small">Estado</label>
                        <select name="status" class="form-select">
                            <option value="active" @selected(old('status', $league->status) === 'active')>Activa</option>
                            <option value="draft" @selected(old('status', $league->status) === 'draft')>Borrador</option>
                            <option value="completed" @selected(old('status', $league->status) === 'completed')>Completada</option>
                            <option value="archived" @selected(old('status', $league->status) === 'archived')>Archivada</option>
                        </select>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label small">Número de jornadas</label>
                        <input type="number" name="num_jornadas" min="1" max="52"
                            class="form-control @error('num_jornadas') is-invalid @enderror"
                            value="{{ old('num_jornadas', $league->num_jornadas) }}">
                        @error('num_jornadas') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                    <div class="col-md-6">
                        <label class="form-label small">Costo por jugador</label>
                        <div class="input-group">
                            <span class="input-group-text">$</span>
                            <input type="number" name="cost" min="0" step="0.01"
                                class="form-control @error('cost') is-invalid @enderror"
                                value="{{ old('cost', $league->cost) }}">
                            @error('cost') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                    </div>

                    <div class="col-12">
                        <label class="form-label">Grupo de WhatsApp (opcional)</label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="fa-brands fa-whatsapp text-success"></i></span>
                            <input type="url" name="whatsapp_url" class="form-control"
                                value="{{ old('whatsapp_url', $league->whatsapp_url ?? '') }}"
                                placeholder="https://chat.whatsapp.com/...">
                        </div>
                        <small class="text-muted">Se mostrará como botón en la página pública.</small>
                        @error('whatsapp_url')<small class="text-danger">{{ $message }}</small>@enderror
                    </div>

                    <div class="col-12">
                        <label class="form-label small">Descripción</label>
                        <textarea name="description" rows="3"
                            class="form-control @error('description') is-invalid @enderror"
                            placeholder="Información que verán los jugadores en la página pública.">{{ old('description', $league->description) }}</textarea>
                        @error('description') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                </div>
            </div>
        </div>

        {{-- TAB: Calendario --}}
        <div class="tab-pane fade" id="tab-schedule">
            <div class="card-soft p-4 mb-3">
                <h6 class="mb-3">Días de la semana</h6>
                <div class="d-flex flex-wrap gap-2 mb-2">
                    @foreach ($dayLabels as $code => $label)
                    <label class="day-chip">
                        <input type="checkbox" name="days_of_week[]" value="{{ $code }}"
                            @checked(in_array($code, $oldDays))>
                        <span>{{ $label }}</span>
                    </label>
                    @endforeach
                </div>
                @error('days_of_week') <small class="text-danger d-block">{{ $message }}</small> @enderror
            </div>

            <div class="card-soft p-4">
                <h6 class="mb-1">Horarios disponibles</h6>
                <p class="text-secondary small mb-3">Agrega los horarios en formato 24h (ej. 18:00).</p>

                <div id="time-slots-input" data-name="time_slots" data-initial='@json($oldSlots)'></div>
                @error('time_slots') <small class="text-danger d-block mt-2">{{ $message }}</small> @enderror
                @error('time_slots.*') <small class="text-danger d-block mt-2">{{ $message }}</small> @enderror
            </div>
        </div>

        {{-- TAB: Reglas --}}
        <div class="tab-pane fade" id="tab-rules">
            <div class="card-soft p-4 mb-3">
                <h6 class="mb-3">Penalizaciones</h6>
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label small">Puntos por suplente</label>
                        <input type="number" name="penalty_suplente" min="0" max="100"
                            class="form-control" value="{{ old('penalty_suplente', $league->penalty_suplente) }}">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label small">Puntos por no presentarse</label>
                        <input type="number" name="penalty_no_show" min="0" max="100"
                            class="form-control" value="{{ old('penalty_no_show', $league->penalty_no_show) }}">
                    </div>
                </div>
            </div>

            <div class="card-soft p-4 mb-3">
                <h6 class="mb-1">Generación de jornadas</h6>
                <p class="text-secondary small mb-3">
                    Cuántas jornadas se generan a la vez según la paridad de equipos en el grupo.
                </p>
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label">Ascenso / descenso por cancha</label>
                        <input type="number" name="promotion_relegation" min="1" max="3" class="form-control"
                            value="{{ old('promotion_relegation', $league->promotion_relegation ?? 1) }}">
                        <small class="text-muted">
                            Cuántos jugadores suben a la cancha de arriba y bajan a la de abajo cada jornada.
                        </small>
                    </div>
                    <!-- <div class="col-md-6">
                        <label class="form-label small">Jornadas pares <small class="text-secondary">(grupos con # par de equipos)</small></label>
                        <input type="number" name="jornadas_pares" min="1" max="10"
                            class="form-control" value="{{ old('jornadas_pares', $league->jornadas_pares) }}">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label small">Jornadas nones <small class="text-secondary">(grupos con # impar de equipos)</small></label>
                        <input type="number" name="jornadas_nones" min="1" max="10"
                            class="form-control" value="{{ old('jornadas_nones', $league->jornadas_nones) }}">
                    </div> -->
                </div>
            </div>

            <div class="card-soft p-4 mb-3">
                <h6 class="mb-1">Orden de clasificación (desempate)</h6>
                <p class="text-secondary small mb-3">
                    Arrastra para ordenar los criterios. El primero decide la posición;
                    los siguientes rompen empates. Aplica a la tabla y al ascenso/descenso.
                </p>

                @php
                $labels = [
                'diff' => ['Diferencia de juegos', 'Juegos ganados − perdidos'],
                'won' => ['Juegos ganados', 'Total de juegos ganados'],
                'rounds' => ['Set ganados', 'Cuántas rondas ganó'],
                ];
                $current = old('standings_order', $league->standingsOrder());
                // Ensure any metric not in the saved order still appears (at the end)
                $ordered = array_values(array_unique(array_merge($current, array_keys($labels))));
                @endphp

                <ul class="standings-order-list" id="standings-order-list">
                    @foreach ($ordered as $i => $key)
                    <li class="standings-order-item" data-key="{{ $key }}">
                        <i class="fa-solid fa-grip-vertical drag-handle"></i>
                        <span class="standings-order-rank">{{ $i + 1 }}</span>
                        <div class="standings-order-text">
                            <strong>{{ $labels[$key][0] }}</strong>
                            <small class="text-muted">{{ $labels[$key][1] }}</small>
                        </div>
                        <input type="hidden" name="standings_order[]" value="{{ $key }}">
                    </li>
                    @endforeach
                </ul>
            </div>
        </div>

        {{-- TAB: Branding --}}
        <div class="tab-pane fade" id="tab-branding">
            <div class="card-soft p-4">
                <h6 class="mb-3">Banner</h6>

                @if ($league->banner_url)
                <div class="mb-3">
                    <img src="{{ $league->banner_url }}" alt="Banner actual"
                        style="max-height:160px;border-radius:var(--radius-md);">
                </div>
                @endif

                <input type="file" name="banner" accept="image/png,image/jpeg,image/webp"
                    class="form-control @error('banner') is-invalid @enderror">
                @error('banner') <div class="invalid-feedback">{{ $message }}</div> @enderror
                <small class="text-secondary d-block mt-2">PNG, JPG o WEBP · máx. 4 MB.</small>
            </div>

            @unless ($isEditing)
            {{-- On CREATE, the submit lives only here (Branding is the last tab) --}}
            <div class="d-flex gap-2 mt-4">
                <button type="submit" class="btn btn-primary">
                    <i class="fa-solid fa-floppy-disk me-1"></i> Crear liga
                </button>
                <a href="{{ route('leagues.index') }}" class="btn btn-outline-secondary">Cancelar</a>
            </div>
            @endunless
        </div>
    </div>

    @if ($isEditing)
    <div class="d-flex gap-2 mt-4">
        <button type="submit" class="btn btn-primary" id="league-submit">
            <i class="fa-solid fa-floppy-disk me-1"></i>
            Guardar cambios
        </button>
        <button type="button" class="btn btn-outline-danger ms-auto" id="delete-league-btn"
            data-action="{{ route('leagues.destroy', $league) }}">
            <i class="fa-solid fa-trash me-1"></i> Eliminar
        </button>
    </div>
    @endif
</form>

<form id="delete-league-form" method="POST" action="" class="d-none">
    @csrf
    @method('DELETE')
</form>