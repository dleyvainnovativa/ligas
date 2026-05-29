import { auth } from '../firebase.js';

function getCsrf() {
    const meta = document.querySelector('meta[name="csrf-token"]');
    return meta ? meta.content : '';
}

async function authHeader() {
    const user = auth.currentUser;
    if (!user) return {};
    const token = await user.getIdToken(true);
    return { Authorization: `Bearer ${token}` };
}

async function request(method, url, body = null) {
    const headers = {
        'Accept': 'application/json',
        'X-CSRF-TOKEN': getCsrf(),
        ...(await authHeader()),
    };
    if (body && !(body instanceof FormData)) {
        headers['Content-Type'] = 'application/json';
        body = JSON.stringify(body);
    }
    const res = await fetch(url, { method, headers, body, credentials: 'same-origin' });
    if (!res.ok) {
        const err = await res.json().catch(() => ({ message: res.statusText }));
        throw new Error(err.message || `HTTP ${res.status}`);
    }
    return res.status === 204 ? null : res.json();
}

export const api = {
    get:    (url)         => request('GET', url),
    post:   (url, body)   => request('POST', url, body),
    put:    (url, body)   => request('PUT', url, body),
    delete: (url)         => request('DELETE', url),
};