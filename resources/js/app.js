import 'bootstrap';
import * as bootstrap from 'bootstrap';

// Fonts (loaded once, cached)
import '@fontsource/inter/400.css';
import '@fontsource/inter/500.css';
import '@fontsource/inter/600.css';
import '@fontsource/inter/700.css';
import '@fontsource/inter-tight/600.css';
import '@fontsource/inter-tight/700.css';
import '@fontsource/inter-tight/800.css';
import '@fontsource/jetbrains-mono/500.css';

import { auth, signInWithEmailAndPassword, signOut } from './firebase.js';
import { toast } from './modules/toast.js';
import { api } from './modules/api.js';
import { loading } from './modules/loading.js';
import { modal } from './modules/modal.js';
import { serializeForm } from './modules/form.js';
import { mountTagInputs } from './components/tag-input.js';
import { mountLeagueForm } from './components/league-form.js';
import { mountSedes } from './components/sedes.js';
import { mountPlayers } from './components/players.js';
import { mountGroups } from './components/groups.js';
import { mountPairs } from './components/pairs.js';
import { mountJornadas } from './components/jornadas.js';
import { mountCanchas } from './components/canchas.js';
import { mountScheduleGrid } from './components/schedule-grid.js';
import { mountMatchResult } from './components/match-result.js';
import { initTheme } from './modules/theme.js';
import { mountAds } from './components/ads.js';
import { mountCellPicker } from './components/cell-picker.js';
import { mountGroupPicker } from './components/group-picker.js';
import { mountCanchaPicker } from './components/cancha-picker.js';

window.bootstrap = bootstrap;
window.app = { toast, api, loading, modal, serializeForm };
window.firebase = { auth, signInWithEmailAndPassword, signOut };

document.addEventListener('DOMContentLoaded', () => {
    initTheme();

    // NEW: Bootstrap-tab hash activation — open a tab if its target is in the URL hash
    if (window.location.hash) {
        const tabButton = document.querySelector(`[data-bs-toggle="tab"][data-bs-target="${window.location.hash}"]`);
        if (tabButton) {
            new bootstrap.Tab(tabButton).show();
        }
    }

    // NEW: Flash message → toast
    const flash = document.getElementById('flash-message');
    if (flash?.dataset.message) {
        toast.success(flash.dataset.message);
    }

    mountTagInputs();
    mountAds();
    mountLeagueForm();
    mountSedes();
    mountPlayers();
    mountGroups();
    mountPairs();
    mountJornadas();
    mountCanchas();
    mountScheduleGrid();
    mountMatchResult();
    mountCellPicker();
    mountGroupPicker();
    mountCanchaPicker();
});

document.addEventListener('click', (e) => {
    if (e.target.closest('[data-action="open-sidebar"]')) {
        document.getElementById('app-sidebar')?.classList.add('open');
        document.getElementById('sidebar-backdrop')?.classList.add('show');
    } else if (e.target.id === 'sidebar-backdrop') {
        document.getElementById('app-sidebar')?.classList.remove('open');
        document.getElementById('sidebar-backdrop')?.classList.remove('show');
    }
});