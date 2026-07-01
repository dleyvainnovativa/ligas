/**
 * Wrap an assign/swap/auto-fill POST so that a 422 { needs_confirmation }
 * response triggers a confirm modal, then re-sends with confirm_reset: true.
 *
 * Returns:
 *   - the API response on success
 *   - null if the user cancelled the confirmation (caller should NOT reload)
 * Throws on any other error (caller's existing catch handles it).
 */
export async function postWithResetGuard(url, payload) {
    try {
        return await window.app.api.post(url, payload);
    } catch (err) {
        if (err.status === 422 && err.data?.needs_confirmation) {
            const ok = await window.app.modal.confirm({
                title: 'Reprogramar canchas',
                body: err.data.message,
                confirmText: 'Continuar',
                danger: true,
            });
            if (!ok) return null; // cancelled → no change
            return await window.app.api.post(url, { ...payload, confirm_reset: true });
        }
        throw err; // any other error bubbles to the caller's catch
    }
}