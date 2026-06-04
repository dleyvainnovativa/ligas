export function mountScheduleGrid() {
    const app = document.querySelector('.grid-app');
    if (!app) return;

    const mode = app.dataset.mode; // 'individual' | 'pairs'
    const scheduleUrlTpl = app.dataset.scheduleUrlTemplate; // .../matches/__ID__/schedule
    const autofitUrlTpl  = app.dataset.autofitUrlTemplate;  // .../canchas/__ID__/auto-fit

    let draggedEl = null;
    let draggedKind = null; // 'match' (single match) or 'pill' (could trigger auto-fit on individual)

    // Wire all draggables — match pills in sidebar, cell-match boxes in grid
    function wireDraggables() {
        document.querySelectorAll('.match-pill:not(.is-scheduled), .cell-match').forEach((el) => {
            if (el.dataset.dndWired) return;
            el.dataset.dndWired = '1';

            el.addEventListener('dragstart', (e) => {
                draggedEl = el;
                draggedKind = el.classList.contains('match-pill') ? 'pill' : 'cell';
                el.classList.add('dragging');
                e.dataTransfer.effectAllowed = 'move';
                e.dataTransfer.setData('text/plain', el.dataset.matchId);
            });
            el.addEventListener('dragend', () => {
                draggedEl?.classList.remove('dragging');
                clearSuggestions();
                draggedEl = null;
                draggedKind = null;
            });
        });
    }

    // Drop targets: cells
    document.querySelectorAll('.grid-cell').forEach((cell) => {
        cell.addEventListener('dragenter', (e) => {
            e.preventDefault();
            cell.classList.add('drag-over');
            // Suggestion: highlight the next N consecutive slots in the same pista in individual mode (when dragging from sidebar pill of first rotation)
            highlightSuggestions(cell);
        });
        cell.addEventListener('dragleave', () => {
            cell.classList.remove('drag-over');
        });
        cell.addEventListener('dragover', (e) => {
            e.preventDefault();
            e.dataTransfer.dropEffect = 'move';
        });
        cell.addEventListener('drop', async (e) => {
            e.preventDefault();
            cell.classList.remove('drag-over');
            if (!draggedEl) return;

            const matchId  = draggedEl.dataset.matchId;
            const canchaId = draggedEl.dataset.canchaId;
            const rotation = parseInt(draggedEl.dataset.rotation, 10);
            const date     = cell.dataset.date;
            const slot     = cell.dataset.slot;
            const pistaId  = parseInt(cell.dataset.pistaId, 10);

            // Auto-fit case: individual mode, dragging a sidebar pill of rotation 1
            const shouldAutoFit =
                mode === 'individual' &&
                draggedKind === 'pill' &&
                rotation === 1;

            try {
                if (shouldAutoFit) {
                    const url = autofitUrlTpl.replace('__ID__', canchaId);
                    await window.app.api.post(url, { date, time_slot: slot, pista_id: pistaId });
                } else {
                    const url = scheduleUrlTpl.replace('__ID__', matchId);
                    await window.app.api.put(url, { date, time_slot: slot, pista_id: pistaId });
                }
                window.location.reload(); // straightforward; cell state is complex enough that re-render saves headaches
            } catch (err) {
                window.app.toast.error(err.message);
            }
        });
    });

    function highlightSuggestions(originCell) {
        clearSuggestions();
        if (!draggedEl) return;
        if (mode !== 'individual' || draggedKind !== 'pill') return;
        const rotation = parseInt(draggedEl.dataset.rotation, 10);
        if (rotation !== 1) return;

        // Same pista column: highlight next 2 cells in this pista's slot order
        const pistaId = originCell.dataset.pistaId;
        const date    = originCell.dataset.date;
        const slot    = originCell.dataset.slot;

        // Find cells with same date+pista, slot strictly greater
        const candidates = Array.from(document.querySelectorAll(`.grid-cell[data-date="${date}"][data-pista-id="${pistaId}"]`));
        const sorted = candidates.sort((a,b) => a.dataset.slot.localeCompare(b.dataset.slot));
        const idx = sorted.findIndex(c => c.dataset.slot === slot);
        if (idx === -1) return;
        for (let i = 1; i <= 2; i++) {
            sorted[idx + i]?.classList.add('drag-suggest');
        }
    }

    function clearSuggestions() {
        document.querySelectorAll('.drag-suggest').forEach(el => el.classList.remove('drag-suggest'));
    }

    // Clear button on a scheduled cell-match
    document.querySelectorAll('.cell-match-clear').forEach((btn) => {
        btn.addEventListener('click', async (e) => {
            e.stopPropagation();
            const box = btn.closest('.cell-match');
            const id = box.dataset.matchId;
            try {
                const url = scheduleUrlTpl.replace('__ID__', id);
                await window.app.api.put(url, { date: null, time_slot: null, pista_id: null });
                window.location.reload();
            } catch (err) {
                window.app.toast.error(err.message);
            }
        });
    });

    // Conflicts
    const cBtn = document.getElementById('check-conflicts-btn');
    cBtn?.addEventListener('click', async () => {
        try {
            const data = await window.app.api.get(cBtn.dataset.url);
            const body = document.getElementById('conflicts-body');
            console.log(data);
            if (!data.conflicts.length) {
                body.innerHTML = `<div class="text-success"><i class="fa-solid fa-circle-check me-1"></i> Sin conflictos. </div>`;
            } else {
                body.innerHTML = `
                    <p class="text-secondary small mb-2">${data.conflicts.length} jugador(es) están en más de un partido al mismo tiempo:</p>
                    <ul class="mb-0">
                        ${data.conflicts.map(c => `
                            <li>
                                <strong>${c.player_name} #${c.player_id}</strong> el ${c.date} a las ${c.time_slot}
                                — partidos: ${c.match_ids.join(', ')}
                            </li>
                        `).join('')}
                    </ul>`;
            }
            window.app.modal.open('conflicts-modal');
        } catch (err) {
            window.app.toast.error(err.message);
        }
    });

    // Auto-generate
const agBtn = document.getElementById('auto-generate-btn');
agBtn?.addEventListener('click', () => {
    document.getElementById('ag-clear-existing').checked = false;
    window.app.modal.open('auto-generate-modal');
});

const agConfirmBtn = document.getElementById('ag-confirm-btn');
agConfirmBtn?.addEventListener('click', async () => {
    const clearExisting = document.getElementById('ag-clear-existing').checked;
    window.app.loading.on(agConfirmBtn);
    try {
        const data = await window.app.api.post(agBtn.dataset.url, {
            clear_existing: clearExisting,
        });

        if (data.ok) {
            window.app.toast.success(data.message);
        } else if (data.placed > 0) {
            window.app.toast.warn(data.message);
        } else {
            window.app.toast.error(data.message || 'No se pudo generar el calendario.');
        }

        window.app.modal.close('auto-generate-modal');
        setTimeout(() => window.location.reload(), 600);
    } catch (err) {
        window.app.toast.error(err.message);
        window.app.loading.off(agConfirmBtn);
    }
});

    wireDraggables();
}