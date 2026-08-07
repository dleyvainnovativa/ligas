<?php

namespace App\Support;

/**
 * Canonical padel/tennis set-score rule. Single source of truth for the
 * server side; the JS mirror in resources/js/set-score-rule.js must stay
 * in sync with this logic.
 *
 * A valid FINAL set score is exactly one of (order-independent):
 *   6-0, 6-1, 6-2, 6-3, 6-4      (won at 6, loser 0..4)
 *   7-5, 7-6                     (won at 7, loser 5 or 6)
 *
 * Everything else is invalid as a final set:
 *   6-5  → not final (goes to 7-5 or 6-6→7-6)
 *   6-6  → not final (tiebreak resolves to 7-6)
 *   7-7, 7-0..7-4, 8+ anything, and any sub-6 result like 4-2
 */
class SetScoreRule
{
    /** Returns true if [a, b] is a valid final set score, in either order. */
    public static function isValid(int $a, int $b): bool
    {
        return self::error($a, $b) === null;
    }

    /**
     * Returns null when valid, or a Spanish error message describing why the
     * score is not a valid final set. Message is order-independent.
     */
    public static function error(int $a, int $b): ?string
    {
        if ($a < 0 || $b < 0) {
            return 'Los marcadores no pueden ser negativos.';
        }

        $hi = max($a, $b);
        $lo = min($a, $b);

        // No winner recorded.
        if ($hi === $lo) {
            return 'Un set no puede terminar empatado.';
        }

        // Winner scored 6: loser must be 0..4 (6-5 is not a final set).
        if ($hi === 6) {
            if ($lo <= 4) {
                return null;
            }
            // lo === 5
            return 'Un set 6-5 no es final: debe terminar 7-5 o 7-6.';
        }

        // Winner scored 7: loser must be 5 or 6.
        if ($hi === 7) {
            if ($lo === 5 || $lo === 6) {
                return null;
            }
            return 'Un 7 solo es válido como 7-5 o 7-6.';
        }

        // Anything under 6 as the top score is an unfinished set.
        if ($hi < 6) {
            return 'Un set debe llegar a 6 (o 7 en desempate).';
        }

        // hi > 7
        return 'Marcador inválido: el máximo por set es 7.';
    }

    /**
     * Validate a list of sets (each [a, b]). Returns an array of
     * ['index' => int, 'message' => string] for every invalid set; empty
     * array means all sets are valid. Empty [0,0] pairs are treated as
     * "not entered" and skipped by the caller before this point.
     */
    public static function validateSets(array $sets): array
    {
        $errors = [];
        foreach ($sets as $i => $s) {
            if (!is_array($s) || count($s) !== 2) {
                $errors[] = ['index' => $i, 'message' => 'Set con formato inválido.'];
                continue;
            }
            $msg = self::error((int) $s[0], (int) $s[1]);
            if ($msg !== null) {
                $errors[] = ['index' => $i, 'message' => $msg];
            }
        }
        return $errors;
    }
}
