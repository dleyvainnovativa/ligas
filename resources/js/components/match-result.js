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
        console.log('Building URL for canchaId', canchaId, 'and suffix', suffix);
        // schedule URL is /.../canchas/__ID__/schedule
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

        const roundsHtml = rounds.map(round => renderRound(round)).join('');

        return headerInfo + roundsHtml;
    }

    function renderRound(round) {
    // Decide which sets to pre-fill into the editable inputs:
    //   - If there's an official result, use it.
    //   - Else if there's a pending proposal, use its sets (so the manager can approve with one click).
    //   - Else a single empty row.
    let initialSets;
    if (round.sets && round.sets.length > 0) {
        initialSets = round.sets;
    } else if (round.proposal && round.proposal.sets && round.proposal.sets.length > 0) {
        initialSets = round.proposal.sets;
    } else {
        initialSets = [[0, 0]];
    }
    const setsHtml = initialSets.map((s, i) => setRow(round.id, i, s[0], s[1])).join('');

    const teamRow = (label, team) => `
        <div class="team-block">
            <div class="team-label">${label}</div>
            <ul class="list-unstyled mb-0">
                ${team.map(p => `
                    <li class="player-line" data-player-id="${p.id}" data-round-id="${round.id}">
                        <span class="player-line-name">${escape(p.name)}</span>
                        <div class="player-line-flags">
                            <label class="form-check form-check-inline m-0">
                                <input type="checkbox" class="form-check-input flag-noshow"
                                       ${round.no_show.includes(p.id) ? 'checked' : ''}>
                                <span class="form-check-label small">No show</span>
                            </label>
                            <label class="form-check form-check-inline m-0">
                                <input type="checkbox" class="form-check-input flag-suplente"
                                       ${round.suplente.includes(p.id) ? 'checked' : ''}>
                                <span class="form-check-label small">Suplente</span>
                            </label>
                        </div>
                    </li>
                `).join('')}
            </ul>
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
                <span class="round-section-badge">Ronda ${round.rotation_index}</span>
                ${round.status === 'completed' ? '<span class="badge text-bg-success">Completada</span>' : ''}
            </div>
            ${proposalBanner}
            <div class="row g-3 mb-3">
                <div class="col-md-6">${teamRow('Equipo A', round.team_a)}</div>
                <div class="col-md-6">${teamRow('Equipo B', round.team_b)}</div>
            </div>
            <div class="result-sets">
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <strong class="small">Sets</strong>
                    <button type="button" class="btn btn-sm btn-outline-secondary add-set-btn" data-round-id="${round.id}">
                        <i class="fa-solid fa-plus me-1"></i> Agregar set
                    </button>
                </div>
                <div class="sets-list" data-round-id="${round.id}">${setsHtml}</div>
            </div>
        </div>
    `;
}

    function setRow(roundId, idx, a, b) {
        return `
        <div class="set-row d-flex align-items-center gap-2 mb-2" data-round-id="${roundId}" data-i="${idx}">
            <span class="set-label small text-secondary" style="width:50px;">Set ${idx + 1}</span>
            <input type="number" min="0" max="99" class="form-control form-control-sm set-a" inputmode="numeric" value="${a}">
            <span class="text-secondary">—</span>
            <input type="number" min="0" max="99" class="form-control form-control-sm set-b" inputmode="numeric" value="${b}">
            <button type="button" class="btn btn-sm btn-link text-danger remove-set">
                <i class="fa-solid fa-xmark"></i>
            </button>
        </div>`;
    }

    function wireRowControls() {
        document.querySelectorAll('.add-set-btn').forEach(btn => {
            btn.addEventListener('click', () => {
                const roundId = btn.dataset.roundId;
                const list = document.querySelector(`.sets-list[data-round-id="${roundId}"]`);
                const idx = list.querySelectorAll('.set-row').length;
                list.insertAdjacentHTML('beforeend', setRow(roundId, idx, 0, 0));
            });
        });
        document.querySelectorAll('.sets-list').forEach(list => {
            list.addEventListener('click', (e) => {
                if (e.target.closest('.remove-set')) {
                    e.target.closest('.set-row').remove();
                    renumberSets(list);
                }
            });
        });
    }

    function renumberSets(list) {
        list.querySelectorAll('.set-row').forEach((row, i) => {
            row.querySelector('.set-label').textContent = `Set ${i + 1}`;
            row.dataset.i = i;
        });
    }

    function wireProposalActions(canchaId) {
        document.querySelectorAll('[data-action="reject-proposal"]').forEach(btn => {
            btn.addEventListener('click', async () => {
                const proposalId = btn.dataset.proposalId;
                const roundId    = btn.dataset.roundId;
                const proposerName = btn.closest('.alert').querySelector('strong')?.textContent || '';

                const ok = await window.app.modal.confirm({
                    title: 'Rechazar propuesta',
                    body: `¿Rechazar la propuesta? No se guardará ningún marcador.`,
                    confirmText: 'Rechazar',
                    danger: true,
                });
                if (!ok) return;

                try {
                    // reject endpoint is /.../matches/{match}/proposal/{proposal}
                    const baseUrl = buildUrl(canchaId, '').replace(/\/$/, '');
                    // We need the match (round) id in the URL; rebuild
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
            const noShow = [], suplente = [];
            document.querySelectorAll(`.player-line[data-round-id="${roundId}"]`).forEach(line => {
                const pid = parseInt(line.dataset.playerId, 10);
                if (line.querySelector('.flag-noshow').checked)   noShow.push(pid);
                if (line.querySelector('.flag-suplente').checked) suplente.push(pid);
            });
            rounds.push({
                round_id:     parseInt(roundId, 10),
                sets,
                no_show_ids:  noShow,
                suplente_ids: suplente,
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
            // Send rounds with empty sets to clear each
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