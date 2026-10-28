export function mountPairsRegistration() {
    const app = document.getElementById('pairs-reg-app');
    if (!app) return;

    const leagueId = app.dataset.leagueId;
    const url = {
        withPlayers: `/leagues/${leagueId}/pairs/with-players`,
        list:        `/leagues/${leagueId}/pairs`,
        one:         (id) => `/leagues/${leagueId}/pairs/${id}`,
    };

    const list = document.getElementById('pairs-list');

    // ---- Primary: register a whole pair (two players at once) ----
    document.getElementById('add-pair-full-btn')?.addEventListener('click', () => {
        document.getElementById('new-pair-full-form')?.reset();
        window.app.modal.open('new-pair-full-modal');
    });

    document.getElementById('new-pair-full-form')?.addEventListener('submit', async (e) => {
        e.preventDefault();
        const btn = document.getElementById('create-pair-full-btn');
        const fd = window.app.serializeForm(e.target);

        const block = (k) => ({
            full_name:      (fd[`${k}_full_name`] || '').trim(),
            email:          (fd[`${k}_email`] || '').trim() || null,
            phone:          (fd[`${k}_phone`] || '').trim() || null,
            paid_amount:    fd[`${k}_paid_amount`] || 0,
            payment_status: fd[`${k}_payment_status`] || null,
        });

        const payload = { a: block('a'), b: block('b'), label: (fd.label || '').trim() || null };

        window.app.loading.on(btn);
        try {
            const { message } = await window.app.api.post(url.withPlayers, payload);
            window.app.toast.success(message || 'Pareja registrada');
            window.app.modal.close('new-pair-full-modal');
            setTimeout(() => window.location.reload(), 500);
        } catch (err) {
            window.app.toast.error(err.message);
            window.app.loading.off(btn);
        }
    });

    // ---- Secondary: pair two already-registered players ----
    document.getElementById('pair-existing-btn')?.addEventListener('click', () => {
        const a = document.getElementById('pair-existing-a');
        const b = document.getElementById('pair-existing-b');
        if (a && b && a.options.length >= 2 && a.value === b.value) {
            b.value = b.options[1].value;
        }
        window.app.modal.open('pair-existing-modal');
    });

    document.getElementById('create-pair-existing-btn')?.addEventListener('click', async (e) => {
        const a = document.getElementById('pair-existing-a').value;
        const b = document.getElementById('pair-existing-b').value;
        const label = document.getElementById('pair-existing-label').value.trim();
        if (a === b) return window.app.toast.warn('Selecciona dos jugadores distintos');

        window.app.loading.on(e.target);
        try {
            await window.app.api.post(url.list, {
                player_a_id: parseInt(a, 10),
                player_b_id: parseInt(b, 10),
                label: label || null,
            });
            window.app.toast.success('Pareja creada');
            window.app.modal.close('pair-existing-modal');
            setTimeout(() => window.location.reload(), 500);
        } catch (err) {
            window.app.toast.error(err.message);
            window.app.loading.off(e.target);
        }
    });

    // ---- Row actions: rename / dissolve ----
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
