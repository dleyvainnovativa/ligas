/**
 * Tap-to-place picker for the schedule grid.
 *
 * Two modes:
 *  - Empty cell tap → show list of unscheduled matches to assign
 *  - Occupied cell tap → show actions for the existing match
 *
 * Plus a reschedule sub-mode: after tapping "Mover a otra celda" on an
 * occupied cell, the picker stays open and the next cell tap moves the
 * match to that location.
 */

const HINT_KEY = 'pl_picker_hint_seen';

export function mountCellPicker() {
    const grid = document.querySelector('.grid-app');
    if (!grid) return;
    if (grid.dataset.readonly === '1') return;
    const mode = grid.dataset.mode;                                  // 'individual' | 'pairs'
    const scheduleUrlTpl = grid.dataset.scheduleUrlTemplate;         // .../matches/__ID__/schedule
    const autofitUrlTpl  = grid.dataset.autofitUrlTemplate;          // .../canchas/__ID__/auto-fit

    const picker     = document.getElementById('cell-picker');
    const panel      = picker.querySelector('.cell-picker-panel');
    const eyebrow    = document.getElementById('picker-eyebrow');
    const title      = document.getElementById('picker-title');
    const body       = document.getElementById('picker-body');
    const hint       = document.getElementById('picker-hint');

    let activeCell = null;
    let allMatchData = collectMatchData();
    let isDesktop = window.matchMedia('(min-width: 992px)').matches;
    let rescheduleMatchId = null;   // when set, picker is in "pick a target cell" mode

    window.matchMedia('(min-width: 992px)').addEventListener('change', (e) => {
        isDesktop = e.matches;
        if (picker.classList.contains('is-open')) {
            positionPanel();
        }
    });

    // ---- Cell tap handler ----
    document.addEventListener('click', (e) => {
    // Skip if clicking on the small icon buttons on a scheduled cancha
    if (e.target.closest('.cell-cancha-clear, .cell-cancha-result')) return;

    const cell = e.target.closest('.grid-cell');
    if (!cell || !grid.contains(cell)) return;

    if (document.querySelector('.dragging')) return;

    if (rescheduleMatchId) {
        e.stopImmediatePropagation();
        handleRescheduleTarget(cell);
        return;
    }

    openPicker(cell);
});

    // ---- Close / dismiss handlers (use closest so clicks on inner <i> also work) ----
    document.addEventListener('click', (e) => {
        if (e.target.closest('[data-action="close-picker"]')) {
            closePicker();
            return;
        }
        if (e.target.closest('[data-action="dismiss-hint"]')) {
            dismissHint();
            return;
        }
    });
    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape' && picker.classList.contains('is-open')) closePicker();
    });

    // Desktop: close on outside click (but not during reschedule)
    document.addEventListener('mousedown', (e) => {
        if (!isDesktop) return;
        if (!picker.classList.contains('is-open')) return;
        if (rescheduleMatchId) return;
        if (panel.contains(e.target)) return;
        if (e.target.closest('.grid-cell')) return;
        closePicker();
    });

    // ---- Main entry point ----
    function openPicker(cell) {
    if (activeCell) activeCell.classList.remove('picker-active');
    activeCell = cell;
    cell.classList.add('picker-active');

    if (!isDesktop) {
        const rect = cell.getBoundingClientRect();
        const viewport = window.innerHeight;
        const sheetHeight = viewport * 0.85;
        const cellBottom = rect.bottom;
        const visibleArea = viewport - sheetHeight;
        if (cellBottom > visibleArea - 20) {
            window.scrollBy({
                top: cellBottom - visibleArea + 40,
                behavior: 'smooth',
            });
        }
    }

    const existing = cell.querySelector('.cell-cancha');   // ← was .cell-match
    renderHeader(cell);

    if (existing) {
        renderOccupiedActions(cell, existing);
    } else {
        renderEmptyPicker(cell);
    }

    picker.classList.add('is-open');
    positionPanel();
    maybeShowHint();
    trapFocus();
}

    function closePicker() {
        picker.classList.remove('is-open');
        panel.classList.remove('is-minimized');
        rescheduleMatchId = null;
        if (activeCell) activeCell.classList.remove('picker-active');
        activeCell = null;
        body.innerHTML = '';
    }

    // ---- Header ----
    function renderHeader(cell) {
        const date = cell.dataset.date;
        const slot = cell.dataset.slot;
        const headerCell = findRowHeader(cell);
        const pistaName  = headerCell?.querySelector('.pista-name')?.textContent.trim() || '';

        eyebrow.textContent = `${formatDateShort(date)} · ${slot}`;
        title.textContent = pistaName || 'Asignar partido';
    }

    function findRowHeader(cell) {
        const row = cell.closest('tr');
        return row?.querySelector('.row-header-cell');
    }

    // ---- Empty cell: list of unscheduled matches ----
    function renderEmptyPicker(cell) {
        const unscheduled = allMatchData.filter(m => !m.scheduled);

        if (unscheduled.length === 0) {
            body.innerHTML = `
                <div class="picker-empty">
                    <i class="fa-regular fa-circle-check"></i>
                    Todos los partidos ya están programados.
                </div>`;
            return;
        }

        // Group by cancha for clarity
        const groups = {};
        unscheduled.forEach(m => {
            (groups[m.canchaId] ||= { label: m.canchaLabel, matches: [] }).matches.push(m);
        });

        const searchHtml = unscheduled.length > 6 ? `
            <div class="picker-search">
                <input type="search" class="form-control form-control-sm"
                       id="picker-search-input"
                       placeholder="Buscar cancha o jugador…"
                       autocomplete="off">
            </div>` : '';

        const groupsHtml = Object.entries(groups).map(([cid, g]) => {
            const matches = g.matches.map(m => matchRowHtml(m)).join('');
            return `
                <div class="picker-cancha-group" data-cancha-id="${cid}">
                    <div class="picker-cancha-label">
                        <i class="fa-solid fa-table-cells text-muted"></i>
                        ${escape(g.label)}
                        <span class="count">${g.matches.length} sin asignar</span>
                    </div>
                    ${matches}
                </div>`;
        }).join('');

        body.innerHTML = `
            <div class="picker-section-label">Partidos disponibles</div>
            ${searchHtml}
            <div id="picker-matches-list">${groupsHtml}</div>
        `;

        wireMatchRows(cell);
        wireSearch();
    }

    function matchRowHtml(m) {
    return `
        <button type="button" class="picker-match" data-cancha-id="${m.canchaId}">
            <span class="picker-match-rot"><i class="fa-solid fa-table-cells" style="font-size:10px;"></i></span>
            <span class="picker-match-info">
                <span class="picker-match-teams">
                    ${m.playerNames.map(n => escape(n)).join(' · ')}
                </span>
                <span class="picker-match-meta">${escape(m.canchaLabel)}</span>
            </span>
            <i class="fa-solid fa-chevron-right picker-match-arrow"></i>
        </button>
    `;
}

    function autofitRowHtml(m) {
        return `
            <button type="button" class="picker-match picker-autofit"
                    data-action="autofit" data-cancha-id="${m.canchaId}">
                <span class="picker-match-rot" style="background: var(--brand-500); color: var(--neutral-900);">
                    <i class="fa-solid fa-wand-magic-sparkles" style="font-size:10px;"></i>
                </span>
                <span class="picker-match-info">
                    <span class="picker-autofit-title">
                        Asignar las 3 rotaciones aquí
                    </span>
                    <span class="picker-autofit-desc">
                        R1, R2 y R3 en horarios consecutivos en esta pista
                    </span>
                </span>
                <i class="fa-solid fa-chevron-right picker-match-arrow"></i>
            </button>`;
    }

    function wireMatchRows(cell) {
    body.querySelectorAll('.picker-match').forEach(btn => {
        btn.addEventListener('click', async () => {
            const canchaId = btn.dataset.canchaId;
            if (!canchaId) {
                window.app.toast.error('No se pudo identificar la cancha.');
                return;
            }

            btn.disabled = true;
            btn.innerHTML = `<span class="spinner-border spinner-border-sm me-2"></span>Asignando…`;

            try {
                const url = scheduleUrlTpl.replace('__ID__', canchaId);
                await window.app.api.put(url, {
                    date:      cell.dataset.date,
                    time_slot: cell.dataset.slot,
                    pista_id:  parseInt(cell.dataset.pistaId, 10),
                });
                closePicker();
                window.location.reload();
            } catch (err) {
                window.app.toast.error(err.message);
                btn.disabled = false;
            }
        });
    });
}

    function wireSearch() {
        const input = document.getElementById('picker-search-input');
        if (!input) return;
        input.addEventListener('input', () => {
            const q = input.value.trim().toLowerCase();
            body.querySelectorAll('.picker-cancha-group').forEach(group => {
                let groupHas = false;
                group.querySelectorAll('.picker-match').forEach(row => {
                    const text = row.textContent.toLowerCase();
                    const match = !q || text.includes(q);
                    row.style.display = match ? '' : 'none';
                    if (match) groupHas = true;
                });
                group.style.display = groupHas ? '' : 'none';
            });
        });
    }

    // ---- Occupied cell: action list ----
    function renderOccupiedActions(cell, canchaEl) {
    const canchaId = canchaEl.dataset.canchaId;
    const c = allMatchData.find(x => String(x.id) === String(canchaId));
    if (!c) { closePicker(); return; }

    const completedHtml = c.completed
        ? `<small class="text-success"><i class="fa-solid fa-check-circle me-1"></i>Resultados guardados</small>`
        : '';

    body.innerHTML = `
        <div class="picker-current-match">
            <div class="picker-current-teams">
                <span style="grid-column: 1 / -1;">${c.playerNames.map(n => escape(n)).join(' · ')}</span>
            </div>
            <div class="text-muted small mt-2 font-mono">
                ${escape(c.canchaLabel)}
            </div>
            ${completedHtml}
        </div>

        <button type="button" class="picker-action" data-action="edit-result">
            <span class="picker-action-icon"><i class="fa-solid fa-pencil"></i></span>
            <span class="picker-action-text">
                <strong>${c.completed ? 'Editar resultados' : 'Ingresar resultados'}</strong>
                <small>${c.completed ? 'Modificar los marcadores' : 'Anotar los sets jugados'}</small>
            </span>
            <i class="fa-solid fa-chevron-right text-muted"></i>
        </button>

        <button type="button" class="picker-action is-danger" data-action="clear">
            <span class="picker-action-icon"><i class="fa-solid fa-eraser"></i></span>
            <span class="picker-action-text">
                <strong>Quitar de la programación</strong>
                <small>La cancha vuelve a la lista de pendientes</small>
            </span>
            <i class="fa-solid fa-chevron-right text-muted"></i>
        </button>
    `;

    wireOccupiedActions(canchaEl, canchaId);
}

    function wireOccupiedActions(canchaEl, canchaId) {
    body.querySelector('[data-action="edit-result"]')?.addEventListener('click', () => {
        closePicker();
        // The cell-cancha-result button on the grid is what opens the result modal
        canchaEl.querySelector('.cell-cancha-result')?.click();
    });

    body.querySelector('[data-action="clear"]')?.addEventListener('click', async () => {
        closePicker();

        const ok = await window.app.modal.confirm({
            title: 'Quitar cancha',
            body: '¿Quitar esta cancha de la programación? Quedará pendiente de asignar.',
            confirmText: 'Quitar',
            danger: true,
        });
        if (!ok) return;

        try {
            const url = scheduleUrlTpl.replace('__ID__', canchaId);
            await window.app.api.put(url, { date: null, time_slot: null, pista_id: null });
            window.location.reload();
        } catch (err) {
            window.app.toast.error(err.message);
        }
    });
}

    // ---- Reschedule mode ----
    function renderRescheduleMode(matchId) {
        rescheduleMatchId = matchId;
        title.textContent = 'Selecciona la nueva celda';
        eyebrow.textContent = 'MOVER PARTIDO';
        body.innerHTML = `
            <div class="picker-reschedule-tip">
                <i class="fa-solid fa-hand-pointer"></i>
                <p class="mb-0">
                    Toca una celda vacía del calendario para mover este partido ahí.
                    Las celdas ocupadas no se pueden seleccionar.
                </p>
            </div>
            <button type="button" class="btn btn-outline-secondary w-100 mt-3" data-action="cancel-reschedule">
                Cancelar
            </button>
        `;

        body.querySelector('[data-action="cancel-reschedule"]').addEventListener('click', () => {
            rescheduleMatchId = null;
            closePicker();
        });

        // On mobile, slide the sheet down so the grid is visible
        if (!isDesktop) {
            panel.classList.add('is-minimized');
        }
    }

    async function handleRescheduleTarget(cell) {
        const matchId = rescheduleMatchId;
        if (!matchId) return;

        if (cell.querySelector('.cell-match')) {
            window.app.toast.warn('Esa celda ya está ocupada. Elige una vacía.');
            return;
        }

        rescheduleMatchId = null;

        try {
            const url = scheduleUrlTpl.replace('__ID__', matchId);
            await window.app.api.put(url, {
                date:      cell.dataset.date,
                time_slot: cell.dataset.slot,
                pista_id:  parseInt(cell.dataset.pistaId, 10),
            });
            closePicker();
            window.location.reload();
        } catch (err) {
            window.app.toast.error(err.message);
        }
    }

    // ---- Desktop popover positioning ----
    function positionPanel() {
        if (!isDesktop || !activeCell) {
            panel.style.removeProperty('top');
            panel.style.removeProperty('left');
            return;
        }

        const rect = activeCell.getBoundingClientRect();
        const panelW = 360;
        const panelH = Math.min(480, window.innerHeight - 32);
        const margin = 8;

        // Anchor to the right of the cell by default; flip left if not enough room
        let left = rect.right + margin;
        if (left + panelW + margin > window.innerWidth) {
            left = rect.left - panelW - margin;
        }
        if (left < margin) {
            left = Math.max(margin, rect.left);
        }

        let top = rect.top;
        if (top + panelH + margin > window.innerHeight) {
            top = Math.max(margin, window.innerHeight - panelH - margin);
        }

        panel.style.position = 'fixed';
        panel.style.top  = `${top}px`;
        panel.style.left = `${left}px`;
    }

    // Re-position on scroll/resize
    let scrollRaf = null;
    function onScrollOrResize() {
        if (scrollRaf) cancelAnimationFrame(scrollRaf);
        scrollRaf = requestAnimationFrame(positionPanel);
    }
    window.addEventListener('scroll', onScrollOrResize, true);
    window.addEventListener('resize', onScrollOrResize);

    // ---- First-time hint ----
    function maybeShowHint() {
        if (localStorage.getItem(HINT_KEY)) return;
        hint.hidden = false;
        setTimeout(dismissHint, 6500);
    }
    function dismissHint() {
        hint.hidden = true;
        localStorage.setItem(HINT_KEY, '1');
    }

    // ---- Focus management ----
    let lastFocus = null;
    function trapFocus() {
        lastFocus = document.activeElement;
        setTimeout(() => {
            panel.querySelector('button:not([disabled])')?.focus();
        }, 100);
    }

    // ---- Collect match data from the DOM (sidebar pills + grid cells) ----
    function collectMatchData() {
    const canchas = [];
    const seen = new Set();

    document.querySelectorAll('.cancha-chip').forEach(chip => {
        const id = chip.dataset.canchaId;
        if (!id || seen.has(id)) return;
        seen.add(id);

        const pill = chip.querySelector('.cancha-pill');
        const label = chip.querySelector('strong')?.textContent.trim() || `Cancha ${id}`;

        const playerNames = pill
            ? Array.from(pill.querySelectorAll('.cancha-pill-players > div')).map(el => el.textContent.trim())
            : [];

        canchas.push({
            id,
            canchaId: id,                           // picker reads this
            canchaLabel: label,
            scheduled: pill ? pill.classList.contains('is-scheduled') : false,
            completed: false,                       // refined below from grid
            playerNames,
            // Compatibility-shim fields so the picker render functions
            // (which still reference teamAText/teamBText/rotationIndex) keep working:
            teamAText: playerNames.slice(0, 2).join(' / ') || 'Equipo A',
            teamBText: playerNames.slice(2, 4).join(' / ') || 'Equipo B',
            rotationIndex: 1,
            canchaHasMultipleMatches: false,        // auto-fit no longer applies
        });
    });

    // Refine state from grid cells (which know about completion)
    document.querySelectorAll('.cell-cancha').forEach(box => {
        const id = box.dataset.canchaId;
        const found = canchas.find(m => String(m.id) === String(id));
        if (found) {
            found.scheduled = true;
            found.completed = box.classList.contains('is-completed');
        }
    });

    return canchas;
}

    function formatDateShort(iso) {
        if (!iso) return '';
        const d = new Date(iso + 'T00:00:00');
        return d.toLocaleDateString('es-MX', { weekday: 'short', day: '2-digit', month: 'short' });
    }

    function escape(s) {
        return String(s).replace(/[&<>"']/g, c => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]));
    }
}