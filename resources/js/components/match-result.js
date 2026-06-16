export function mountMatchResult() {
    const grid = document.querySelector('.grid-app');
    if (!grid) return;

    const scheduleUrlTpl = grid.dataset.scheduleUrlTemplate;

    let currentCanchaId = null;
    let currentData = null;

    document.body.addEventListener('click', async (e) => {
        const btn = e.target.closest('.cell-cancha-result');
        if (!btn) return;
        e.stopPropagation();
        currentCanchaId = btn.dataset.canchaId;
        await openResultModal(currentCanchaId);
    });

    async function openResultModal(canchaId) {
        const url = buildUrl(canchaId, 'result');
        const body = document.getElementById('result-modal-body');
        body.innerHTML = `<div class="text-center py-4 text-secondary"><span class="spinner-border spinner-border-sm me-2"></span>Cargando…</div>`;
        window.app.modal.open('result-modal');
        try {
            const data = await window.app.api.get(url);
            currentData = data;
            body.innerHTML = renderForm(data);
            wireRowControls();
            wireProposalActions(canchaId);
        } catch (err) {
            body.innerHTML = `<div class="alert alert-danger mb-0">${err.message}</div>`;
        }
    }

    function buildUrl(canchaId, suffix) {
        return scheduleUrlTpl.replace('/schedule', `/${suffix}`).replace('__ID__', canchaId);
    }

    function renderForm(data) {
        const c = data.cancha;
        const rounds = data.rounds;

        const headerInfo = `
            <div class="d-flex justify-content-between align-items-start mb-3 small text-secondary">
                <div>
                    <strong>${escape(c.label)}</strong>
                    ${c.date ? ` · ${escape(c.date)} ${escape(c.time_slot ?? '')}` : ''}
                    ${c.pista ? ` · ${escape(c.pista)}` : ''}
                </div>
                <div>Estado: <strong>${escape(c.status)}</strong></div>
            </div>`;

        const sessionFlagsHtml = renderSessionFlags(rounds);
        const roundsHtml = rounds.map(round => renderRound(round)).join('');

        return headerInfo + sessionFlagsHtml + roundsHtml;
    }

    /**
     * Collect all unique players across all rounds, then build a single
     * summary block with one no-show / one suplente checkbox per player.
     * Defaults: if a player is currently flagged in ANY round, the box starts checked.
     */
    function renderSessionFlags(rounds) {
    if (rounds.length === 0) return '';

    // Canonical flag round = lowest rotation_index
    const flagRound = rounds.reduce((a, b) => a.rotation_index < b.rotation_index ? a : b);
    const noShowSet   = new Set(flagRound.no_show  || []);
    const suplenteSet = new Set(flagRound.suplente || []);

    // Build a deduplicated list of players from the flag round's teams
    const playerMap = new Map(); // id -> { name }
    [...flagRound.team_a, ...flagRound.team_b].forEach(p => {
        if (!playerMap.has(p.id)) {
            playerMap.set(p.id, { name: p.name });
        }
    });

    if (playerMap.size === 0) return '';

    const playerRows = Array.from(playerMap.entries()).map(([id, info]) => `
        <li class="session-player-row" data-player-id="${id}">
            <span class="session-player-name">${escape(info.name)}</span>
            <div class="session-player-flags">
                <label class="form-check form-check-inline m-0">
                    <input type="checkbox" class="form-check-input flag-noshow"
                           ${noShowSet.has(id) ? 'checked' : ''}>
                    <span class="form-check-label small">No show</span>
                </label>
                <label class="form-check form-check-inline m-0">
                    <input type="checkbox" class="form-check-input flag-suplente"
                           ${suplenteSet.has(id) ? 'checked' : ''}>
                    <span class="form-check-label small">Suplente</span>
                </label>
            </div>
        </li>
    `).join('');

    return `
        <div class="session-flags-block" id="session-flags">
            <div class="session-flags-head">
                <span class="session-flags-title">Jugadores de la cancha</span>
                <small class="text-muted">Las marcas aplican a la sesión completa</small>
            </div>
            <ul class="list-unstyled mb-0">
                ${playerRows}
            </ul>
        </div>
    `;
}

    function renderRound(round) {
        let initialSets;
        if (round.sets && round.sets.length > 0) {
            initialSets = round.sets;
        } else if (round.proposal && round.proposal.sets && round.proposal.sets.length > 0) {
            initialSets = round.proposal.sets;
        } else {
            initialSets = [[0, 0]];
        }
        const setsHtml = initialSets.map((s, i) => setRow(round.id, i, s[0], s[1])).join('');

        const teamLine = (label, team) => `
            <div class="round-team">
                <div class="round-team-label">${label}</div>
                <div class="round-team-players">
                    ${team.map(p => `<span data-player-id="${p.id}">${escape(p.name)}</span>`).join(' / ')}
                </div>
            </div>`;

        const proposalBanner = round.proposal ? `
            <div class="alert alert-warning d-flex align-items-start gap-2 mb-3" role="alert">
                <i class="fa-solid fa-clock-rotate-left mt-1"></i>
                <div class="flex-grow-1">
                    <strong>Propuesta de ${escape(round.proposal.proposer_name)}</strong>
                    · ${escape(round.proposal.created_at)}
                    <div class="small mt-1">
                        Sets propuestos: ${round.proposal.sets.map(s => `<span class="font-mono">${s[0]}–${s[1]}</span>`).join(', ')}
                    </div>
                    <div class="small mt-2 text-muted">
                        Se preseleccionaron los marcadores abajo. Al guardar, la propuesta
                        se marca como <em>aceptada</em> (si coinciden) o <em>modificada</em>.
                    </div>
                </div>
                <button type="button" class="btn btn-sm btn-outline-danger" data-action="reject-proposal"
                        data-proposal-id="${round.proposal.id}" data-round-id="${round.id}">
                    Rechazar
                </button>
            </div>` : '';

        return `
            <div class="round-section" data-round-id="${round.id}">
                <div class="round-section-head">
                    <span class="round-section-badge">Set ${round.rotation_index}</span>
                    ${round.status === 'completed' ? '<span class="badge text-bg-success">Completado</span>' : ''}
                </div>
                ${proposalBanner}
                <div class="round-teams">
                    ${teamLine('A', round.team_a)}
                    <span class="round-vs">vs</span>
                    ${teamLine('B', round.team_b)}
                </div>
                <div class="result-sets mt-2">
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <strong class="small">Score</strong>
                    </div>
                    <div class="sets-list" data-round-id="${round.id}">${setsHtml}</div>
                </div>
            </div>
        `;
    }

    function setRow(roundId, idx, a, b) {
        return `
        <div class="set-row d-flex align-items-center gap-2 mb-2" data-round-id="${roundId}" data-i="${idx}">
            <input type="number" min="0" max="7" class="form-control form-control-sm set-a" inputmode="numeric" value="${a}">
            <span class="text-secondary">—</span>
            <input type="number" min="0" max="7" class="form-control form-control-sm set-b" inputmode="numeric" value="${b}">
        </div>`;
    }

    function wireRowControls() {
        const body = document.getElementById('result-modal-body');
        if (!body) return;

        // Select-all on focus, so typing replaces instead of appending
        body.querySelectorAll('.set-a, .set-b').forEach(input => {
            input.addEventListener('focus', () => input.select());

            // Clamp to 0–7 on every input, and strip non-digits
            input.addEventListener('input', () => {
                let v = input.value.replace(/[^\d]/g, '');     // digits only
                if (v === '') { input.value = ''; return; }     // allow empty mid-edit
                let n = parseInt(v, 10);
                if (n > 7) n = 7;
                if (n < 0) n = 0;
                input.value = String(n);
            });

            // On blur, an empty field becomes 0 (so a set is never left blank)
            input.addEventListener('blur', () => {
                if (input.value.trim() === '') input.value = '0';
            });
        });
    }

    function wireProposalActions(canchaId) {
        document.querySelectorAll('[data-action="reject-proposal"]').forEach(btn => {
            btn.addEventListener('click', async () => {
                const proposalId = btn.dataset.proposalId;
                const roundId    = btn.dataset.roundId;

                const ok = await window.app.modal.confirm({
                    title: 'Rechazar propuesta',
                    body: `¿Rechazar la propuesta? No se guardará ningún marcador.`,
                    confirmText: 'Rechazar',
                    danger: true,
                });
                if (!ok) return;

                try {
                    const url = scheduleUrlTpl
                        .replace('/canchas/__ID__/schedule', `/matches/${roundId}/proposal/${proposalId}`);
                    const res = await fetch(url, {
                        method: 'DELETE',
                        headers: {
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '',
                            'Accept': 'application/json',
                        },
                        credentials: 'same-origin',
                    });
                    if (!res.ok) throw new Error('No se pudo rechazar la propuesta.');
                    window.app.toast.success('Propuesta rechazada');
                    window.app.modal.close('result-modal');
                    window.location.reload();
                } catch (err) {
                    window.app.toast.error(err.message);
                }
            });
        });
    }

    function collectFormValues() {
    // Read session-level flags once
    const sessionNoShow   = [];
    const sessionSuplente = [];
    document.querySelectorAll('#session-flags .session-player-row').forEach(row => {
        const pid = parseInt(row.dataset.playerId, 10);
        if (row.querySelector('.flag-noshow').checked)   sessionNoShow.push(pid);
        if (row.querySelector('.flag-suplente').checked) sessionSuplente.push(pid);
    });

    // Find the canonical round to attach session flags to (lowest rotation_index)
    const allRounds = currentData?.rounds ?? [];
    const flagRoundId = allRounds.length
        ? allRounds.reduce((a, b) => a.rotation_index < b.rotation_index ? a : b).id
        : null;

    // Build per-round payload. Only the flag-round gets the session arrays;
    // other rounds get empty arrays.
    const roundIds = new Set();
    document.querySelectorAll('.round-section').forEach(r => roundIds.add(r.dataset.roundId));

    const rounds = [];
    roundIds.forEach(roundId => {
        const sets = [];
        document.querySelectorAll(`.sets-list[data-round-id="${roundId}"] .set-row`).forEach(row => {
            const a = parseInt(row.querySelector('.set-a').value || '0', 10);
            const b = parseInt(row.querySelector('.set-b').value || '0', 10);
            sets.push([a, b]);
        });

        const isFlagRound = parseInt(roundId, 10) === flagRoundId;

        rounds.push({
            round_id:     parseInt(roundId, 10),
            sets,
            no_show_ids:  isFlagRound ? sessionNoShow.slice()   : [],
            suplente_ids: isFlagRound ? sessionSuplente.slice() : [],
        });
    });
    return { rounds };
}

    document.getElementById('result-save-btn')?.addEventListener('click', async () => {
        if (!currentCanchaId) return;
        const btn = document.getElementById('result-save-btn');
        window.app.loading.on(btn);
        try {
            const url = buildUrl(currentCanchaId, 'result');
            await window.app.api.put(url, collectFormValues());
            window.app.toast.success('Resultados guardados');
            window.app.modal.close('result-modal');
            window.location.reload();
        } catch (err) {
            window.app.toast.error(err.message);
        } finally {
            window.app.loading.off(btn);
        }
    });

    document.getElementById('result-clear-btn')?.addEventListener('click', async () => {
        if (!currentCanchaId) return;
        const ok = await window.app.modal.confirm({
            title: 'Borrar resultados',
            body: '¿Borrar todos los marcadores de esta cancha?',
            confirmText: 'Borrar',
            danger: true,
        });
        if (!ok) return;
        try {
            const url = buildUrl(currentCanchaId, 'result');
            const payload = {
                rounds: (currentData?.rounds ?? []).map(r => ({
                    round_id: r.id, sets: [], no_show_ids: [], suplente_ids: [],
                })),
            };
            await window.app.api.put(url, payload);
            window.app.toast.success('Resultados borrados');
            window.app.modal.close('result-modal');
            window.location.reload();
        } catch (err) {
            window.app.toast.error(err.message);
        }
    });
}

function escape(s) {
    return String(s).replace(/[&<>"']/g, c => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]));
}