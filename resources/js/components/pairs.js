export function mountPairs() {
    const app = document.getElementById('pairs-app');
    if (!app) return;

    const leagueId = app.dataset.leagueId;
    const url = {
        list: `/leagues/${leagueId}/pairs`,
        one:  (id) => `/leagues/${leagueId}/pairs/${id}`,
    };

    const newBtn = document.getElementById('new-pair-btn');
    const createBtn = document.getElementById('create-pair-btn');
    const list = document.getElementById('pairs-list');

    newBtn?.addEventListener('click', () => {
        // Make sure the two selects aren't on the same player
        const selA = document.getElementById('pair-player-a');
        const selB = document.getElementById('pair-player-b');
        if (selA.options.length >= 2 && selA.value === selB.value) {
            selB.value = selB.options[1].value;
        }
        document.getElementById('pair-label').value = '';
        window.app.modal.open('new-pair-modal');
    });

    createBtn?.addEventListener('click', async () => {
        const a = document.getElementById('pair-player-a').value;
        const b = document.getElementById('pair-player-b').value;
        const label = document.getElementById('pair-label').value.trim();
        if (a === b) return window.app.toast.warn('Selecciona dos jugadores distintos');
        window.app.loading.on(createBtn);
        try {
            const { pair } = await window.app.api.post(url.list, {
                player_a_id: parseInt(a, 10),
                player_b_id: parseInt(b, 10),
                label: label || null,
            });
            window.app.toast.success('Pareja creada');
            window.app.modal.close('new-pair-modal');
            // Simplest: reload to refresh "available players" select + drag-and-drop chips
            window.location.reload();
        } catch (err) {
            window.app.toast.error(err.message);
        } finally {
            window.app.loading.off(createBtn);
        }
    });

    list?.addEventListener('click', async (e) => {
        const row = e.target.closest('.pair-row');
        if (!row) return;
        const pid = row.dataset.pairId;

        if (e.target.closest('.save-pair')) {
            const label = row.querySelector('.pair-label-input').value.trim();
            try {
                await window.app.api.put(url.one(pid), { label: label || null });
                window.app.toast.success('Guardado');
            } catch (err) { window.app.toast.error(err.message); }
        }

        if (e.target.closest('.dissolve-pair')) {
            if (!confirm('¿Disolver esta pareja? Sus jugadores quedarán libres.')) return;
            try {
                await window.app.api.delete(url.one(pid));
                window.location.reload();
            } catch (err) { window.app.toast.error(err.message); }
        }
    });
}