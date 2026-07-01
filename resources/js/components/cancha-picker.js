/**
 * Tap-to-assign picker for the cancha roster (jornada show view).
 *
 * Tap a chip → bottom sheet (mobile) or popover (desktop) lists destination canchas
 * + "el pool". If the chosen destination is at capacity, a second screen lets
 * the user pick which player to swap with.
 *
 * Desktop drag-and-drop still works via canchas.js. This is a parallel path.
 */

import { postWithResetGuard } from '../modules/reset-guard.js';

export function mountCanchaPicker() { 
    const app = document.querySelector('.canchas-app');
    if (!app) return;

    const leagueId  = app.dataset.leagueId;
    const groupId   = app.dataset.groupId;
    const jornadaId = app.dataset.jornadaId;
    const mode      = app.dataset.mode;
    const assignUrl = app.dataset.assignUrl;
    const swapUrl   = app.dataset.swapUrl;
    const max       = mode === 'pairs' ? 2 : 4;

    const picker  = document.getElementById('cancha-picker');
    if (!picker) return;
    const panel   = picker.querySelector('.cell-picker-panel');
    const eyebrow = document.getElementById('cancha-picker-eyebrow');
    const title   = document.getElementById('cancha-picker-title');
    const body    = document.getElementById('cancha-picker-body');

    let isDesktop = window.matchMedia('(min-width: 992px)').matches;
    let anchorChip = null;

    // State for two-step swap flow
    let swapState = null; // { sourceChip, targetCancha, targetList }

    window.matchMedia('(min-width: 992px)').addEventListener('change', (e) => {
        isDesktop = e.matches;
        if (picker.classList.contains('is-open')) positionPanel();
    });

    // ---- Tap a chip → open picker ----
    document.addEventListener('click', (e) => {
        // Skip if currently dragging (desktop)
        if (document.body.classList.contains('sortable-dragging')) return;

        const chip = e.target.closest('.roster-chip');
        if (!chip || !app.contains(chip)) return;

        // Don't intercept clicks on inputs/buttons inside the chip (none exist now, but defensively)
        if (e.target.closest('button, input, a, label')) return;

        openPicker(chip);
    });

    // Close handlers (use closest so clicks on inner icons work)
    document.addEventListener('click', (e) => {
        if (e.target.closest('[data-action="close-cancha-picker"]')) {
            closePicker();
        }
    });
    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape' && picker.classList.contains('is-open')) closePicker();
    });

    // Desktop: close on outside click
    document.addEventListener('mousedown', (e) => {
        if (!isDesktop) return;
        if (!picker.classList.contains('is-open')) return;
        if (panel.contains(e.target)) return;
        if (e.target.closest('.roster-chip')) return;
        closePicker();
    });

    function openPicker(chip) {
        anchorChip = chip;
        swapState = null;

        const chipName = chip.querySelector('.chip-name')?.textContent.trim() || '';
        const currentList = chip.closest('.roster-list');
        const currentCanchaId = currentList?.dataset.canchaId || '0';
        const isInCancha = currentCanchaId !== '0';

        eyebrow.textContent = isInCancha ? 'MOVER A' : 'ASIGNAR A CANCHA';
        title.textContent = chipName;

        renderDestinationList(currentCanchaId, isInCancha);

        picker.classList.add('is-open');
        positionPanel();
            maybeShowHint();

    }
    function maybeShowHint() {
    if (localStorage.getItem('pl_cancha_hint_seen')) return;
    const hint = document.getElementById('canchas-hint');
    if (hint) {
        hint.hidden = false;
        setTimeout(() => {
            hint.hidden = true;
            localStorage.setItem('pl_cancha_hint_seen', '1');
        }, 6000);
    }
}


