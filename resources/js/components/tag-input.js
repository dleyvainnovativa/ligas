/**
 * Mount tag-input widgets.
 * <div id="time-slots-input" data-name="time_slots" data-initial='["18:00","19:00"]'></div>
 *
 * Renders chips, hidden inputs (name="time_slots[]"), and an inline input
 * that accepts comma-separated entries.
 */
export function mountTagInputs(root = document) {
    root.querySelectorAll('[data-name][data-initial]').forEach((el) => {
        if (el.dataset.tagInputMounted) return;
        el.dataset.tagInputMounted = '1';

        const name = el.dataset.name;
        const initial = JSON.parse(el.dataset.initial || '[]');
        let tags = [...initial];

        el.classList.add('tag-input');
        el.innerHTML = `
            <div class="tag-list d-contents"></div>
            <input type="text" placeholder="Agregar… (Enter)">
        `;
        const list = el.querySelector('.tag-list');
        const input = el.querySelector('input[type="text"]');

        function render() {
            list.innerHTML = tags.map((t, i) => `
                <span class="tag">
                    <input type="hidden" name="${name}[]" value="${escapeAttr(t)}">
                    ${escapeHtml(t)}
                    <button type="button" data-i="${i}" aria-label="Eliminar">
                        <i class="fa-solid fa-xmark"></i>
                    </button>
                </span>
            `).join('');
            list.querySelectorAll('button[data-i]').forEach((b) =>
                b.addEventListener('click', () => {
                    tags.splice(Number(b.dataset.i), 1);
                    render();
                })
            );
        }

        function addRaw(raw) {
            raw.split(',').map((s) => s.trim()).filter(Boolean).forEach((v) => {
                if (!tags.includes(v)) tags.push(v);
            });
            render();
        }

        input.addEventListener('keydown', (e) => {
            if (e.key === 'Enter' || e.key === ',') {
                e.preventDefault();
                addRaw(input.value);
                input.value = '';
            } else if (e.key === 'Backspace' && !input.value && tags.length) {
                tags.pop();
                render();
            }
        });
        input.addEventListener('blur', () => {
            if (input.value.trim()) {
                addRaw(input.value);
                input.value = '';
            }
        });

        render();
    });
}

function escapeHtml(s) {
    return String(s).replace(/[&<>"']/g, (c) => ({
        '&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'
    }[c]));
}
function escapeAttr(s) {
    return escapeHtml(s);
}