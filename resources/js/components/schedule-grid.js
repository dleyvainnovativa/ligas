export function mountScheduleGrid() {
    const app = document.querySelector('.grid-app');
    if (!app) return;

    const scheduleUrlTpl = app.dataset.scheduleUrlTemplate;
    // We removed autofit; if data attr is still present, ignore it.

    let draggedEl = null;

    function wireDraggables() {
        document.querySelectorAll('.cancha-pill:not(.is-scheduled), .cell-cancha').forEach((el) => {
            if (el.dataset.dndWired) return;
            el.dataset.dndWired = '1';
            el.addEventListener('dragstart', (e) => {
                draggedEl = el;
                el.classList.add('dragging');
                e.dataTransfer.effectAllowed = 'move';
                e.dataTransfer.setData('text/plain', el.dataset.canchaId);
            });
            el.addEventListener('dragend', () => {
                draggedEl?.classList.remove('dragging');
                draggedEl = null;
            });
        });
    }

    document.querySelectorAll('.grid-cell').forEach((cell) => {
        cell.addEventListener('dragenter', (e) => {
            e.preventDefault();
            cell.classList.add('drag-over');
        });
        cell.addEventListener('dragleave', () => cell.classList.remove('drag-over'));
        cell.addEventListener('dragover', (e) => {
            e.preventDefault();
            e.dataTransfer.dropEffect = 'move';
        });
        cell.addEventListener('drop', async (e) => {
            e.preventDefault();
            cell.classList.remove('drag-over');
            if (!draggedEl) return;

            const canchaId = draggedEl.dataset.canchaId;
            const date     = cell.dataset.date;
            const slot     = cell.dataset.slot;
            const pistaId  = parseInt(cell.dataset.pistaId, 10);

            try {
                const url = scheduleUrlTpl.replace('__ID__', canchaId);
                await window.app.api.put(url, { date, time_slot: slot, pista_id: pistaId });
                window.location.reload();
            } catch (err) {
                window.app.toast.error(err.message);
            }
        });
    });

    // Clear button on a scheduled cell-cancha
    document.querySelectorAll('.cell-cancha-clear').forEach((btn) => {
        btn.addEventListener('click', async (e) => {
            e.stopPropagation();
            const box = btn.closest('.cell-cancha');
            const id = box.dataset.canchaId;
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
            if (!data.conflicts.length) {
                body.innerHTML = `<div class="text-success"><i class="fa-solid fa-circle-check me-1"></i> Sin conflictos. 🎉</div>`;
            } else {
                body.innerHTML = `
                    <p class="text-secondary small mb-2">${data.conflicts.length} jugador(es) en más de una cancha al mismo tiempo:</p>
                    <ul class="mb-0">
                        ${data.conflicts.map(c => `
                            <li>
                                <strong>${escapeHtml(c.player_name)}</strong> el ${c.date} a las ${c.time_slot}
                                — canchas: ${c.cancha_ids.join(', ')}
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
            const data = await window.app.api.post(agBtn.dataset.url, { clear_existing: clearExisting });
            if (data.ok) window.app.toast.success(data.message);
            else if (data.placed > 0) window.app.toast.warn(data.message);
            else window.app.toast.error(data.message || 'No se pudo generar el calendario.');
            window.app.modal.close('auto-generate-modal');
            setTimeout(() => window.location.reload(), 600);
        } catch (err) {
            window.app.toast.error(err.message);
            window.app.loading.off(agConfirmBtn);
        }
    });

    wireDraggables();
}

function escapeHtml(s) {
    return String(s).replace(/[&<>"']/g, c => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]));
}