document.addEventListener('click', (e) => {
    if (e.target.closest('[data-action="dismiss-canchas-hint"]')) {
        document.getElementById('canchas-hint').hidden = true;
        localStorage.setItem('pl_cancha_hint_seen', '1');
    }
});


    function closePicker() {
        picker.classList.remove('is-open');
        anchorChip = null;
        swapState = null;
        body.innerHTML = '';
    }

    function renderDestinationList(currentCanchaId, isInCancha) {
        const canchaCards = Array.from(document.querySelectorAll('.cancha-card'));

        if (canchaCards.length === 0) {
            body.innerHTML = `
                <div class="picker-empty">
                    <i class="fa-solid fa-table-cells"></i>
                    No hay canchas todavía. Crea una primero.
                </div>`;
            return;
        }

        const canchaRows = canchaCards.map(card => {
            const cid    = card.dataset.canchaId;
            const label  = card.querySelector('.cancha-label')?.value.trim() || `Cancha ${cid}`;
            const list   = card.querySelector('.roster-list');
            const count  = list ? list.querySelectorAll('.roster-chip').length : 0;
            const isCurrent = String(cid) === String(currentCanchaId);
            const isFull = count >= max;

            const meta = isFull
                ? `Llena (${count}/${max}) · toca para intercambiar`
                : `${count}/${max}`;

            return `
                <button type="button" class="picker-action"
                        data-cancha-id="${cid}"
                        ${isCurrent ? 'disabled' : ''}>
                    <span class="picker-action-icon"><i class="fa-solid fa-table-cells"></i></span>
                    <span class="picker-action-text">
                        <strong>${escape(label)}</strong>
                        <small>${meta}${isCurrent ? ' · Cancha actual' : ''}</small>
                    </span>
                    ${isCurrent
                        ? '<i class="fa-solid fa-check text-success"></i>'
                        : '<i class="fa-solid fa-chevron-right text-muted"></i>'}
                </button>`;
        }).join('');

        const poolRow = isInCancha ? `
            <div class="picker-section-label mt-3">Otras opciones</div>
            <button type="button" class="picker-action is-danger" data-cancha-id="0">
                <span class="picker-action-icon"><i class="fa-solid fa-arrow-up-from-bracket"></i></span>
                <span class="picker-action-text">
                    <strong>Sacar de la cancha</strong>
                    <small>Volver al pool de pendientes</small>
                </span>
                <i class="fa-solid fa-chevron-right text-muted"></i>
            </button>` : '';

        body.innerHTML = `
            <div class="picker-section-label">Canchas</div>
            ${canchaRows}
            ${poolRow}
        `;

        wireDestinations();
    }

    function wireDestinations() {
        body.querySelectorAll('[data-cancha-id]').forEach(btn => {
            btn.addEventListener('click', async () => {
                const targetCanchaId = btn.dataset.canchaId;
                const targetList = targetCanchaId === '0'
                    ? document.querySelector('.cancha-pool')
                    : document.querySelector(`.cancha-roster[data-cancha-id="${targetCanchaId}"]`);

                if (!targetList) return;

                const targetChipCount = targetList.querySelectorAll('.roster-chip').length;
                const isCancha = targetCanchaId !== '0';
                const isFull = isCancha && targetChipCount >= max;

                if (isFull) {
                    // Show swap picker — which chip to swap with?
                    swapState = { sourceChip: anchorChip, targetList, targetCanchaId };
                    renderSwapPicker(targetList);
                    return;
                }

                // Normal assign
                await commitAssign(anchorChip, targetCanchaId, btn);
            });
        });
    }

    function renderSwapPicker(targetList) {
        const targetCanchaCard = targetList.closest('.cancha-card');
        const targetLabel = targetCanchaCard?.querySelector('.cancha-label')?.value.trim() || 'la cancha';

        eyebrow.textContent = 'INTERCAMBIAR EN';
        title.textContent = targetLabel;

        const members = Array.from(targetList.querySelectorAll('.roster-chip'));
        const memberRows = members.map(chip => {
            const name = chip.querySelector('.chip-name')?.textContent.trim() || '?';
            const initial = name.substring(0, 1).toUpperCase();
            const dataAttr = mode === 'pairs' ? 'data-pair-id' : 'data-player-id';
            const id = mode === 'pairs' ? chip.dataset.pairId : chip.dataset.playerId;
            return `
                <button type="button" class="cancha-picker-member" ${dataAttr}="${id}">
                    <span class="cancha-picker-member-avatar">${escape(initial)}</span>
                    <span class="cancha-picker-member-info">
                        <div class="cancha-picker-member-name">${escape(name)}</div>
                        <div class="cancha-picker-member-sub">Tocar para intercambiar</div>
                    </span>
                    <i class="fa-solid fa-arrow-right-arrow-left text-muted"></i>
                </button>`;
        }).join('');

        const sourceName = swapState.sourceChip.querySelector('.chip-name')?.textContent.trim() || '';

        body.innerHTML = `
            <button type="button" class="cancha-picker-back-btn" data-action="back-to-destinations">
                <i class="fa-solid fa-arrow-left"></i> Cambiar destino
            </button>
            <div class="alert alert-info py-2 px-3 mb-3 small">
                <i class="fa-solid fa-circle-info me-1"></i>
                <strong>${escape(sourceName)}</strong> entrará a <strong>${escape(targetLabel)}</strong>.
                Elige quién sale.
            </div>
            ${memberRows}
        `;

        body.querySelector('[data-action="back-to-destinations"]')?.addEventListener('click', () => {
            const currentList = swapState.sourceChip.closest('.roster-list');
            const currentCanchaId = currentList?.dataset.canchaId || '0';
            renderDestinationList(currentCanchaId, currentCanchaId !== '0');
        });

        body.querySelectorAll('.cancha-picker-member').forEach(btn => {
            btn.addEventListener('click', async () => {
                const victimId = mode === 'pairs'
                    ? parseInt(btn.dataset.pairId, 10)
                    : parseInt(btn.dataset.playerId, 10);
                await commitSwap(swapState.sourceChip, victimId, btn);
            });
        });
    }

    async function commitAssign(chip, targetCanchaId, btn) {
        btn.disabled = true;
        btn.innerHTML = `<span class="spinner-border spinner-border-sm me-2"></span>Asignando…`;

        const payload = (mode === 'pairs')
            ? { pair_id: parseInt(chip.dataset.pairId, 10),     cancha_id: targetCanchaId === '0' ? null : parseInt(targetCanchaId, 10) }
            : { player_id: parseInt(chip.dataset.playerId, 10), cancha_id: targetCanchaId === '0' ? null : parseInt(targetCanchaId, 10) };

        try {
            const result = await postWithResetGuard(assignUrl, payload);
            if (result === null) {
                // User cancelled the reset confirmation — restore the button, keep picker open
                btn.disabled = false;
                btn.innerHTML = originalBtnHtml(targetCanchaId);
                return;
            }
            closePicker();
            window.location.reload();
        } catch (err) {
            window.app.toast.error(err.message);
            btn.disabled = false;
            btn.innerHTML = originalBtnHtml(targetCanchaId);
        }
    }

    async function commitSwap(sourceChip, victimId, btn) {
        btn.disabled = true;
        btn.innerHTML = `<span class="spinner-border spinner-border-sm me-2"></span>Intercambiando…`;

        const payload = (mode === 'pairs')
            ? {
                source_pair_id: parseInt(sourceChip.dataset.pairId, 10),
                target_pair_id: victimId,
            }
            : {
                source_player_id: parseInt(sourceChip.dataset.playerId, 10),
                target_player_id: victimId,
            };

        try {
            const result = await postWithResetGuard(swapUrl, payload);
            if (result === null) {
                // Cancelled — reopen the destination list so the user can pick again
                btn.disabled = false;
                const currentList = swapState?.sourceChip.closest('.roster-list');
                const currentCanchaId = currentList?.dataset.canchaId || '0';
                renderDestinationList(currentCanchaId, currentCanchaId !== '0');
                return;
            }
            closePicker();
            window.location.reload();
        } catch (err) {
            window.app.toast.error(err.message);
            btn.disabled = false;
        }
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
        const panelH = Math.min(480, window.innerHeight - 32);
        const margin = 8;

        let left = rect.right + margin;
        if (left + panelW + margin > window.innerWidth) left = rect.left - panelW - margin;
        if (left < margin) left = Math.max(margin, rect.left);

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

    function originalBtnHtml(targetCanchaId) {
        // Rebuild the button's inner content after a cancelled action.
        if (targetCanchaId === '0') {
            return `<span class="picker-action-icon"><i class="fa-solid fa-arrow-up-from-bracket"></i></span>
                    <span class="picker-action-text"><strong>Sacar de la cancha</strong><small>Volver al pool de pendientes</small></span>
                    <i class="fa-solid fa-chevron-right text-muted"></i>`;
        }
        const card = document.querySelector(`.cancha-card[data-cancha-id="${targetCanchaId}"]`);
        const label = card?.querySelector('.cancha-label')?.value.trim() || `Cancha ${targetCanchaId}`;
        const list = card?.querySelector('.roster-list');
        const count = list ? list.querySelectorAll('.roster-chip').length : 0;
        return `<span class="picker-action-icon"><i class="fa-solid fa-table-cells"></i></span>
                <span class="picker-action-text"><strong>${escape(label)}</strong><small>${count}/${max}</small></span>
                <i class="fa-solid fa-chevron-right text-muted"></i>`;
    }

    if (window.matchMedia('(hover: none)').matches && !localStorage.getItem('pl_cancha_hint_seen')) {
    const hint = document.getElementById('canchas-hint');
    if (hint) {
        hint.hidden = false;
        setTimeout(() => { hint.hidden = true; localStorage.setItem('pl_cancha_hint_seen', '1'); }, 6000);
    }
}
}