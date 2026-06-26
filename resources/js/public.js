// Lean: only what the public page actually needs.
// Bootstrap Tab + Collapse plugins for the group tabs and any disclosures.
import { Tab, Collapse, Carousel, Modal } from 'bootstrap';

// Make them globally accessible for data-bs-toggle to work
window.bootstrap = { Tab, Collapse, Carousel, Modal };

import '@fontsource/inter/400.css';
import '@fontsource/inter/500.css';
import '@fontsource/inter/600.css';
import '@fontsource/inter/700.css';
import '@fontsource/inter-tight/700.css';
import '@fontsource/inter-tight/800.css';
import '@fontsource/jetbrains-mono/500.css';


// ---- Match proposal flow ----
(function () {
    const slug = window.__publicLeagueSlug;
    const matches = window.__publicMatches || {};
    if (!slug) return;

    const modalEl = document.getElementById('propose-modal');
    if (!modalEl) return;
    const modal = new Modal(modalEl);
    const body = document.getElementById('propose-modal-body');
    const submitBtn = document.getElementById('propose-submit-btn');

    let currentMatchId = null;
    let currentSets = [[0, 0]];

    document.addEventListener('click', (e) => {
        const btn = e.target.closest('.propose-btn');
if (!btn) return;
currentMatchId = btn.dataset.roundId;  // renamed semantically; URL still uses /matches/{id}/propose
        const m = matches[currentMatchId];
        if (!m) return;

        // If we proposed before, prefill the name from cookie
        const lastName = localStorage.getItem('pl_proposer_name') || '';

        currentSets = [[0, 0]];

        body.innerHTML = `
            <div class="propose-match-summary mb-3">
                <div class="propose-teams">
                    <div class="t-name">${m.team_a.map(escape).join(' / ')}</div>
                    <div class="t-vs">vs</div>
                    <div class="t-name text-end">${m.team_b.map(escape).join(' / ')}</div>
                </div>
                <div class="text-muted small mt-1">
                    ${m.date_display ? escape(m.date_display) : ''}
                    ${m.time_slot ? ' · ' + escape(m.time_slot) : ''}
                </div>
            </div>

            <div class="mb-3">
                <label class="form-label">Tu nombre</label>
                <input type="text" class="form-control" id="proposer-name" maxlength="120"
                       value="${escape(lastName)}" placeholder="Para que el manager sepa quién propuso">
            </div>

            <label class="form-label">Marcador</label>
            <div id="propose-sets" class="propose-sets"></div>
            

            <small class="text-muted d-block mt-3">
                <i class="fa-solid fa-circle-info"></i>
                El manager revisará y aprobará el marcador. Cualquier jugador puede proponer.
            </small>
        `;
        renderSets();
        modal.show();
    });

    function renderSets() {
        const c = document.getElementById('propose-sets');
        c.innerHTML = currentSets.map((s, i) => `
            <div class="propose-set-row" data-i="${i}">
                <span class="set-label">Set</span>
                <input type="number" min="0" max="99" class="form-control form-control-sm set-a" inputmode="numeric" value="${s[0]}">
                <span class="text-muted">—</span>
                <input type="number" min="0" max="99" class="form-control form-control-sm set-b" inputmode="numeric" value="${s[1]}">
                <button type="button" class="btn-icon btn-sm remove-set" ${currentSets.length === 1 ? 'disabled' : ''}>
                    <i class="fa-solid fa-xmark"></i>
                </button>
            </div>
        `).join('');
    }

    body?.addEventListener('input', (e) => {
        const row = e.target.closest('.propose-set-row');
        if (!row) return;
        const i = parseInt(row.dataset.i, 10);
        if (e.target.classList.contains('set-a')) currentSets[i][0] = parseInt(e.target.value || '0', 10);
        if (e.target.classList.contains('set-b')) currentSets[i][1] = parseInt(e.target.value || '0', 10);
    });

    body?.addEventListener('click', (e) => {
        if (e.target.closest('#propose-add-set')) {
            currentSets.push([0, 0]);
            renderSets();
        }
        const rm = e.target.closest('.remove-set');
        if (rm && !rm.disabled) {
            const row = rm.closest('.propose-set-row');
            const i = parseInt(row.dataset.i, 10);
            currentSets.splice(i, 1);
            renderSets();
        }
    });

    submitBtn?.addEventListener('click', async () => {
        const name = document.getElementById('proposer-name').value.trim();
        if (name.length < 2) {
            alert('Por favor ingresa tu nombre.');
            return;
        }
        const sets = currentSets.filter(s => s[0] > 0 || s[1] > 0);
        if (sets.length === 0) {
            alert('Ingresa al menos un set con marcador.');
            return;
        }

        submitBtn.disabled = true;
        const original = submitBtn.innerHTML;
        submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Enviando…';

        try {
            const res = await fetch(`/${slug}/matches/${currentMatchId}/propose`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '',
                },
                body: JSON.stringify({ name, sets }),
                credentials: 'same-origin',
            });
            if (!res.ok) {
                const err = await res.json().catch(() => ({ message: res.statusText }));
                throw new Error(err.message || 'No se pudo enviar la propuesta.');
            }
            localStorage.setItem('pl_proposer_name', name);
            modal.hide();
            // Soft refresh to show the new proposal banner
            window.location.reload();
        } catch (err) {
            alert(err.message);
            submitBtn.disabled = false;
            submitBtn.innerHTML = original;
        }
    });

    function escape(s) {
        return String(s).replace(/[&<>"']/g, c => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]));
    }
})();

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


