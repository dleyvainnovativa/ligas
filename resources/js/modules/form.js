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