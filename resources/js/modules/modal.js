export const modal = {
    open(id) {
        const el = document.getElementById(id);
        if (!el) return null;
        const m = bootstrap.Modal.getOrCreateInstance(el);
        m.show();
        return m;
    },
    close(id) {
        const el = document.getElementById(id);
        const m = el && bootstrap.Modal.getInstance(el);
        if (m) m.hide();
    },
    /**
     * Promise-based confirmation. Returns true if user clicks confirm, false otherwise.
     * Usage:
     *   if (await window.app.modal.confirm({ title: 'Borrar?', body: '…', danger: true })) {
     *       // delete
     *   }
     */
    confirm({ title = 'Confirmar', body = '¿Estás seguro?', confirmText = 'Confirmar', cancelText = 'Cancelar', danger = false } = {}) {
        return new Promise((resolve) => {
            let el = document.getElementById('global-confirm-modal');
            if (!el) {
                el = document.createElement('div');
                el.id = 'global-confirm-modal';
                el.className = 'modal fade';
                el.tabIndex = -1;
                el.innerHTML = `
                    <div class="modal-dialog modal-dialog-centered">
                      <div class="modal-content">
                        <div class="modal-header">
                          <h5 class="modal-title" data-role="title"></h5>
                          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                        </div>
                        <div class="modal-body" data-role="body"></div>
                        <div class="modal-footer">
                          <button type="button" class="btn btn-outline-secondary" data-role="cancel" data-bs-dismiss="modal"></button>
                          <button type="button" class="btn" data-role="confirm"></button>
                        </div>
                      </div>
                    </div>`;
                document.body.appendChild(el);
            }
            el.querySelector('[data-role="title"]').textContent = title;
            el.querySelector('[data-role="body"]').textContent = body;
            const confirmBtn = el.querySelector('[data-role="confirm"]');
            const cancelBtn  = el.querySelector('[data-role="cancel"]');
            confirmBtn.textContent = confirmText;
            confirmBtn.className = `btn ${danger ? 'btn-danger' : 'btn-primary'}`;
            cancelBtn.textContent = cancelText;

            const m = bootstrap.Modal.getOrCreateInstance(el);
            const handleConfirm = () => { cleanup(); resolve(true); m.hide(); };
            const handleHide    = () => { cleanup(); resolve(false); };

            confirmBtn.addEventListener('click', handleConfirm);
            el.addEventListener('hidden.bs.modal', handleHide, { once: true });

            function cleanup() {
                confirmBtn.removeEventListener('click', handleConfirm);
            }

            m.show();
        });
    },
};