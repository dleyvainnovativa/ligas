export function mountPlayers() {
    const app = document.getElementById('players-app');
    if (!app) return;

    const leagueId   = app.dataset.leagueId;
    const leagueCost = parseFloat(app.dataset.leagueCost || '0');
    const tbodyDesktop = document.getElementById('players-tbody-desktop');
    const tbodyMobile  = document.getElementById('players-tbody-mobile');
    const search      = document.getElementById('players-search');
    const countEl     = document.getElementById('players-count');

    const url = {
        list:    `/leagues/${leagueId}/players`,
        one:     (id) => `/leagues/${leagueId}/players/${id}`,
        preview: `/leagues/${leagueId}/players/import/preview`,
        import:  `/leagues/${leagueId}/players/import`,
    };

    // ---- Add player ----
    document.getElementById('add-player-btn').addEventListener('click', async () => {
        try {
            const { player } = await window.app.api.post(url.list, {
                full_name: 'Nuevo jugador',
                paid_amount: 0,
            });
            removeEmptyStates();
            if (tbodyDesktop) tbodyDesktop.insertAdjacentHTML('afterbegin', desktopRowHtml(player));
            if (tbodyMobile)  tbodyMobile.insertAdjacentHTML('afterbegin', mobileRowHtml(player));
            recountPlayers();
            window.app.toast.success('Jugador agregado');
        } catch (err) {
            window.app.toast.error(err.message);
        }
    });

    // ---- Delegated row actions on both containers ----
    [tbodyDesktop, tbodyMobile].forEach((container) => {
        if (!container) return;

        container.addEventListener('click', async (e) => {
            const row = e.target.closest('.player-row');
            if (!row) return;
            const pid = row.dataset.playerId;

            if (e.target.closest('.save-player')) {
                await savePlayer(pid, row);
            }
            if (e.target.closest('.delete-player')) {
                const ok = await window.app.modal.confirm({
    title: 'Eliminar jugador',
    body: '¿Estás seguro? Esta acción no se puede deshacer.',
    confirmText: 'Eliminar',
    danger: true,
});
if (!ok) return;
                try {
                    await window.app.api.delete(url.one(pid));
                    // Remove from both layouts
                    document.querySelectorAll(`.player-row[data-player-id="${pid}"]`).forEach(r => r.remove());
                    recountPlayers();
                    maybeShowEmptyStates();
                } catch (err) { window.app.toast.error(err.message); }
            }
        });

        // Live status derivation + mirror across layouts
        container.addEventListener('input', (e) => {
            const row = e.target.closest('.player-row');
            if (!row) return;
            const pid = row.dataset.playerId;

            // Derive status from paid_amount
            if (e.target.classList.contains('field-paid')) {
                const paid = parseFloat(e.target.value || '0');
                const select = row.querySelector('.field-status');
                if (paid <= 0)                                  select.value = 'unpaid';
                else if (leagueCost > 0 && paid >= leagueCost) select.value = 'paid';
                else                                            select.value = 'partial';
                syncStatusDot(pid, select.value);
                mirrorField(pid, row, 'field-paid', e.target.value);
                mirrorField(pid, row, 'field-status', select.value);
                return;
            }

            // Status dot stays in sync with manual select changes
            if (e.target.classList.contains('field-status')) {
                syncStatusDot(pid, e.target.value);
            }

            // Keep both layouts mirrored in case the user resizes mid-edit
            for (const cls of ['field-name','field-email','field-phone','field-status']) {
                if (e.target.classList.contains(cls)) {
                    mirrorField(pid, row, cls, e.target.value);
                }
            }
        });
    });

    async function savePlayer(pid, row) {
        const data = {
            full_name:      row.querySelector('.field-name').value.trim(),
            email:          row.querySelector('.field-email').value.trim() || null,
            phone:          row.querySelector('.field-phone').value.trim() || null,
            paid_amount:    parseFloat(row.querySelector('.field-paid').value || '0'),
            payment_status: row.querySelector('.field-status').value,
        };
        if (!data.full_name) return window.app.toast.warn('Nombre requerido');
        try {
            await window.app.api.put(url.one(pid), data);
            syncStatusDot(pid, data.payment_status);
            window.app.toast.success('Jugador guardado');
        } catch (err) {
            window.app.toast.error(err.message);
        }
    }

    function syncStatusDot(pid, status) {
        const dot = document.querySelector(`.player-row[data-player-id="${pid}"] .player-status-dot`);
        if (!dot) return;
        dot.className = `player-status-dot status-${status}`;
        dot.title = status;
    }

    function mirrorField(pid, sourceRow, cls, value) {
        document.querySelectorAll(`.player-row[data-player-id="${pid}"] .${cls}`).forEach((el) => {
            if (el !== sourceRow.querySelector(`.${cls}`)) el.value = value;
        });
    }

    // ---- Search ----
    if (search) {
        search.addEventListener('input', () => {
            const q = search.value.trim().toLowerCase();
            document.querySelectorAll('.player-row').forEach((r) => {
                const name  = (r.querySelector('.field-name')?.value || '').toLowerCase();
                const email = (r.querySelector('.field-email')?.value || '').toLowerCase();
                r.style.display = (!q || name.includes(q) || email.includes(q)) ? '' : 'none';
            });
        });
    }

    // ---- CSV import ----
    const importBtn = document.getElementById('import-csv-btn');
    const fileInput = document.getElementById('csv-file-input');
    const previewBox = document.getElementById('csv-preview');
    const previewBody = document.getElementById('csv-preview-tbody');
    const validEl = document.getElementById('csv-valid');
    const invalidEl = document.getElementById('csv-invalid');
    const importConfirmBtn = document.getElementById('csv-import-btn');

    let currentFile = null;

    importBtn.addEventListener('click', () => {
        fileInput.value = '';
        previewBox.classList.add('d-none');
        importConfirmBtn.disabled = true;
        currentFile = null;
        window.app.modal.open('import-csv-modal');
    });

    fileInput.addEventListener('change', async (e) => {
        const file = e.target.files[0];
        if (!file) return;
        currentFile = file;
        const fd = new FormData();
        fd.append('file', file);
        try {
            const data = await window.app.api.post(url.preview, fd);
            validEl.textContent = data.valid;
            invalidEl.textContent = data.invalid;
            previewBody.innerHTML = data.rows.map(r => `
                <tr class="${Object.keys(r.errors).length ? 'table-danger' : ''}">
                    <td>${r.line}</td>
                    <td>${escape(r.data.full_name)}</td>
                    <td>${escape(r.data.email || '')}</td>
                    <td>${escape(r.data.phone || '')}</td>
                    <td class="text-end">${r.data.paid_amount || 0}</td>
                    <td class="small">${Object.values(r.errors).join(', ')}</td>
                </tr>`).join('');
            previewBox.classList.remove('d-none');
            importConfirmBtn.disabled = data.valid === 0;
        } catch (err) {
            window.app.toast.error(err.message);
        }
    });

    importConfirmBtn.addEventListener('click', async () => {
        if (!currentFile) return;
        window.app.loading.on(importConfirmBtn);
        try {
            const fd = new FormData();
            fd.append('file', currentFile);
            const result = await window.app.api.post(url.import, fd);
            window.app.toast.success(`${result.imported} jugadores importados.`);
            window.app.modal.close('import-csv-modal');
            window.location.reload();
        } catch (err) {
            window.app.toast.error(err.message);
        } finally {
            window.app.loading.off(importConfirmBtn);
        }
    });

    // ---- Renderers ----
    function desktopRowHtml(p) {
        return `
        <tr class="player-row" data-player-id="${p.id}" data-layout="desktop">
            <td><input type="text" class="form-control form-control-sm field-name" value="${escape(p.full_name)}" placeholder="Nombre"></td>
            <td><input type="email" class="form-control form-control-sm field-email" value="${escape(p.email || '')}" placeholder="email@ejemplo.com"></td>
            <td><input type="text" class="form-control form-control-sm field-phone" value="${escape(p.phone || '')}" placeholder="—"></td>
            <td>
                <div class="input-group input-group-sm">
                    <span class="input-group-text">$</span>
                    <input type="number" min="0" step="0.01" class="form-control form-control-sm field-paid" value="${p.paid_amount}">
                </div>
            </td>
            <td>
                <select class="form-select form-select-sm field-status">
                    <option value="unpaid"  ${p.payment_status === 'unpaid'  ? 'selected' : ''}>No pagado</option>
                    <option value="partial" ${p.payment_status === 'partial' ? 'selected' : ''}>Parcial</option>
                    <option value="paid"    ${p.payment_status === 'paid'    ? 'selected' : ''}>Pagado</option>
                </select>
            </td>
            <td class="text-end" style="white-space:nowrap;">
                <button class="btn btn-sm btn-outline-secondary save-player"><i class="fa-solid fa-floppy-disk"></i></button>
                <button class="btn btn-sm btn-outline-danger delete-player"><i class="fa-solid fa-trash"></i></button>
            </td>
        </tr>`;
    }

    function mobileRowHtml(p) {
        const initial = (p.full_name || '?').charAt(0).toUpperCase();
        return `
        <div class="player-row player-card" data-player-id="${p.id}" data-layout="mobile">
            <div class="player-card-header">
                <div class="player-card-avatar">${escape(initial)}</div>
                <div class="flex-grow-1 min-w-0">
                    <input type="text" class="form-control form-control-sm field-name" value="${escape(p.full_name)}" placeholder="Nombre">
                </div>
                <span class="player-status-dot status-${p.payment_status}" title="${p.payment_status}"></span>
            </div>
            <div class="player-card-body">
                <div class="player-field">
                    <label><i class="fa-solid fa-envelope text-secondary"></i> Email</label>
                    <input type="email" class="form-control form-control-sm field-email" value="${escape(p.email || '')}" placeholder="email@ejemplo.com">
                </div>
                <div class="player-field">
                    <label><i class="fa-solid fa-phone text-secondary"></i> Teléfono</label>
                    <input type="text" class="form-control form-control-sm field-phone" value="${escape(p.phone || '')}" placeholder="—">
                </div>
                <div class="row g-2">
                    <div class="col-7">
                        <div class="player-field mb-0">
                            <label><i class="fa-solid fa-dollar-sign text-secondary"></i> Pagado</label>
                            <div class="input-group input-group-sm">
                                <span class="input-group-text">$</span>
                                <input type="number" min="0" step="0.01" class="form-control form-control-sm field-paid" value="${p.paid_amount}">
                            </div>
                        </div>
                    </div>
                    <div class="col-5">
                        <div class="player-field mb-0">
                            <label><i class="fa-solid fa-circle-check text-secondary"></i> Estado</label>
                            <select class="form-select form-select-sm field-status">
                                <option value="unpaid"  ${p.payment_status === 'unpaid'  ? 'selected' : ''}>No pagado</option>
                                <option value="partial" ${p.payment_status === 'partial' ? 'selected' : ''}>Parcial</option>
                                <option value="paid"    ${p.payment_status === 'paid'    ? 'selected' : ''}>Pagado</option>
                            </select>
                        </div>
                    </div>
                </div>
            </div>
            <div class="player-card-footer">
                <button class="btn btn-sm btn-outline-danger delete-player"><i class="fa-solid fa-trash me-1"></i> Eliminar</button>
                <button class="btn btn-sm btn-primary save-player"><i class="fa-solid fa-floppy-disk me-1"></i> Guardar</button>
            </div>
        </div>`;
    }

    function recountPlayers() {
        // Count distinct player IDs, not DOM nodes (each player has 2 nodes: one per layout)
        const ids = new Set();
        document.querySelectorAll('.player-row').forEach(r => ids.add(r.dataset.playerId));
        countEl.textContent = ids.size;
    }

    function removeEmptyStates() {
        document.getElementById('players-empty-desktop')?.remove();
        document.getElementById('players-empty-mobile')?.remove();
    }

    function maybeShowEmptyStates() {
        const remaining = document.querySelectorAll('.player-row').length;
        if (remaining > 0) return;
        if (tbodyDesktop && !document.getElementById('players-empty-desktop')) {
            tbodyDesktop.innerHTML = `<tr id="players-empty-desktop"><td colspan="6" class="text-center py-4 text-secondary">Aún no hay jugadores. Agrega uno o importa un CSV.</td></tr>`;
        }
        if (tbodyMobile && !document.getElementById('players-empty-mobile')) {
            tbodyMobile.innerHTML = `<div id="players-empty-mobile" class="text-center py-4 text-secondary">Aún no hay jugadores. Agrega uno o importa un CSV.</div>`;
        }
    }

    function escape(s) {
        return String(s).replace(/[&<>"']/g, c => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]));
    }
}