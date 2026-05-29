export function mountJornadas() {
    const list = document.getElementById('jornadas-list');
    const addBtn = document.getElementById('add-jornada-btn');
    if (!list || !addBtn) return;

    addBtn.addEventListener('click', async () => {
        window.app.loading.on(addBtn);
        try {
            const { jornada } = await window.app.api.post(addBtn.dataset.url, {});
            window.app.toast.success(`Jornada #${jornada.number} creada`);
            // Easiest: reload so the URL builder gives us the right detail link
            window.location.reload();
        } catch (err) {
            window.app.toast.error(err.message);
            window.app.loading.off(addBtn);
        }
    });
}

const wForm = document.getElementById('jornada-window-form');
wForm?.addEventListener('submit', async (e) => {
    e.preventDefault();
    const fd = window.app.serializeForm(wForm);
    try {
        await window.app.api.put(wForm.dataset.url, fd);
        window.app.toast.success('Fechas guardadas');
    } catch (err) {
        window.app.toast.error(err.message);
    }
});