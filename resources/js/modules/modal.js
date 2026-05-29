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
};