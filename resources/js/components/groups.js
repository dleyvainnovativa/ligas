import Sortable from 'sortablejs';

export function mountGroups() {
    const app = document.querySelector('.groups-app');
    if (!app) return;

    const leagueId = app.dataset.leagueId;
    const mode     = app.dataset.mode; // 'individual' | 'pairs'

    const url = {
        groups:     `/leagues/${leagueId}/groups`,
        group:      (id) => `/leagues/${leagueId}/groups/${id}`,
        movePlayer: `/leagues/${leagueId}/groups/move-player`,
        movePair:   `/leagues/${leagueId}/groups/move-pair`,
    };

    // ---- Mount Sortable on every roster-list ----
    function mountSortables() {
        document.querySelectorAll('.roster-list').forEach((list) => {
            if (list.dataset.sortableMounted) return;
            list.dataset.sortableMounted = '1';
            Sortable.create(list, {
    group: 'roster',
    animation: 150,
    ghostClass: 'sortable-ghost',
    chosenClass: 'sortable-chosen',
    dragClass:   'sortable-drag',
    handle: '.roster-chip',
    onChoose:   () => document.body.classList.add('sortable-dragging'),
    onUnchoose: () => document.body.classList.remove('sortable-dragging'),
    onAdd:      (evt) => handleMove(evt),
    onSort:     () => updateAllCounts(),
});
            updateEmptyHint(list);
        });
    }

    async function handleMove(evt) {
        const chip = evt.item;
        const toList   = evt.to;
        const fromList = evt.from;
        const targetGroupId = parseInt(toList.dataset.groupId || '0', 10);

        try {
            if (mode === 'pairs') {
                const pairId = parseInt(chip.dataset.pairId, 10);
                await window.app.api.post(url.movePair, {
                    pair_id:  pairId,
                    group_id: targetGroupId || null,
                });
            } else {
                const playerId = parseInt(chip.dataset.playerId, 10);
                await window.app.api.post(url.movePlayer, {
                    player_id: playerId,
                    group_id:  targetGroupId || null,
                });
            }
        } catch (err) {
            // Revert on failure
            window.app.toast.error(err.message);
            fromList.insertBefore(chip, fromList.children[evt.oldIndex] || null);
        } finally {
            [toList, fromList].forEach(updateEmptyHint);
            updateAllCounts();
        }
    }

    function updateEmptyHint(list) {
        if (list.querySelector('.roster-chip')) list.classList.remove('is-empty');
        else list.classList.add('is-empty');
    }

    function updateAllCounts() {
        document.querySelectorAll('.group-card').forEach((card) => {
            const list = card.querySelector('.roster-list');
            const count = list.querySelectorAll('.roster-chip').length;
            card.querySelector('.group-count').textContent = count;
        });
        const unassigned = document.querySelector('.roster-list[data-group-id="0"]');
        if (unassigned) {
            const c = unassigned.querySelectorAll('.roster-chip').length;
            document.querySelector('.unassigned-count').textContent = c;
        }
    }

    // ---- Add group ----
    const addBtn = document.getElementById('add-group-btn');
    addBtn?.addEventListener('click', async () => {
        try {
            const name = `División ${String.fromCharCode(64 + (document.querySelectorAll('.group-card').length + 1))}`;
            const { group } = await window.app.api.post(url.groups, { name });
            document.getElementById('groups-empty')?.remove();
            document.getElementById('groups-container').insertAdjacentHTML('beforeend', groupCardHtml(group));
            mountSortables();
            window.app.toast.success('Grupo creado');
        } catch (err) {
            window.app.toast.error(err.message);
        }
    });

    // ---- Save / delete group ----
    document.getElementById('groups-container')?.addEventListener('click', async (e) => {
        const card = e.target.closest('.group-card');
        if (!card) return;
        const gid = card.dataset.groupId;

        if (e.target.closest('.save-group')) {
            const name = card.querySelector('.group-name').value.trim();
            if (!name) return window.app.toast.warn('El nombre es requerido');
            try {
                await window.app.api.put(url.group(gid), { name });
                window.app.toast.success('Grupo guardado');
            } catch (err) { window.app.toast.error(err.message); }
        }

        if (e.target.closest('.delete-group')) {
            const chips = card.querySelectorAll('.roster-chip').length;
            const confirmMsg = chips > 0
                ? `Este grupo tiene ${chips} miembro(s). ¿Eliminar de todas formas? Los miembros volverán al pool sin grupo.`
                : '¿Eliminar este grupo?';
            if (!confirm(confirmMsg)) return;
            try {
                // Move all members to unassigned first (so they don't disappear when group is deleted)
                const unassigned = document.querySelector('.roster-list[data-group-id="0"]');
                card.querySelectorAll('.roster-chip').forEach(chip => unassigned?.appendChild(chip));
                await window.app.api.delete(url.group(gid));
                card.remove();
                updateAllCounts();
                mountSortables();
            } catch (err) { window.app.toast.error(err.message); }
        }
    });

    function groupCardHtml(g) {
        return `
        <div class="group-card card-soft" data-group-id="${g.id}">
            <div class="group-card-header">
                <div class="flex-grow-1 min-w-0 d-flex align-items-center gap-2">
                    <i class="fa-solid fa-layer-group text-secondary"></i>
                    <input type="text" class="form-control form-control-sm group-name" value="${escape(g.name)}">
                </div>
                <span class="badge text-bg-secondary group-count">0</span>
                <button class="btn btn-sm btn-outline-secondary save-group"><i class="fa-solid fa-floppy-disk"></i></button>
                <button class="btn btn-sm btn-outline-danger delete-group"><i class="fa-solid fa-trash"></i></button>
            </div>
            <div class="roster-list group-roster" data-group-id="${g.id}"></div>
        </div>`;
    }

    function escape(s) {
        return String(s).replace(/[&<>"']/g, c => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]));
    }

    mountSortables();
}