const KEY = 'pl_theme'; // 'light' | 'dark' | 'system'

export function initTheme() {
    apply(get());

    // Sync with system changes when set to 'system'
    if (window.matchMedia) {
        window.matchMedia('(prefers-color-scheme: dark)').addEventListener('change', () => {
            if (get() === 'system') apply('system');
        });
    }

    // Wire any visible toggle buttons
    document.addEventListener('click', (e) => {
        const btn = e.target.closest('[data-theme-toggle]');
        if (!btn) return;
        const current = resolved();
        const next = current === 'dark' ? 'light' : 'dark';
        set(next);
    });
}

export function get() {
    return localStorage.getItem(KEY) || 'system';
}

export function set(value) {
    if (value === 'system') {
        localStorage.removeItem(KEY);
    } else {
        localStorage.setItem(KEY, value);
    }
    apply(value);
}

export function resolved() {
    const pref = get();
    if (pref === 'light' || pref === 'dark') return pref;
    return window.matchMedia?.('(prefers-color-scheme: dark)').matches ? 'dark' : 'light';
}

function apply(value) {
    const theme = (value === 'light' || value === 'dark')
        ? value
        : (window.matchMedia?.('(prefers-color-scheme: dark)').matches ? 'dark' : 'light');
    document.documentElement.setAttribute('data-bs-theme', theme);
}