// ---- Jornada cancha dropdown selector ----
document.querySelectorAll('.cancha-dropdown').forEach(dropdown => {
    dropdown.addEventListener('change', (e) => {
        const groupIdx = e.target.dataset.group;
        const selectedValue = e.target.value; // e.g. "0-2"

        // Hide all panels in this group, show the selected one
        document.querySelectorAll(`[data-cancha-panel^="${groupIdx}-"]`).forEach(panel => {
            panel.classList.toggle('d-none', panel.dataset.canchaPanel !== selectedValue);
        });
    });
});

// ---- Jornada: preserve location (group + cancha + inner tab) across reloads ----
(function () {
    // Only run on pages that have the cancha selector structure
    const hasJornadaTabs = document.querySelector('.cancha-dropdown');
    if (!hasJornadaTabs) return;

    const TAB_STANDINGS = 'standings';
    const TAB_MATCHES   = 'matches';

    function writeHash(groupIdx, canchaIdx, tab) {
        const hash = `#loc-${groupIdx}-${canchaIdx}-${tab}`;
        // replaceState so we don't spam browser history or cause a jump
        history.replaceState(null, '', hash);
    }

    function currentTabFor(groupIdx, canchaIdx) {
        const matchesTab = document.querySelector(`[data-bs-target="#matches-${groupIdx}-${canchaIdx}"]`);
        return matchesTab && matchesTab.classList.contains('active') ? TAB_MATCHES : TAB_STANDINGS;
    }

    // --- Wire listeners that update the hash ---

    // Group tab change
    document.querySelectorAll('[data-bs-target^="#jornada-group-"]').forEach(btn => {
        btn.addEventListener('shown.bs.tab', () => {
            const groupIdx = btn.dataset.bsTarget.replace('#jornada-group-', '');
            const dropdown = document.querySelector(`.cancha-dropdown[data-group="${groupIdx}"]`);
            const canchaIdx = dropdown ? dropdown.value.split('-')[1] : '0';
            writeHash(groupIdx, canchaIdx, currentTabFor(groupIdx, canchaIdx));
        });
    });

    // Cancha dropdown change
    document.querySelectorAll('.cancha-dropdown').forEach(dropdown => {
        dropdown.addEventListener('change', (e) => {
            const [groupIdx, canchaIdx] = e.target.value.split('-');
            writeHash(groupIdx, canchaIdx, currentTabFor(groupIdx, canchaIdx));
        });
    });

    // Inner tab change (Standings / Partidos)
    document.querySelectorAll('[data-bs-target^="#standings-"], [data-bs-target^="#matches-"]').forEach(btn => {
        btn.addEventListener('shown.bs.tab', () => {
            // target looks like "#matches-0-2" or "#standings-0-2"
            const target = btn.dataset.bsTarget;
            const isMatches = target.startsWith('#matches-');
            const coords = target.replace('#matches-', '').replace('#standings-', ''); // "0-2"
            const [groupIdx, canchaIdx] = coords.split('-');
            writeHash(groupIdx, canchaIdx, isMatches ? TAB_MATCHES : TAB_STANDINGS);
        });
    });

    // --- On load: restore location from hash, if present ---
    function restoreFromHash() {
        const m = window.location.hash.match(/^#loc-(\d+)-(\d+)-(standings|matches)$/);
        if (!m) return;

        const [, groupIdx, canchaIdx, tab] = m;

        // 1. Activate the group tab (if multiple groups)
        const groupBtn = document.querySelector(`[data-bs-target="#jornada-group-${groupIdx}"]`);
        if (groupBtn && window.bootstrap?.Tab) {
            window.bootstrap.Tab.getOrCreateInstance(groupBtn).show();
        }

        // 2. Select the cancha in the dropdown + show its panel
        const dropdown = document.querySelector(`.cancha-dropdown[data-group="${groupIdx}"]`);
        if (dropdown) {
            dropdown.value = `${groupIdx}-${canchaIdx}`;
            // trigger the same panel-swap logic the change handler does
            dropdown.dispatchEvent(new Event('change'));
        }

        // 3. Activate the inner tab
        const innerSelector = tab === 'matches'
            ? `[data-bs-target="#matches-${groupIdx}-${canchaIdx}"]`
            : `[data-bs-target="#standings-${groupIdx}-${canchaIdx}"]`;
        const innerBtn = document.querySelector(innerSelector);
        if (innerBtn && window.bootstrap?.Tab) {
            window.bootstrap.Tab.getOrCreateInstance(innerBtn).show();
        }
    }

    restoreFromHash();
})();