export function mountLeagueForm() {
    const form = document.getElementById('league-form');
    if (!form) return;

    const submitBtn = document.getElementById('league-submit');
    let dirty = false;
    let submitting = false;

    // Mark dirty on any input change
    form.addEventListener('input', () => { dirty = true; });
    form.addEventListener('change', () => { dirty = true; });

    form.addEventListener('submit', () => {
        submitting = true;
        if (submitBtn) window.app.loading.on(submitBtn);
    });

    window.addEventListener('beforeunload', (e) => {
        if (!dirty || submitting) return;
        e.preventDefault();
        // Most browsers ignore the custom message but will show their generic dialog.
        e.returnValue = '';
    });

    const deleteBtn = document.getElementById('delete-league-btn');
    if (deleteBtn) {
        deleteBtn.addEventListener('click', async () => {
            const ok = await window.app.modal.confirm({
                title: 'Eliminar liga',
                body: 'Esta acción borrará la liga, sus jugadores, jornadas, canchas y resultados. No se puede deshacer.',
                confirmText: 'Eliminar liga',
                danger: true,
            });
            if (!ok) return;
            // Sidestep the unload guard for our own form submit
            submitting = true;
            const f = document.getElementById('delete-league-form');
            f.action = deleteBtn.dataset.action;
            f.submit();
        });
    }
}