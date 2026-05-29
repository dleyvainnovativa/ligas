// Lean: only what the public page actually needs.
// Bootstrap Tab + Collapse plugins for the group tabs and any disclosures.
import { Tab, Collapse } from 'bootstrap';

// Make them globally accessible for data-bs-toggle to work
window.bootstrap = { Tab, Collapse };

import '@fontsource/inter/400.css';
import '@fontsource/inter/500.css';
import '@fontsource/inter/600.css';
import '@fontsource/inter/700.css';
import '@fontsource/inter-tight/700.css';
import '@fontsource/inter-tight/800.css';
import '@fontsource/jetbrains-mono/500.css';


function initThemeMinimal() {
    document.addEventListener('click', (e) => {
        const btn = e.target.closest('[data-theme-toggle]');
        if (!btn) return;
        const current = document.documentElement.getAttribute('data-bs-theme') || 'light';
        const next = current === 'dark' ? 'light' : 'dark';
        if (next === 'system') localStorage.removeItem('pl_theme');
        else localStorage.setItem('pl_theme', next);
        document.documentElement.setAttribute('data-bs-theme', next);
    });
}
initThemeMinimal();
// Native share where supported
document.addEventListener('click', async (e) => {
    const btn = e.target.closest('[data-action="share"]');
    if (!btn) return;
    e.preventDefault();
    const url   = btn.dataset.url || window.location.href;
    const title = btn.dataset.title || document.title;
    try {
        if (navigator.share) {
            await navigator.share({ title, url });
        } else {
            await navigator.clipboard.writeText(url);
            btn.dataset.originalHtml = btn.dataset.originalHtml || btn.innerHTML;
            btn.innerHTML = '<i class="fa-solid fa-check me-1"></i> Copiado';
            setTimeout(() => { btn.innerHTML = btn.dataset.originalHtml; }, 1800);
        }
    } catch (_) { /* user cancelled */ }
});

// Smooth scroll for in-page anchor links inside tab panes
document.querySelectorAll('a[href^="#"]').forEach((a) => {
    a.addEventListener('click', (e) => {
        const id = a.getAttribute('href');
        if (id.length < 2) return;
        const target = document.querySelector(id);
        if (!target) return;
        e.preventDefault();
        target.scrollIntoView({ behavior: 'smooth', block: 'start' });
    });
});