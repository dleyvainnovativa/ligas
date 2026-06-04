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
        // Skip if clicking on the small icon buttons on a scheduled match
        // (the ✕ and ✎ shortcuts) — they have their own handlers.
        if (e.target.closest('.cell-match-clear, .cell-match-result')) return;

        const cell = e.target.closest('.grid-cell');
        if (!cell || !grid.contains(cell)) return;

        // Skip if we're mid-drag
        if (document.querySelector('.dragging')) return;

        // If we're in reschedule mode, route to target handling and stop here
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

        // On mobile, scroll the cell up so it's not behind the bottom sheet
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

        const existing = cell.querySelector('.cell-match');
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
        const showAutofit = (mode === 'individual' && m.rotationIndex === 1 && m.canchaHasMultipleMatches);
        return `
            <button type="button" class="picker-match" data-match-id="${m.id}" data-cancha-id="${m.canchaId}">
                <span class="picker-match-rot">R${m.rotationIndex}</span>
                <span class="picker-match-info">
                    <span class="picker-match-teams">
                        ${escape(m.teamAText)} <span class="vs">vs</span> ${escape(m.teamBText)}
                    </span>
                    <span class="picker-match-meta">${escape(m.canchaLabel)}</span>
                </span>
                <i class="fa-solid fa-chevron-right picker-match-arrow"></i>
            </button>
            ${showAutofit ? autofitRowHtml(m) : ''}
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
                const matchId  = btn.dataset.matchId;
                const canchaId = btn.dataset.canchaId;
                const isAutofit = btn.dataset.action === 'autofit';

                btn.disabled = true;
                btn.innerHTML = `<span class="spinner-border spinner-border-sm me-2"></span>Asignando…`;

                try {
                    if (isAutofit) {
                        const url = autofitUrlTpl.replace('__ID__', canchaId);
                        await window.app.api.post(url, {
                            date:      cell.dataset.date,
                            time_slot: cell.dataset.slot,
                            pista_id:  parseInt(cell.dataset.pistaId, 10),
                        });
                    } else {
                        const url = scheduleUrlTpl.replace('__ID__', matchId);
                        await window.app.api.put(url, {
                            date:      cell.dataset.date,
                            time_slot: cell.dataset.slot,
                            pista_id:  parseInt(cell.dataset.pistaId, 10),
                        });
                    }
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
    function renderOccupiedActions(cell, matchEl) {
        const matchId = matchEl.dataset.matchId;
        const m = allMatchData.find(x => String(x.id) === String(matchId));
        if (!m) { closePicker(); return; }

        const completedHtml = m.completed
            ? `<small class="text-success"><i class="fa-solid fa-check-circle me-1"></i>Resultado guardado</small>`
            : '';

        body.innerHTML = `
            <div class="picker-current-match">
                <div class="picker-current-teams">
                    <span>${escape(m.teamAText)}</span>
                    <span class="vs">vs</span>
                    <span class="text-end">${escape(m.teamBText)}</span>
                </div>
                <div class="text-muted small mt-2 font-mono">
                    R${m.rotationIndex} · ${escape(m.canchaLabel)}
                </div>
                ${completedHtml}
            </div>

            <button type="button" class="picker-action" data-action="edit-result">
                <span class="picker-action-icon"><i class="fa-solid fa-pencil"></i></span>
                <span class="picker-action-text">
                    <strong>${m.completed ? 'Editar resultado' : 'Ingresar resultado'}</strong>
                    <small>${m.completed ? 'Modificar el marcador' : 'Anotar sets jugados'}</small>
                </span>
                <i class="fa-solid fa-chevron-right text-muted"></i>
            </button>

            <button type="button" class="picker-action is-danger" data-action="clear">
                <span class="picker-action-icon"><i class="fa-solid fa-eraser"></i></span>
                <span class="picker-action-text">
                    <strong>Quitar de la programación</strong>
                    <small>El partido vuelve a la lista de pendientes</small>
                </span>
                <i class="fa-solid fa-chevron-right text-muted"></i>
            </button>
        `;

        wireOccupiedActions(matchEl, matchId);
    }

    function wireOccupiedActions(matchEl, matchId) {
        
        body.querySelector('[data-action="edit-result"]')?.addEventListener('click', () => {
            closePicker();
            // Trigger the existing result modal handler
            matchEl.querySelector('.cell-match-result')?.click();
        });

        body.querySelector('[data-action="reschedule"]')?.addEventListener('click', () => {
            renderRescheduleMode(matchId);
        });

        body.querySelector('[data-action="clear"]')?.addEventListener('click', async () => {
    closePicker();   // ← close picker first so the confirm modal stands alone

    const ok = await window.app.modal.confirm({
        title: 'Quitar partido',
        body: '¿Quitar este partido de la programación? Quedará pendiente de asignar.',
        confirmText: 'Quitar',
        danger: true,
    });
    if (!ok) return;

    try {
        const url = scheduleUrlTpl.replace('__ID__', matchId);
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
        const matches = [];
        const seen = new Set();

        // Sidebar canchas: every cancha lists its matches (scheduled + unscheduled)
        document.querySelectorAll('.cancha-chip').forEach(chip => {
            const canchaId = chip.dataset.canchaId;
            const canchaLabel = chip.querySelector('strong')?.textContent.trim() || `Cancha ${canchaId}`;
            const pills = chip.querySelectorAll('.match-pill');
            const hasMultiple = pills.length > 1;
            pills.forEach(pill => {
                const id = pill.dataset.matchId;
                if (seen.has(id)) return;
                seen.add(id);
                const teamsEls = pill.querySelectorAll('.match-pill-teams > div');
                matches.push({
                    id,
                    canchaId,
                    canchaLabel,
                    canchaHasMultipleMatches: hasMultiple,
                    rotationIndex: parseInt(pill.dataset.rotation, 10),
                    scheduled: pill.classList.contains('is-scheduled'),
                    completed: false,
                    teamAText: (teamsEls[0]?.textContent || '').trim(),
                    teamBText: (teamsEls[2]?.textContent || '').trim(),
                });
            });
        });

        // Grid cells: refine scheduled + completion state for any matches placed on the grid
        document.querySelectorAll('.cell-match').forEach(box => {
            const id = box.dataset.matchId;
            const found = matches.find(m => String(m.id) === String(id));
            if (found) {
                found.scheduled = true;
                found.completed = box.classList.contains('is-completed');
            }
        });

        return matches;
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