export function mountMatchResult() {
    const grid = document.querySelector('.grid-app');
    if (!grid) return;

    const showBase = window.location.pathname; // /.../grid — close enough; we'll build the URL from data attrs

    let currentMatchId = null;
    let currentMatch   = null;

    // Open modal when pencil button clicked
    document.body.addEventListener('click', async (e) => {
        const btn = e.target.closest('.cell-match-result');
        console.log(btn);
        if (!btn) return;
        e.stopPropagation();
        currentMatchId = btn.dataset.matchId;
        await openResultModal(currentMatchId);
    });

    async function openResultModal(matchId) {
        const url = buildUrl(matchId, 'result');
        const body = document.getElementById('result-modal-body');
        body.innerHTML = `<div class="text-center py-4 text-secondary"><span class="spinner-border spinner-border-sm me-2"></span>Cargando…</div>`;
        window.app.modal.open('result-modal');
        try {
            const data = await window.app.api.get(url);
            currentMatch = data.match;
            const proposal = data.proposal;

            // Pre-fill modal with proposal if pending and no official sets yet
            if (proposal && (!currentMatch.sets || currentMatch.sets.length === 0)) {
                currentMatch.sets = proposal.sets;
            }

            body.innerHTML = renderForm(currentMatch, proposal);
            wireRowControls();
            wireProposalActions(matchId, proposal);
        } catch (err) {
            body.innerHTML = `<div class="alert alert-danger mb-0">${err.message}</div>`;
        }
    }

    function buildUrl(matchId, suffix) {
        // The grid view dropped two URL templates; we'll reuse the schedule one's structure
        const tpl = grid.dataset.scheduleUrlTemplate; // .../matches/__ID__/schedule
        return tpl.replace('/schedule', `/${suffix}`).replace('__ID__', matchId);
    }

    function renderForm(m, proposal) {
    const sets = (m.sets && m.sets.length) ? m.sets : [[0, 0]];
    const setsHtml = sets.map((s, i) => setRow(i, s[0], s[1])).join('');

    const teamRow = (label, team) => `
        <div class="team-block">
            <div class="team-label">${label}</div>
            <ul class="list-unstyled mb-0">
                ${team.map(p => `
                    <li class="player-line" data-player-id="${p.id}">
                        <span class="player-line-name">${escape(p.name)}</span>
                        <div class="player-line-flags">
                            <label class="form-check form-check-inline m-0">
                                <input type="checkbox" class="form-check-input flag-noshow"
                                       ${m.no_show.includes(p.id) ? 'checked' : ''}>
                                <span class="form-check-label small">No show</span>
                            </label>
                            <label class="form-check form-check-inline m-0">
                                <input type="checkbox" class="form-check-input flag-suplente"
                                       ${m.suplente.includes(p.id) ? 'checked' : ''}>
                                <span class="form-check-label small">Suplente</span>
                            </label>
                        </div>
                    </li>
                `).join('')}
            </ul>
        </div>`;

    const proposalBanner = proposal ? `
        <div class="alert alert-warning d-flex align-items-start gap-2 mb-3" role="alert">
            <i class="fa-solid fa-clock-rotate-left mt-1"></i>
            <div class="flex-grow-1">
                <strong>Propuesta pendiente</strong> de
                <strong>${escape(proposal.proposer_name)}</strong>
                · ${escape(proposal.created_at)}
                <div class="small mt-1">
                    Sets propuestos:
                    ${proposal.sets.map(s => `<span class="font-mono">${s[0]}–${s[1]}</span>`).join(', ')}
                </div>
                <div class="small mt-2 text-muted">
                    Se preseleccionaron los marcadores en este formulario.
                    Al guardar, la propuesta se marcará como <em>aceptada</em> (si coinciden) o <em>modificada</em>.
                </div>
            </div>
            <button type="button" class="btn btn-sm btn-outline-danger" id="reject-proposal-btn"
                    data-proposal-id="${proposal.id}">
                Rechazar
            </button>
        </div>` : '';

    return `
        ${proposalBanner}

        <div class="d-flex justify-content-between align-items-start mb-3 small text-secondary">
            <div>
                Rotación <strong>R${m.rotation_index}</strong>
                ${m.date ? ` · ${m.date} ${m.time_slot ?? ''}` : ''}
                ${m.pista ? ` · ${escape(m.pista)}` : ''}
            </div>
            <div>Estado: <strong>${escape(m.status)}</strong></div>
        </div>

        <div class="row g-3 mb-3">
            <div class="col-md-6">${teamRow('Equipo A', m.team_a)}</div>
            <div class="col-md-6">${teamRow('Equipo B', m.team_b)}</div>
        </div>

        <div class="result-sets">
            <div class="d-flex justify-content-between align-items-center mb-2">
                <strong>Sets</strong>
                <button type="button" class="btn btn-sm btn-outline-secondary" id="add-set-btn">
                    <i class="fa-solid fa-plus me-1"></i> Agregar set
                </button>
            </div>
            <div id="sets-list">${setsHtml}</div>
            <small class="text-secondary d-block mt-2">
                Si dejas todos los sets en 0, se elimina el resultado.
            </small>
        </div>
    `;
}

    function setRow(idx, a, b) {
        return `
        <div class="set-row d-flex align-items-center gap-2 mb-2">
            <span class="set-label small text-secondary" style="width:50px;">Set ${idx + 1}</span>
            <input type="number" min="0" max="99" class="form-control form-control-sm set-a" value="${a}">
            <span class="text-secondary">—</span>
            <input type="number" min="0" max="99" class="form-control form-control-sm set-b" value="${b}">
            <button type="button" class="btn btn-sm btn-link text-danger remove-set">
                <i class="fa-solid fa-xmark"></i>
            </button>
        </div>`;
    }

    function wireProposalActions(matchId, proposal) {
    if (!proposal) return;
    const btn = document.getElementById('reject-proposal-btn');
    if (!btn) return;

    btn.addEventListener('click', async () => {
        if (!confirm(`¿Rechazar la propuesta de ${proposal.proposer_name}? No se guardará ningún marcador.`)) return;
        try {
            const url = buildUrl(matchId, 'result').replace('/result', `/proposal/${proposal.id}`);
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
}

    function wireRowControls() {
        document.getElementById('add-set-btn')?.addEventListener('click', () => {
            const list = document.getElementById('sets-list');
            const idx = list.querySelectorAll('.set-row').length;
            list.insertAdjacentHTML('beforeend', setRow(idx, 0, 0));
        });
        document.getElementById('sets-list')?.addEventListener('click', (e) => {
            if (e.target.closest('.remove-set')) {
                e.target.closest('.set-row').remove();
                renumberSets();
            }
        });
    }

    function renumberSets() {
        document.querySelectorAll('#sets-list .set-row').forEach((row, i) => {
            row.querySelector('.set-label').textContent = `Set ${i + 1}`;
        });
    }

    function collectFormValues() {
        const sets = [];
        document.querySelectorAll('#sets-list .set-row').forEach(row => {
            const a = parseInt(row.querySelector('.set-a').value || '0', 10);
            const b = parseInt(row.querySelector('.set-b').value || '0', 10);
            sets.push([a, b]);
        });
        const noShow = [];
        const suplente = [];
        document.querySelectorAll('.player-line').forEach(line => {
            const pid = parseInt(line.dataset.playerId, 10);
            if (line.querySelector('.flag-noshow').checked) noShow.push(pid);
            if (line.querySelector('.flag-suplente').checked) suplente.push(pid);
        });
        return { sets, no_show_ids: noShow, suplente_ids: suplente };
    }

    document.getElementById('result-save-btn')?.addEventListener('click', async () => {
        if (!currentMatchId) return;
        const btn = document.getElementById('result-save-btn');
        window.app.loading.on(btn);
        try {
            const url = buildUrl(currentMatchId, 'result');
            await window.app.api.put(url, collectFormValues());
            window.app.toast.success('Resultado guardado');
            window.app.modal.close('result-modal');
            window.location.reload();
        } catch (err) {
            window.app.toast.error(err.message);
        } finally {
            window.app.loading.off(btn);
        }
    });

    document.getElementById('result-clear-btn')?.addEventListener('click', async () => {
        if (!currentMatchId) return;
        if (!confirm('¿Borrar el resultado de este partido?')) return;
        try {
            const url = buildUrl(currentMatchId, 'result');
            await window.app.api.put(url, { sets: [] });
            window.app.toast.success('Resultado borrado');
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