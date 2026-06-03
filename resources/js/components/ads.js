import Sortable from 'sortablejs';

export function mountAds() {
    const app = document.getElementById('ads-app');
    if (!app) return;

    const leagueId = app.dataset.leagueId;
    const list  = document.getElementById('ads-list');
    const fileInput = document.getElementById('ad-file-input');
    const addBtn = document.getElementById('add-ad-btn');

    const url = {
        list:    `/leagues/${leagueId}/ads`,
        one:     (id) => `/leagues/${leagueId}/ads/${id}`,
        reorder: `/leagues/${leagueId}/ads/reorder`,
    };

    // Mount sortable for reorder
    let sortable = null;
    function mountSortable() {
        if (sortable) sortable.destroy();
        sortable = Sortable.create(list, {
            animation: 150,
            handle: '.ad-grip',
            ghostClass: 'sortable-ghost',
            chosenClass: 'sortable-chosen',
            onEnd: async () => {
                const ids = Array.from(list.querySelectorAll('.ad-row')).map(r => parseInt(r.dataset.adId, 10));
                try {
                    await window.app.api.post(url.reorder, { ids });
                } catch (err) {
                    window.app.toast.error('No se pudo guardar el orden');
                }
            },
        });
    }

    // ---- Add ad: trigger file picker first ----
    let mode = 'add'; // 'add' | 'replace'
    let replaceTargetId = null;

    addBtn.addEventListener('click', () => {
        mode = 'add';
        replaceTargetId = null;
        fileInput.value = '';
        fileInput.click();
    });

    fileInput.addEventListener('change', async () => {
        const file = fileInput.files[0];
        if (!file) return;

        const fd = new FormData();
        fd.append('image', file);

        try {
            if (mode === 'add') {
                const { ad } = await window.app.api.post(url.list, fd);
                document.getElementById('ads-empty')?.remove();
                list.insertAdjacentHTML('beforeend', rowHtml(ad));
                mountSortable();
                window.app.toast.success('Anuncio creado');
            } else if (mode === 'replace' && replaceTargetId) {
                const row = list.querySelector(`.ad-row[data-ad-id="${replaceTargetId}"]`);
                const data = collectRowData(row);
                fd.append('title', data.title);
                fd.append('link_url', data.link_url);
                fd.append('is_active', data.is_active ? '1' : '0');
                const { ad } = await window.app.api.post(url.one(replaceTargetId), fd);
                const thumb = row.querySelector('.ad-thumb');
                thumb.style.backgroundImage = `url('${ad.image_url}')`;
                window.app.toast.success('Imagen actualizada');
            }
        } catch (err) {
            window.app.toast.error(err.message);
        } finally {
            fileInput.value = '';
        }
    });

    // ---- Row actions ----
    list.addEventListener('click', async (e) => {
        const row = e.target.closest('.ad-row');
        if (!row) return;
        const id = row.dataset.adId;

        if (e.target.closest('.save-ad')) {
            const data = collectRowData(row);
            const fd = new FormData();
            fd.append('title', data.title);
            fd.append('link_url', data.link_url);
            fd.append('is_active', data.is_active ? '1' : '0');
            try {
                await window.app.api.post(url.one(id), fd);
                window.app.toast.success('Anuncio guardado');
            } catch (err) {
                window.app.toast.error(err.message);
            }
        }

        if (e.target.closest('.delete-ad')) {
            if (!confirm('¿Eliminar este anuncio?')) return;
            try {
                await window.app.api.delete(url.one(id));
                row.remove();
            } catch (err) {
                window.app.toast.error(err.message);
            }
        }

        if (e.target.closest('.replace-image')) {
            mode = 'replace';
            replaceTargetId = id;
            fileInput.value = '';
            fileInput.click();
        }
    });

    function collectRowData(row) {
        return {
            title:    row.querySelector('.ad-title').value.trim(),
            link_url: row.querySelector('.ad-link').value.trim(),
            is_active: row.querySelector('.ad-active').checked,
        };
    }

    function rowHtml(a) {
        return `
        <div class="ad-row" data-ad-id="${a.id}">
            <i class="fa-solid fa-grip-vertical ad-grip text-muted" title="Arrastra para reordenar"></i>
            <div class="ad-thumb" style="background-image: url('${a.image_url}');"></div>
            <div class="ad-fields">
                <input type="text" class="form-control form-control-sm ad-title" value="${escape(a.title || '')}" placeholder="Título (opcional)">
                <input type="url"  class="form-control form-control-sm ad-link"  value="${escape(a.link_url || '')}" placeholder="https://… (opcional)">
            </div>
            <div class="ad-actions">
                <div class="form-check form-switch mb-1">
                    <input class="form-check-input ad-active" type="checkbox" id="ad-active-${a.id}" ${a.is_active ? 'checked' : ''}>
                    <label class="form-check-label small text-secondary" for="ad-active-${a.id}">Activo</label>
                </div>
                <div class="d-flex gap-1">
                    <button class="btn btn-icon btn-sm replace-image"><i class="fa-solid fa-image"></i></button>
                    <button class="btn btn-icon btn-sm save-ad"><i class="fa-solid fa-floppy-disk"></i></button>
                    <button class="btn btn-icon btn-sm delete-ad"><i class="fa-solid fa-trash"></i></button>
                </div>
            </div>
        </div>`;
    }

    function escape(s) {
        return String(s).replace(/[&<>"']/g, c => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]));
    }

    mountSortable();
}