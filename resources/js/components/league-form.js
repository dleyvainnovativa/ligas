export function mountLeagueForm() {
    const form = document.getElementById('league-form');
    if (!form) return;

    const submitBtn = document.getElementById('league-submit');
    form.addEventListener('submit', () => {
        if (submitBtn) window.app.loading.on(submitBtn);
    });

    const deleteBtn = document.getElementById('delete-league-btn');
    if (deleteBtn) {
        deleteBtn.addEventListener('click', () => {
            if (!confirm('¿Eliminar esta liga? Esta acción no se puede deshacer.')) return;
            const f = document.getElementById('delete-league-form');
            f.action = deleteBtn.dataset.action;
            f.submit();
        });
    }
}