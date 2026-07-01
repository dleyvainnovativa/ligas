import { auth } from '../firebase.js';

function getCsrf() {
    const meta = document.querySelector('meta[name="csrf-token"]');
    return meta ? meta.content : '';
}

async function authHeader() {
    const user = auth.currentUser;
    if (!user) return {};
    try {
        const token = await user.getIdToken();
        return { Authorization: `Bearer ${token}` };
    } catch (_) {
        return {};
    }
}

async function request(method, url, body = null) {
    const headers = {
        'Accept': 'application/json',
        'X-CSRF-TOKEN': getCsrf(),
        'X-Requested-With': 'XMLHttpRequest',
        ...(await authHeader()),
    };
    if (body && !(body instanceof FormData)) {
        headers['Content-Type'] = 'application/json';
        body = JSON.stringify(body);
    }
    const res = await fetch(url, { method, headers, body, credentials: 'same-origin' });

    // Session expired — handle gracefully
    if (res.status === 419 || res.status === 401) {
        const msg = res.status === 419
            ? 'Tu sesión expiró. Recargando…'
            : 'No estás autenticado. Redirigiendo…';
        try { window.app?.toast?.warn(msg); } catch (_) {}
        setTimeout(() => window.location.reload(), 1200);
        throw new Error(msg);
    }

    // if (!res.ok) {
    //     const err = await res.json().catch(() => ({ message: res.statusText }));
    //     throw new Error(err.message || `HTTP ${res.status}`);
    // }
    if (!res.ok) {
        const body = await res.json().catch(() => ({ message: res.statusText }));
        const error = new Error(body.message || `HTTP ${res.status}`);
        error.status = res.status;
        error.data = body;          // full JSON body — lets callers read needs_confirmation, etc.
        throw error;
    }
    return res.status === 204 ? null : res.json();
}

export const api = {
    get:    (url)         => request('GET', url),
    post:   (url, body)   => request('POST', url, body),
    put:    (url, body)   => request('PUT', url, body),
    delete: (url)         => request('DELETE', url),
};