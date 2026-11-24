// Mirror of App\Support\SetScoreRule (PHP). Keep the two in sync.
//
// padel / tennis — valid final set: 6-0..6-4, 7-5, 7-6 (max 7).
// pickleball     — valid final game: to 11, win by 2 (11-0..11-9, then
//                  12-10, 13-11, … in deuce), capped at 21.

export function maxScore(sport) {
    return sport === 'pickleball' ? 21 : 7;
}

export function setScoreError(a, b, sport) {
    a = parseInt(a, 10);
    b = parseInt(b, 10);
    if (Number.isNaN(a) || Number.isNaN(b)) return 'Ingresa ambos marcadores.';
    if (a < 0 || b < 0) return 'Los marcadores no pueden ser negativos.';

    return sport === 'pickleball' ? errorPickleball(a, b) : errorTennis(a, b);
}

function errorTennis(a, b) {
    const hi = Math.max(a, b);
    const lo = Math.min(a, b);

    if (hi === lo) return 'Un set no puede terminar empatado.';
    if (hi === 6) {
        if (lo <= 4) return null;
        return 'Un set 6-5 no es final: debe terminar 7-5 o 7-6.';
    }
    if (hi === 7) {
        if (lo === 5 || lo === 6) return null;
        return 'Un 7 solo es válido como 7-5 o 7-6.';
    }
    if (hi < 6) return 'Un set debe llegar a 6 (o 7 en desempate).';
    return 'Marcador inválido: el máximo por set es 7.';
}

function errorPickleball(a, b) {
    const hi = Math.max(a, b);
    const lo = Math.min(a, b);

    if (hi === lo) return 'Un juego no puede terminar empatado.';
    if (hi < 11) return 'Un juego debe llegar a 11.';
    if (hi > 21) return 'Marcador inválido: el máximo por juego es 21.';
    if (hi === 11) {
        if (lo <= 9) return null;
        return 'Un juego 11-10 no es final: se gana por 2 (12-10).';
    }
    // 12..21 → win by exactly 2.
    if (hi - lo === 2) return null;
    return 'Con 12 o más, el juego se gana por exactamente 2 (ej. 12-10, 13-11).';
}

export function isValidSet(a, b, sport) {
    return setScoreError(a, b, sport) === null;
}

// Treats [0,0] as "not entered" (skipped). Returns array of
// { index, message } for invalid sets; empty means all good.
export function validateSets(sets, sport) {
    const errors = [];
    sets.forEach((s, index) => {
        const a = parseInt(s[0], 10) || 0;
        const b = parseInt(s[1], 10) || 0;
        if (a === 0 && b === 0) return; // placeholder
        const message = setScoreError(a, b, sport);
        if (message) errors.push({ index, message });
    });
    return errors;
}
