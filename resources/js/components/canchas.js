import Sortable from 'sortablejs';

export function mountCanchas() {
    const app = document.querySelector('.canchas-app');
    if (!app) return;

    const leagueId    = app.dataset.leagueId;
    const groupId     = app.dataset.groupId;
    const jornadaId   = app.dataset.jornadaId;
    const mode        = app.dataset.mode; // 'individual' | 'pairs'
    const assignUrl   = app.dataset.assignUrl;
    const canchaUrlBase = app.dataset.canchaUrlTemplate; // .../canchas
    const max         = mode === 'pairs' ? 2 : 4;

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
                // Reject drops into a full cancha
                onMove: (evt) => {
                    const target = evt.to;
                    if (!target.classList.contains('cancha-roster')) return true;
                    const count = target.querySelectorAll('.roster-chip').length;
                    return count < max;
                },
                onAdd: (evt) => handleMove(evt),
                onSort: () => updateAllStates(),
            });
            updateOneState(list);
        });
    }

    async function handleMove(evt) {
        const chip = evt.item;
        const toList   = evt.to;
        const fromList = evt.from;
        const targetCanchaId = parseInt(toList.dataset.canchaId || '0', 10);

        const payload = (mode === 'pairs')
            ? { pair_id: parseInt(chip.dataset.pairId, 10),     cancha_id: targetCanchaId || null }
            : { player_id: parseInt(chip.dataset.playerId, 10), cancha_id: targetCanchaId || null };

        try {
            await window.app.api.post(assignUrl, payload);
        } catch (err) {
            window.app.toast.error(err.message);
            fromList.insertBefore(chip, fromList.children[evt.oldIndex] || null);
        } finally {
            [toList, fromList].forEach(updateOneState);
        }
    }

    function updateOneState(list) {
        if (list.classList.contains('cancha-pool')) {
            document.querySelector('.pool-count').textContent =
                list.querySelectorAll('.roster-chip').length;
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
            if (!confirm(msg)) return;
            try {
                // Move occupants back to the pool client-side first
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
        if (!confirm('¿Auto-asignar redistribuye TODOS los miembros del grupo en canchas. ¿Continuar?')) return;
        window.app.loading.on(autoBtn);
        try {
            await window.app.api.post(autoBtn.dataset.url, {});
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