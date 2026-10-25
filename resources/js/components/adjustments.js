export function mountAdjustments() {
    const app = document.getElementById('adjustments-app');
    if (!app) return;

    const leagueId = app.dataset.leagueId;
    const form = document.getElementById('adjustment-form');
    const list = document.getElementById('adjustments-list');

    const url = {
        list: `/leagues/${leagueId}/adjustments`,
        one:  (id) => `/leagues/${leagueId}/adjustments/${id}`,
    };

    // ---- Add ----
    form?.addEventListener('submit', async (e) => {
        e.preventDefault();
        const payload = window.app.serializeForm(form);

        try {
            const { adjustment, message } = await window.app.api.post(url.list, payload);
            document.getElementById('adjustments-empty')?.remove();
            list.insertAdjacentHTML('afterbegin', rowHtml(adjustment));
            form.reset();
            window.app.toast.success(message || 'Puntos extra agregados');
        } catch (err) {
            window.app.toast.error(err.message);
        }
    });

    // ---- Delete ----
    list?.addEventListener('click', async (e) => {
        const btn = e.target.closest('.delete-adjustment');
        if (!btn) return;
        const row = btn.closest('.adjustment-row');
        if (!row) return;
        const id = row.dataset.adjustmentId;

        if (!confirm('¿Eliminar este ajuste de puntos?')) return;
        try {
            const { message } = await window.app.api.delete(url.one(id));
            row.remove();
            window.app.toast.success(message || 'Eliminado');
            if (!list.querySelector('.adjustment-row')) {
                list.innerHTML = emptyHtml();
            }
        } catch (err) {
            window.app.toast.error(err.message);
        }
    });

    function rowHtml(a) {
        const amount = parseInt(a.amount, 10) || 0;
        const sign = amount > 0 ? '+' : '';
        const unitLabel = a.unit === 'set' ? 'sets' : 'games';
        const tone = amount >= 0 ? 'text-bg-success' : 'text-bg-danger';
        const note = a.note
            ? `<small class="text-secondary text-truncate d-block">${escape(a.note)}</small>`
            : '';
        return `
        <div class="adjustment-row d-flex align-items-center gap-3 p-2 border rounded" data-adjustment-id="${a.id}">
            <div class="flex-grow-1 min-w-0">
                <div class="fw-medium text-truncate">${escape(a.subject || '—')}</div>
                ${note}
            </div>
            <span class="badge ${tone} font-mono">${sign}${amount} ${unitLabel}</span>
            <button class="btn btn-icon btn-sm delete-adjustment" title="Eliminar">
                <i class="fa-solid fa-trash"></i>
            </button>
        </div>`;
    }

    function emptyHtml() {
        return `
        <div class="empty-state py-4" id="adjustments-empty">
            <div class="empty-state-icon"><i class="fa-solid fa-star-half-stroke"></i></div>
            <h6>Sin puntos extra todavía</h6>
            <p class="small mb-0">Asigna un bono o corrección para que aparezca en la tabla general.</p>
        </div>`;
    }

    function escape(s) {
        return String(s).replace(/[&<>"']/g, c => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]));
    }
}
