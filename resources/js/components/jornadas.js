export function mountJornadas() {
    const list = document.getElementById('jornadas-list');
    const addBtn = document.getElementById('add-jornada-btn');
    if (!list || !addBtn) return;

    addBtn?.addEventListener('click', async () => {
        window.app.loading.on(addBtn);
        try {
            const data = await window.app.api.post(addBtn.dataset.url, {});
            window.app.toast.success(data.message || 'Jornada creada');
            setTimeout(() => window.location.reload(), 700);
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

    const save = async (confirmWindow) => {
        const payload = confirmWindow ? { ...fd, confirm_window: 1 } : fd;
        await window.app.api.put(wForm.dataset.url, payload);
        window.app.toast.success('Ajustes guardados');
    };

    try {
        await save(false);
    } catch (err) {
        // Scheduled matches fall outside the new window — ask before saving.
        if (err.data && err.data.code === 'WINDOW_OUT_OF_RANGE') {
            const list = (err.data.matches || [])
                .map(m => `${m.label} (${m.date})`).join(', ');
            const ok = await window.app.modal.confirm({
                title: 'Partidos fuera del rango',
                body: `Estos partidos ya programados quedan fuera del nuevo rango: ${list}. Tendrás que reprogramarlos. ¿Guardar el nuevo rango de todas formas?`,
                confirmText: 'Guardar de todas formas',
                cancelText: 'Cancelar',
            });
            if (!ok) return;
            try {
                await save(true);
            } catch (err2) {
                window.app.toast.error(err2.message);
            }
            return;
        }
        window.app.toast.error(err.message);
    }
});

document.querySelectorAll('.delete-jornada-btn').forEach(btn => {
    btn.addEventListener('click', async () => {
        const number = btn.dataset.number;
        const ok = await window.app.modal.confirm({
            title: `Eliminar Jornada ${number}`,
            body: `Esto eliminará la Jornada ${number} y todas sus canchas y resultados. `
                + `La jornada anterior volverá a estar disponible para editar. Esta acción no se puede deshacer.`,
            confirmText: 'Eliminar',
            danger: true,
        });
        if (!ok) return;

        try {
            const data = await window.app.api.delete(btn.dataset.url);
            window.app.toast.success(data.message || 'Jornada eliminada');
            setTimeout(() => window.location.reload(), 700);
        } catch (err) {
            window.app.toast.error(err.message);
        }
    });
});