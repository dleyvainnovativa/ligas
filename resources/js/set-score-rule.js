// Mirror of App\Support\SetScoreRule (PHP). Keep the two in sync.
//
// Valid final set (order-independent):
//   6-0, 6-1, 6-2, 6-3, 6-4   |   7-5, 7-6
// Everything else is invalid (6-5, 6-6, 7-7, 7-0..7-4, sub-6, 8+).

export function setScoreError(a, b) {
    a = parseInt(a, 10);
    b = parseInt(b, 10);
    if (Number.isNaN(a) || Number.isNaN(b)) return 'Ingresa ambos marcadores.';
    if (a < 0 || b < 0) return 'Los marcadores no pueden ser negativos.';

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

export function isValidSet(a, b) {
    return setScoreError(a, b) === null;
}

// Treats [0,0] as "not entered" (skipped). Returns array of
// { index, message } for invalid sets; empty means all good.
export function validateSets(sets) {
    const errors = [];
    sets.forEach((s, index) => {
        const a = parseInt(s[0], 10) || 0;
        const b = parseInt(s[1], 10) || 0;
        if (a === 0 && b === 0) return; // placeholder
        const message = setScoreError(a, b);
        if (message) errors.push({ index, message });
    });
    return errors;
}