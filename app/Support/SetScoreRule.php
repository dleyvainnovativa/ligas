<?php

namespace App\Support;

/**
 * Canonical set/game-score rule. Single source of truth for the server side;
 * the JS mirror in resources/js/set-score-rule.js must stay in sync.
 *
 * Sport-aware:
 *   padel / tennis — a valid FINAL set is 6-0..6-4, 7-5, 7-6 (max 7).
 *   pickleball     — a valid FINAL game is to 11, win by 2 (11-0..11-9, then
 *                    12-10, 13-11, … in deuce), capped at 21.
 */
class SetScoreRule
{
    public const SPORT_PADEL      = 'padel';
    public const SPORT_TENNIS     = 'tennis';
    public const SPORT_PICKLEBALL = 'pickleball';

    /** Highest score a single set/game input should allow, by sport. */
    public static function maxScore(?string $sport = null): int
    {
        return $sport === self::SPORT_PICKLEBALL ? 21 : 7;
    }

    public static function isValid(int $a, int $b, ?string $sport = null): bool
    {
        return self::error($a, $b, $sport) === null;
    }

    /**
     * Returns null when valid, or a Spanish error message. Order-independent.
     */
    public static function error(int $a, int $b, ?string $sport = null): ?string
    {
        if ($a < 0 || $b < 0) {
            return 'Los marcadores no pueden ser negativos.';
        }

        return $sport === self::SPORT_PICKLEBALL
            ? self::errorPickleball($a, $b)
            : self::errorTennis($a, $b);
    }

    /** Padel / tennis: 6-0..6-4, 7-5, 7-6. */
    private static function errorTennis(int $a, int $b): ?string
    {
        $hi = max($a, $b);
        $lo = min($a, $b);

        if ($hi === $lo) {
            return 'Un set no puede terminar empatado.';
        }
        if ($hi === 6) {
            return $lo <= 4 ? null : 'Un set 6-5 no es final: debe terminar 7-5 o 7-6.';
        }
        if ($hi === 7) {
            return ($lo === 5 || $lo === 6) ? null : 'Un 7 solo es válido como 7-5 o 7-6.';
        }
        if ($hi < 6) {
            return 'Un set debe llegar a 6 (o 7 en desempate).';
        }
        return 'Marcador inválido: el máximo por set es 7.';
    }

    /** Pickleball: game to 11, win by 2, capped at 21. */
    private static function errorPickleball(int $a, int $b): ?string
    {
        $hi = max($a, $b);
        $lo = min($a, $b);

        if ($hi === $lo) {
            return 'Un juego no puede terminar empatado.';
        }
        if ($hi < 11) {
            return 'Un juego debe llegar a 11.';
        }
        if ($hi > 21) {
            return 'Marcador inválido: el máximo por juego es 21.';
        }
        if ($hi === 11) {
            return $lo <= 9 ? null : 'Un juego 11-10 no es final: se gana por 2 (12-10).';
        }
        // 12..21 → must be won by exactly 2 (deuce).
        return ($hi - $lo === 2)
            ? null
            : 'Con 12 o más, el juego se gana por exactamente 2 (ej. 12-10, 13-11).';
    }

    /**
     * Validate a list of sets (each [a, b]). Returns ['index','message'] for
     * every invalid set; empty [0,0] pairs should be filtered by the caller.
     */
    public static function validateSets(array $sets, ?string $sport = null): array
    {
        $errors = [];
        foreach ($sets as $i => $s) {
            if (!is_array($s) || count($s) !== 2) {
                $errors[] = ['index' => $i, 'message' => 'Set con formato inválido.'];
                continue;
            }
            $msg = self::error((int) $s[0], (int) $s[1], $sport);
            if ($msg !== null) {
                $errors[] = ['index' => $i, 'message' => $msg];
            }
        }
        return $errors;
    }
}
