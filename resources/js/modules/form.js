export function serializeForm(form) {
    const fd = new FormData(form);
    const obj = {};
    for (const [k, v] of fd.entries()) {
        if (obj[k] !== undefined) {
            obj[k] = [].concat(obj[k], v);
        } else {
            obj[k] = v;
        }
    }
    return obj;
}

import Sortable from 'sortablejs';

const list = document.getElementById('standings-order-list');
if (list) {
    Sortable.create(list, {
        animation: 150,
        handle: '.drag-handle',
        onSort: () => {
            // Renumber the visible rank badges after a reorder
            list.querySelectorAll('.standings-order-item').forEach((li, i) => {
                li.querySelector('.standings-order-rank').textContent = i + 1;
            });
        },
    });
}