/**
 * Theme module: light / dark / system tri-state with localStorage persistence.
 *
 * Two ways to add a theme toggle button:
 *
 * 1. Declarative (preferred): give the button a `data-theme-toggle` attribute.
 *    The module's global click handler will flip the theme automatically.
 *      <button data-theme-toggle>...</button>
 *
 * 2. Imperative: import { toggle } and call it explicitly.
 *      import { toggle as toggleTheme } from './modules/theme.js';
 *      myButton.addEventListener('click', toggleTheme);
 */

const KEY = 'pl_theme'; // 'light' | 'dark' | 'system'

export function initTheme() {
    apply(get());

    // Sync with system changes when set to 'system'
    if (window.matchMedia) {
        window.matchMedia('(prefers-color-scheme: dark)').addEventListener('change', () => {
            if (get() === 'system') apply('system');
        });
    }

    // Wire any visible toggle buttons declaratively
    document.addEventListener('click', (e) => {
        const btn = e.target.closest('[data-theme-toggle]');
        if (!btn) return;
        toggle();
    });
}

/** Get the user's saved preference. Returns 'light', 'dark', or 'system'. */
export function get() {
    return localStorage.getItem(KEY) || 'system';
}

/** Set the user's preference. Pass 'light', 'dark', or 'system'. */
export function set(value) {
    if (value === 'system') {
        localStorage.removeItem(KEY);
    } else {
        localStorage.setItem(KEY, value);
    }
    apply(value);
}

/** The currently-rendered theme: always 'light' or 'dark', never 'system'. */
export function resolved() {
    const pref = get();
    if (pref === 'light' || pref === 'dark') return pref;
    return window.matchMedia?.('(prefers-color-scheme: dark)').matches ? 'dark' : 'light';
}

/**
 * Toggle between light and dark. If the user is on 'system', this resolves
 * to whatever is currently displayed and flips it, then persists the choice
 * (so the next page load isn't surprising).
 */
export function toggle() {
    const current = resolved();
    const next = current === 'dark' ? 'light' : 'dark';
    set(next);
}

/**
 * Set theme by cycling through all three states: light → dark → system → light.
 * Useful if you ever want a three-state toggle button.
 */
export function cycle() {
    const order = ['light', 'dark', 'system'];
    const idx = order.indexOf(get());
    const next = order[(idx + 1) % order.length];
    set(next);
}

function apply(value) {
    const theme = (value === 'light' || value === 'dark')
        ? value
        : (window.matchMedia?.('(prefers-color-scheme: dark)').matches ? 'dark' : 'light');
    document.documentElement.setAttribute('data-bs-theme', theme);
}