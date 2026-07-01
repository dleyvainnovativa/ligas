import Sortable from 'sortablejs';
import { postWithResetGuard } from '../modules/reset-guard.js';

export function mountCanchas() {
    const app = document.querySelector('.canchas-app');
    if (!app) return;

    const leagueId      = app.dataset.leagueId;
    const groupId       = app.dataset.groupId;
    const jornadaId     = app.dataset.jornadaId;
    const mode          = app.dataset.mode; // 'individual' | 'pairs'
    const assignUrl     = app.dataset.assignUrl;
    const swapUrl       = app.dataset.swapUrl;
    const canchaUrlBase = app.dataset.canchaUrlTemplate;
    const max           = mode === 'pairs' ? 2 : 4;

    function mountSortables() {
        document.querySelectorAll('.canchas-app .roster-list').forEach((list) => {
            if (list.dataset.sortableMounted) return;
            list.dataset.sortableMounted = '1';

            Sortable.create(list, {
    group: 'cancha-roster',
    animation: 150,
    ghostClass: 'sortable-ghost',
    chosenClass: 'sortable-chosen',
    dragClass: 'sortable-drag',
    handle: '.roster-chip',
    emptyInsertThreshold: 20,
    onChoose:   () => document.body.classList.add('sortable-dragging'),
    onUnchoose: () => document.body.classList.remove('sortable-dragging'),
    onAdd:      (evt) => handleMove(evt),
    onSort:     () => updateAllStates(),
});
            updateOneState(list);
        });
    }

    async function handleMove(evt) {
        const chip = evt.item;
        const toList   = evt.to;
        const fromList = evt.from;
        const targetCanchaId = parseInt(toList.dataset.canchaId || '0', 10);

        const targetChips = toList.querySelectorAll('.roster-chip');
        const isTargetCancha = targetCanchaId !== 0;
        const isOverCapacity = isTargetCancha && targetChips.length > max;

        if (isOverCapacity) {
            const ourIndex = Array.from(targetChips).indexOf(chip);
            let victim = targetChips[ourIndex + 1] || targetChips[ourIndex - 1];

            if (!victim) {
                window.app.toast.error('No se pudo intercambiar.');
                fromList.insertBefore(chip, fromList.children[evt.oldIndex] || null);
                [toList, fromList].forEach(updateOneState);
                return;
            }

            await doSwap(chip, victim, fromList, toList, evt);
            return;
        }

        // Normal assign / unassign
        const payload = (mode === 'pairs')
            ? { pair_id: parseInt(chip.dataset.pairId, 10),     cancha_id: targetCanchaId || null }
            : { player_id: parseInt(chip.dataset.playerId, 10), cancha_id: targetCanchaId || null };

        try {
            const result = await postWithResetGuard(assignUrl, payload);
            if (result === null) {
                // Cancelled — roll the chip back to where it came from
                fromList.insertBefore(chip, fromList.children[evt.oldIndex] || null);
            }
        } catch (err) {
            window.app.toast.error(err.message);
            fromList.insertBefore(chip, fromList.children[evt.oldIndex] || null);
        } finally {
            [toList, fromList].forEach(updateOneState);
        }
    }

    async function doSwap(droppedChip, victimChip, fromList, toList, evt) {
        const payload = (mode === 'pairs')
            ? {
                source_pair_id: parseInt(droppedChip.dataset.pairId, 10),
                target_pair_id: parseInt(victimChip.dataset.pairId, 10),
            }
            : {
                source_player_id: parseInt(droppedChip.dataset.playerId, 10),
                target_player_id: parseInt(victimChip.dataset.playerId, 10),
            };

        try {
            const result = await postWithResetGuard(swapUrl, payload);
            if (result === null) {
                // Cancelled — roll dropped chip back, leave victim in place
                fromList.insertBefore(droppedChip, fromList.children[evt.oldIndex] || null);
            } else {
                // Move victim chip to source list (the swap mirror)
                fromList.appendChild(victimChip);
                window.app.toast.success('Intercambio realizado');
            }
        } catch (err) {
            window.app.toast.error(err.message);
            fromList.insertBefore(droppedChip, fromList.children[evt.oldIndex] || null);
        } finally {
            [toList, fromList].forEach(updateOneState);
        }
    }

    function updateOneState(list) {
        if (list.classList.contains('cancha-pool')) {
            const el = document.querySelector('.pool-count');
            if (el) el.textContent = list.querySelectorAll('.roster-chip').length;
            return;
        }
        const card = list.closest('.cancha-card');
        if (!card) return;
        const count = list.querySelectorAll('.roster-chip').length;
        const cap = parseInt(card.dataset.max, 10);
        card.querySelector('.cancha-count').textContent = `${count}/${cap}`;
        card.classList.toggle('is-full', count >= cap);
        list.classList.toggle('is-full', count >= cap);
    }

    function updateAllStates() {
        document.querySelectorAll('.canchas-app .roster-list').forEach(updateOneState);
    }

    // ---- Add cancha ----
    const addBtn = document.getElementById('add-cancha-btn');
    addBtn?.addEventListener('click', async () => {
        try {
            const { cancha } = await window.app.api.post(addBtn.dataset.url, {});
            document.getElementById('canchas-empty')?.remove();
            document.getElementById('canchas-container').insertAdjacentHTML('beforeend', canchaCardHtml(cancha));
            mountSortables();
            window.app.toast.success('Cancha creada');
        } catch (err) {
            window.app.toast.error(err.message);
        }
    });

    // ---- Save / delete cancha ----
    document.getElementById('canchas-container')?.addEventListener('click', async (e) => {
        const card = e.target.closest('.cancha-card');
        if (!card) return;
        const cid = card.dataset.canchaId;

        if (e.target.closest('.save-cancha')) {
            const label = card.querySelector('.cancha-label').value.trim();
            if (!label) return window.app.toast.warn('El nombre es requerido');
            try {
                await window.app.api.put(`${canchaUrlBase}/${cid}`, { label });
                window.app.toast.success('Guardado');
            } catch (err) { window.app.toast.error(err.message); }
        }

        if (e.target.closest('.delete-cancha')) {
            const chipsInside = card.querySelectorAll('.roster-chip').length;
            const msg = chipsInside > 0
                ? `Esta cancha tiene ${chipsInside} ${mode === 'pairs' ? 'pareja(s)' : 'jugador(es)'}. ¿Eliminar de todas formas? Volverán al pool.`
                : '¿Eliminar esta cancha?';
            const ok = await window.app.modal.confirm({
                title: 'Eliminar cancha',
                body: msg,
                confirmText: 'Eliminar',
                danger: true,
            });
            if (!ok) return;

            try {
                const pool = document.querySelector('.cancha-pool');
                card.querySelectorAll('.roster-chip').forEach(chip => pool.appendChild(chip));
                await window.app.api.delete(`${canchaUrlBase}/${cid}`);
                card.closest('.cancha-card-wrapper').remove();
                updateAllStates();
            } catch (err) { window.app.toast.error(err.message); }
        }
    });

    // ---- Auto-fill ----
    const autoBtn = document.getElementById('auto-fill-btn');
    autoBtn?.addEventListener('click', async () => {
        const ok = await window.app.modal.confirm({
            title: 'Auto-asignar',
            body: 'Auto-asignar redistribuye TODOS los miembros del grupo en canchas. ¿Continuar?',
            confirmText: 'Auto-asignar',
        });
        if (!ok) return;
        window.app.loading.on(autoBtn);
        try {
            const result = await postWithResetGuard(autoBtn.dataset.url, {});
            if (result === null) {
                // User cancelled the second (schedule-reset) confirmation
                window.app.loading.off(autoBtn);
                return;
            }
            window.location.reload();
        } catch (err) {
            window.app.toast.error(err.message);
            window.app.loading.off(autoBtn);
        }
    });

    function canchaCardHtml(c) {
        const cap = max;
        return `
        <div class="col-md-6 cancha-card-wrapper">
            <div class="cancha-card card-soft" data-cancha-id="${c.id}" data-max="${cap}">
                <div class="cancha-header">
                    <div class="d-flex align-items-center gap-2 flex-grow-1 min-w-0">
                        <i class="fa-solid fa-table-cells text-secondary"></i>
                        <input type="text" class="form-control form-control-sm cancha-label" value="${escape(c.label)}">
                    </div>
                    <span class="badge text-bg-secondary cancha-count">0/${cap}</span>
                    <button class="btn btn-sm btn-outline-secondary save-cancha"><i class="fa-solid fa-floppy-disk"></i></button>
                    <button class="btn btn-sm btn-outline-danger delete-cancha"><i class="fa-solid fa-trash"></i></button>
                </div>
                <div class="roster-list cancha-roster" data-cancha-id="${c.id}"></div>
            </div>
        </div>`;
    }

    function escape(s) {
        return String(s).replace(/[&<>"']/g, c => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]));
    }

    mountSortables();
}