function ensureContainer() {
    let el = document.querySelector('.toast-container-fixed');
    if (!el) {
        el = document.createElement('div');
        el.className = 'toast-container-fixed';
        document.body.appendChild(el);
    }
    return el;
}

export const toast = {
    show(message, variant = 'info') {
        const container = ensureContainer();
        const icons = {
            success: 'fa-circle-check',
            danger:  'fa-circle-exclamation',
            warning: 'fa-triangle-exclamation',
            info:    'fa-circle-info',
        };
        const el = document.createElement('div');
        el.className = `toast align-items-center text-bg-${variant} border-0 mb-2`;
        el.role = 'alert';
        el.innerHTML = `
            <div class="d-flex">
                <div class="toast-body">
                    <i class="fa-solid ${icons[variant] || icons.info} me-2"></i>${message}
                </div>
                <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>
            </div>`;
        container.appendChild(el);
        const t = new bootstrap.Toast(el, { delay: 3500 });
        t.show();
        el.addEventListener('hidden.bs.toast', () => el.remove());
    },
    success: (m) => toast.show(m, 'success'),
    error:   (m) => toast.show(m, 'danger'),
    warn:    (m) => toast.show(m, 'warning'),
    info:    (m) => toast.show(m, 'info'),
};