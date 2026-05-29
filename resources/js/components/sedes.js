export function mountSedes() {
    const app = document.getElementById('sedes-app');
    if (!app) return;

    const leagueId = app.dataset.leagueId;
    const list = document.getElementById('sedes-list');

    const urls = {
        sedes:  `/leagues/${leagueId}/sedes`,
        sede:   (sid)         => `/leagues/${leagueId}/sedes/${sid}`,
        pistas: (sid)         => `/leagues/${leagueId}/sedes/${sid}/pistas`,
        pista:  (sid, pid)    => `/leagues/${leagueId}/sedes/${sid}/pistas/${pid}`,
    };

    // ---- Add sede ----
    document.getElementById('add-sede-btn').addEventListener('click', async () => {
        try {
            const { sede } = await window.app.api.post(urls.sedes, { name: 'Nueva sede' });
            list.insertAdjacentHTML('beforeend', renderSede(sede));
            window.app.toast.success('Sede creada');
        } catch (e) {
            window.app.toast.error(e.message);
        }
    });

    // ---- Delegated events on the list ----
    list.addEventListener('click', async (e) => {
        const sedeRow = e.target.closest('.sede-row');
        if (!sedeRow) return;
        const sedeId = sedeRow.dataset.sedeId;

        // Save sede
        if (e.target.closest('.save-sede')) {
            const name = sedeRow.querySelector('.sede-name').value.trim();
            const address = sedeRow.querySelector('.sede-address').value.trim();
            if (!name) return window.app.toast.warn('El nombre es requerido');
            try {
                await window.app.api.put(urls.sede(sedeId), { name, address });
                window.app.toast.success('Sede guardada');
            } catch (err) { window.app.toast.error(err.message); }
        }

        // Delete sede
        if (e.target.closest('.delete-sede')) {
            if (!confirm('¿Eliminar esta sede y todas sus pistas?')) return;
            try {
                await window.app.api.delete(urls.sede(sedeId));
                sedeRow.remove();
                window.app.toast.success('Sede eliminada');
            } catch (err) { window.app.toast.error(err.message); }
        }

        // Add pista
        if (e.target.closest('.add-pista')) {
            const input = sedeRow.querySelector('.new-pista-name');
            const name = input.value.trim();
            if (!name) return window.app.toast.warn('Nombre de pista requerido');
            try {
                const { pista } = await window.app.api.post(urls.pistas(sedeId), { name });
                const pistasList = sedeRow.querySelector('.pistas-list');
                pistasList.insertAdjacentHTML('afterbegin', renderPista(pista));
                input.value = '';
            } catch (err) { window.app.toast.error(err.message); }
        }

        // Save pista
        if (e.target.closest('.save-pista')) {
            const pistaRow = e.target.closest('.pista-row');
            const pistaId = pistaRow.dataset.pistaId;
            const name = pistaRow.querySelector('.pista-name').value.trim();
            if (!name) return window.app.toast.warn('El nombre es requerido');
            try {
                await window.app.api.put(urls.pista(sedeId, pistaId), { name });
                window.app.toast.success('Pista guardada');
            } catch (err) { window.app.toast.error(err.message); }
        }

        // Delete pista
        if (e.target.closest('.delete-pista')) {
            const pistaRow = e.target.closest('.pista-row');
            const pistaId = pistaRow.dataset.pistaId;
            if (!confirm('¿Eliminar esta pista?')) return;
            try {
                await window.app.api.delete(urls.pista(sedeId, pistaId));
                pistaRow.remove();
            } catch (err) { window.app.toast.error(err.message); }
        }
    });

    function renderSede(s) {
        return `
            <div class="sede-row" data-sede-id="${s.id}">
                <div class="d-flex align-items-center gap-2 mb-2">
                    <input type="text" class="form-control form-control-sm sede-name" value="${escape(s.name)}">
                    <input type="text" class="form-control form-control-sm sede-address" placeholder="Dirección (opcional)" value="${escape(s.address || '')}">
                    <button class="btn btn-sm btn-outline-secondary save-sede"><i class="fa-solid fa-floppy-disk"></i></button>
                    <button class="btn btn-sm btn-outline-danger delete-sede"><i class="fa-solid fa-trash"></i></button>
                </div>
                <div class="pistas-list ms-3 mb-3">
                    <div class="d-flex gap-2 mt-1">
                        <input type="text" class="form-control form-control-sm new-pista-name" placeholder="Nueva pista…" style="max-width:240px;">
                        <button class="btn btn-sm btn-outline-primary add-pista">
                            <i class="fa-solid fa-plus me-1"></i> Agregar pista
                        </button>
                    </div>
                </div>
            </div>`;
    }

    function renderPista(p) {
        return `
            <div class="pista-row d-flex align-items-center gap-2 mb-1" data-pista-id="${p.id}">
                <i class="fa-solid fa-square-parking text-secondary"></i>
                <input type="text" class="form-control form-control-sm pista-name" style="max-width:240px;" value="${escape(p.name)}">
                <button class="btn btn-sm btn-link save-pista p-0"><i class="fa-solid fa-check text-success"></i></button>
                <button class="btn btn-sm btn-link delete-pista p-0 ms-1"><i class="fa-solid fa-xmark text-danger"></i></button>
            </div>`;
    }

    function escape(s) {
        return String(s).replace(/[&<>"']/g, c => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]));
    }
}