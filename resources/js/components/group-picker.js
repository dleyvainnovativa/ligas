/**
 * Tap-to-assign picker for the groups screen.
 *
 * Tapping a chip (player or pair) opens a picker listing destination groups.
 * For a chip currently in a group, also shows "Quitar del grupo".
 *
 * Also wires the per-group auto-fill button.
 */

export function mountGroupPicker() {
    const app = document.querySelector('.groups-app');
    if (!app) return;

    const leagueId = app.dataset.leagueId;
    const mode     = app.dataset.mode; // 'individual' | 'pairs'

    const url = {
        movePlayer:   `/leagues/${leagueId}/groups/move-player`,
        movePair:     `/leagues/${leagueId}/groups/move-pair`,
        autoFill:     (gid) => `/leagues/${leagueId}/groups/${gid}/auto-fill`,
    };

    const picker  = document.getElementById('chip-picker');
    if (!picker) return;
    const panel   = picker.querySelector('.cell-picker-panel');
    const eyebrow = document.getElementById('chip-picker-eyebrow');
    const title   = document.getElementById('chip-picker-title');
    const body    = document.getElementById('chip-picker-body');

    let isDesktop = window.matchMedia('(min-width: 992px)').matches;
    let anchorChip = null;

    window.matchMedia('(min-width: 992px)').addEventListener('change', (e) => {
        isDesktop = e.matches;
        if (picker.classList.contains('is-open')) positionPanel();
    });

    // ---- Tap a chip → open picker ----
    document.addEventListener('click', (e) => {
        // Skip if dragging (Sortable adds a class to body during drag)
        if (document.body.classList.contains('sortable-dragging')) return;

        const chip = e.target.closest('.roster-chip');
        if (!chip) return;
        // Make sure it's inside the groups-app context, not some other roster
        if (!app.contains(chip)) return;

        // Skip clicks on action elements (currently none on chips, but future-proof)
        if (e.target.closest('button, a, input')) return;

        openPicker(chip);
    });

    document.addEventListener('keydown', (e) => {
    if (e.key !== 'Enter' && e.key !== ' ') return;
    const chip = e.target.closest('.roster-chip');
    if (!chip || !app.contains(chip)) return;
    e.preventDefault();
    openPicker(chip);
});

    // ---- Close handlers ----
    document.addEventListener('click', (e) => {
        if (e.target.closest('[data-action="close-chip-picker"]')) {
            closePicker();
        }
    });
    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape' && picker.classList.contains('is-open')) closePicker();
    });
    document.addEventListener('mousedown', (e) => {
        if (!isDesktop) return;
        if (!picker.classList.contains('is-open')) return;
        if (panel.contains(e.target)) return;
        if (e.target.closest('.roster-chip')) return;
        closePicker();
    });

    // ---- Open ----
    function openPicker(chip) {
        anchorChip = chip;

        const chipName = chip.querySelector('.chip-name')?.textContent.trim() || '';
        const currentList = chip.closest('.roster-list');
        const currentGroupId = currentList ? currentList.dataset.groupId : '0';
        const isInGroup = currentGroupId !== '0';

        eyebrow.textContent = isInGroup ? 'MOVER A' : 'ASIGNAR A GRUPO';
        title.textContent = chipName;

        body.innerHTML = renderDestinations(currentGroupId, isInGroup);
        wireDestinations(chip);

        picker.classList.add('is-open');
        positionPanel();
    }

    function closePicker() {
        picker.classList.remove('is-open');
        anchorChip = null;
        body.innerHTML = '';
    }

    function renderDestinations(currentGroupId, isInGroup) {
        const groups = Array.from(document.querySelectorAll('.group-card'));
        if (groups.length === 0) {
            return `
                <div class="picker-empty">
                    <i class="fa-solid fa-layer-group"></i>
                    No hay grupos todavía. Crea un grupo primero.
                </div>`;
        }

        const groupRows = groups.map(card => {
            const gid = card.dataset.groupId;
            const name = card.querySelector('.group-name')?.value.trim() || `Grupo ${gid}`;
            const count = card.querySelector('.group-count')?.textContent.trim() || '0';
            const isCurrent = String(gid) === String(currentGroupId);

            return `
                <button type="button" class="picker-action"
                        data-group-id="${gid}"
                        ${isCurrent ? 'disabled' : ''}>
                    <span class="picker-action-icon">
                        <i class="fa-solid fa-layer-group"></i>
                    </span>
                    <span class="picker-action-text">
                        <strong>${escape(name)}</strong>
                        <small>${count} ${count === '1' ? 'miembro' : 'miembros'}${isCurrent ? ' · Grupo actual' : ''}</small>
                    </span>
                    ${isCurrent
                        ? '<i class="fa-solid fa-check text-success"></i>'
                        : '<i class="fa-solid fa-chevron-right text-muted"></i>'}
                </button>`;
        }).join('');

        const unassignRow = isInGroup ? `
            <div class="picker-section-label mt-3">Otras opciones</div>
            <button type="button" class="picker-action is-danger" data-group-id="0">
                <span class="picker-action-icon"><i class="fa-solid fa-arrow-up-from-bracket"></i></span>
                <span class="picker-action-text">
                    <strong>Quitar del grupo</strong>
                    <small>Vuelve al pool de pendientes</small>
                </span>
                <i class="fa-solid fa-chevron-right text-muted"></i>
            </button>` : '';

        return `
            <div class="picker-section-label">Grupos</div>
            ${groupRows}
            ${unassignRow}
        `;
    }

    function wireDestinations(chip) {
        body.querySelectorAll('[data-group-id]').forEach(btn => {
            btn.addEventListener('click', async () => {
                const targetGroupId = btn.dataset.groupId;
                btn.disabled = true;
                btn.innerHTML = `<span class="spinner-border spinner-border-sm me-2"></span>Asignando…`;

                try {
                    if (mode === 'pairs') {
                        await window.app.api.post(url.movePair, {
                            pair_id:  parseInt(chip.dataset.pairId, 10),
                            group_id: targetGroupId === '0' ? null : parseInt(targetGroupId, 10),
                        });
                    } else {
                        await window.app.api.post(url.movePlayer, {
                            player_id: parseInt(chip.dataset.playerId, 10),
                            group_id:  targetGroupId === '0' ? null : parseInt(targetGroupId, 10),
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

    // ---- Per-group auto-fill ----
    document.addEventListener('click', async (e) => {
        const btn = e.target.closest('.auto-fill-group');
        if (!btn) return;

        const groupCard = btn.closest('.group-card');
        const groupId   = groupCard.dataset.groupId;
        const groupName = groupCard.querySelector('.group-name')?.value.trim() || 'el grupo';

        const unassigned = countUnassigned();
        if (unassigned === 0) {
            window.app.toast.info('No hay jugadores pendientes para asignar.');
            return;
        }

        const count = await promptCount({
            groupName,
            max: unassigned,
            defaultValue: unassigned,
        });
        if (count === null) return;

        btn.disabled = true;
        const originalHtml = btn.innerHTML;
        btn.innerHTML = `<span class="spinner-border spinner-border-sm"></span>`;

        try {
            const data = await window.app.api.post(btn.dataset.url, { count });
            window.app.toast.success(`${data.assigned} ${data.assigned === 1 ? 'asignado' : 'asignados'} a ${groupName}.`);
            window.location.reload();
        } catch (err) {
            window.app.toast.error(err.message);
            btn.disabled = false;
            btn.innerHTML = originalHtml;
        }
    });

    function countUnassigned() {
        const sidebar = document.querySelector('.roster-list[data-group-id="0"]');
        if (!sidebar) return 0;
        return sidebar.querySelectorAll('.roster-chip').length;
    }

    /**
     * Custom modal asking how many to auto-fill, with a number input.
     * Returns the count, or null if cancelled.
     */
    function promptCount({ groupName, max, defaultValue }) {
        return new Promise((resolve) => {
            let el = document.getElementById('auto-fill-prompt-modal');
            if (!el) {
                el = document.createElement('div');
                el.id = 'auto-fill-prompt-modal';
                el.className = 'modal fade';
                el.tabIndex = -1;
                el.innerHTML = `
                    <div class="modal-dialog modal-dialog-centered">
                        <div class="modal-content">
                            <div class="modal-header">
                                <h5 class="modal-title">Auto-asignar pendientes</h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                            </div>
                            <div class="modal-body">
                                <p class="mb-3" data-role="prompt-text"></p>
                                <div class="d-flex align-items-center gap-2">
                                    <button type="button" class="btn btn-outline-secondary btn-sm" data-role="decrement">
                                        <i class="fa-solid fa-minus"></i>
                                    </button>
                                    <input type="number" class="form-control text-center" data-role="count-input"
                                           style="max-width: 100px; font-family: var(--font-mono); font-size: 18px; font-weight: 700;">
                                    <button type="button" class="btn btn-outline-secondary btn-sm" data-role="increment">
                                        <i class="fa-solid fa-plus"></i>
                                    </button>
                                    <span class="text-secondary small ms-2" data-role="max-hint"></span>
                                </div>
                                <small class="text-muted d-block mt-3">
                                    Los jugadores se eligen al azar del pool de pendientes.
                                </small>
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancelar</button>
                                <button type="button" class="btn btn-primary" data-role="confirm">
                                    <i class="fa-solid fa-wand-magic-sparkles me-1"></i> Auto-asignar
                                </button>
                            </div>
                        </div>
                    </div>`;
                document.body.appendChild(el);
            }

            el.querySelector('[data-role="prompt-text"]').innerHTML =
                `¿Cuántos pendientes asignar a <strong>${escape(groupName)}</strong>?`;
            const input = el.querySelector('[data-role="count-input"]');
            input.min = 1;
            input.max = max;
            input.value = defaultValue;
            el.querySelector('[data-role="max-hint"]').textContent = `de ${max} disponibles`;

            const confirmBtn = el.querySelector('[data-role="confirm"]');
            const incBtn = el.querySelector('[data-role="increment"]');
            const decBtn = el.querySelector('[data-role="decrement"]');

            const onInc = () => { input.value = Math.min(max, parseInt(input.value || '0', 10) + 1); };
            const onDec = () => { input.value = Math.max(1, parseInt(input.value || '0', 10) - 1); };
            const onConfirm = () => {
                const v = parseInt(input.value || '0', 10);
                if (v < 1 || v > max) {
                    input.classList.add('is-invalid');
                    return;
                }
                cleanup();
                resolve(v);
                m.hide();
            };
            const onHide = () => { cleanup(); resolve(null); };

            incBtn.addEventListener('click', onInc);
            decBtn.addEventListener('click', onDec);
            confirmBtn.addEventListener('click', onConfirm);
            el.addEventListener('hidden.bs.modal', onHide, { once: true });

            function cleanup() {
                incBtn.removeEventListener('click', onInc);
                decBtn.removeEventListener('click', onDec);
                confirmBtn.removeEventListener('click', onConfirm);
            }

            const m = bootstrap.Modal.getOrCreateInstance(el);
            m.show();
            setTimeout(() => input.focus({ preventScroll: true }), 200);
        });
    }

    // ---- Desktop popover positioning ----
    function positionPanel() {
        if (!isDesktop || !anchorChip) {
            panel.style.removeProperty('top');
            panel.style.removeProperty('left');
            return;
        }

        const rect = anchorChip.getBoundingClientRect();
        const panelW = 340;
        const panelH = Math.min(420, window.innerHeight - 32);
        const margin = 8;

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

    let scrollRaf = null;
    function onScrollOrResize() {
        if (scrollRaf) cancelAnimationFrame(scrollRaf);
        scrollRaf = requestAnimationFrame(positionPanel);
    }
    window.addEventListener('scroll', onScrollOrResize, true);
    window.addEventListener('resize', onScrollOrResize);

    function escape(s) {
        return String(s).replace(/[&<>"']/g, c => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]));
    }
}

