<?php

namespace App\Services;

use App\Models\GameMatch;
use Illuminate\Support\Facades\DB;

class MatchResultService
{
    /**
     * Save a match result.
     *
     * @param array $sets   e.g. [[6,4],[3,6],[7,5]]; pass empty array to clear.
     * @param array $noShow  player ids that didn't show
     * @param array $suplente player ids that were replacements
     */
    public function save(GameMatch $match, array $sets, array $noShow = [], array $suplente = []): GameMatch
    {
        return DB::transaction(function () use ($match, $sets, $noShow, $suplente) {
            // Sanitize sets: keep only [int,int] pairs with both >= 0
            $sets = array_values(array_filter(array_map(function ($s) {
                if (!is_array($s) || count($s) !== 2) return null;
                $a = (int) $s[0];
                $b = (int) $s[1];
                if ($a < 0 || $b < 0) return null;
                if ($a === 0 && $b === 0) return null; // empty pair
                return [$a, $b];
            }, $sets)));

            // Player-id flag arrays: only ids that participated in the match
            $participants = array_merge($match->team_a_player_ids ?? [], $match->team_b_player_ids ?? []);
            $noShow   = array_values(array_intersect($participants, array_map('intval', $noShow)));
            $suplente = array_values(array_intersect($participants, array_map('intval', $suplente)));

            $match->sets = $sets;
            $match->no_show_player_ids  = $noShow ?: null;
            $match->suplente_player_ids = $suplente ?: null;

            $winner = $match->deriveWinner();
            $match->winner = $winner;

            if (count($sets) > 0) {
                $match->status    = GameMatch::STATUS_COMPLETED;
                $match->played_at = $match->played_at ?: now();
            } else {
                // Empty sets means "clear results"; keep schedule if any
                $match->status    = $match->date ? GameMatch::STATUS_SCHEDULED : GameMatch::STATUS_UNSCHEDULED;
                $match->played_at = null;
            }

            $match->save();
            return $match->fresh();
        });
    }
}
