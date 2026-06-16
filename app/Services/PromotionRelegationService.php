<?php

namespace App\Services;

use App\Models\Cancha;
use App\Models\GameMatch;
use App\Models\Group;
use App\Models\Jornada;
use Illuminate\Support\Collection;

class PromotionRelegationService
{
    /**
     * Rank the players within a single cancha based on its completed rounds.
     * Returns an ordered array of player IDs, best first.
     *
     * Ranking: total games won across all rounds, ties broken by games
     * difference (games won − games lost).
     */
    public function rankCancha(Cancha $cancha): array
    {
        $cancha->loadMissing('rounds');

        $stats = []; // playerId => ['won' => x, 'lost' => y]

        foreach ($cancha->rounds as $round) {
            $teamA = $round->team_a_player_ids ?? [];
            $teamB = $round->team_b_player_ids ?? [];
            $tally = $round->tally(); // ['games_a', 'games_b', ...]

            $gamesA = $tally['games_a'] ?? 0;
            $gamesB = $tally['games_b'] ?? 0;

            foreach ($teamA as $pid) {
                $stats[$pid]['won']  = ($stats[$pid]['won']  ?? 0) + $gamesA;
                $stats[$pid]['lost'] = ($stats[$pid]['lost'] ?? 0) + $gamesB;
            }
            foreach ($teamB as $pid) {
                $stats[$pid]['won']  = ($stats[$pid]['won']  ?? 0) + $gamesB;
                $stats[$pid]['lost'] = ($stats[$pid]['lost'] ?? 0) + $gamesA;
            }
        }

        // Build sortable rows
        $rows = collect($stats)->map(fn($s, $pid) => [
            'player_id' => (int) $pid,
            'won'       => $s['won'] ?? 0,
            'lost'      => $s['lost'] ?? 0,
            'diff'      => ($s['won'] ?? 0) - ($s['lost'] ?? 0),
        ])->values();

        // Sort: games won desc, then diff desc
        $sorted = $rows->sortByDesc(fn($r) => [$r['won'], $r['diff']])->values();

        return $sorted->pluck('player_id')->all();
    }

    /**
     * Given a completed jornada, compute the player distribution for the next
     * jornada's canchas using promotion/relegation rules.
     *
     * Returns an ordered array of canchas, each a list of player IDs:
     *   [ [p1,p2,p3,p4], [p5,p6,p7,p8], ... ]
     * Index 0 = Cancha 1 (top court).
     */
    public function computeNextDistribution(Jornada $jornada, int $movement): array
    {
        $jornada->loadMissing(['canchas.rounds']);

        // Canchas ordered by their position (Cancha 1 = top). We use the
        // cancha's position/label ordering; fall back to id.
        $canchas = $jornada->canchas
            ->sortBy(fn($c) => $c->position ?? $c->id)
            ->values();

        if ($canchas->isEmpty()) return [];

        // Rank each cancha's players (best-first)
        $ranked = $canchas->map(fn($c) => $this->rankCancha($c))->all();
        $n = count($ranked);

        // Build the next distribution. Start each next-cancha as an empty list.
        $next = array_fill(0, $n, []);

        foreach ($ranked as $i => $players) {
            $size = count($players);
            // Clamp movement so it can't exceed what's reasonable for this cancha
            $m = min($movement, intdiv($size, 2));

            foreach ($players as $rank => $pid) {
                // rank is 0-based: 0 = best in this cancha
                $isTop    = $rank < $m;
                $isBottom = $rank >= ($size - $m);

                if ($isTop && $i > 0) {
                    // promote up to cancha i-1
                    $next[$i - 1][] = $pid;
                } elseif ($isBottom && $i < $n - 1) {
                    // relegate down to cancha i+1
                    $next[$i + 1][] = $pid;
                } else {
                    // stay (includes edge canchas where there's nowhere to move)
                    $next[$i][] = $pid;
                }
            }
        }

        return $next;
    }

    /**
     * Detect whether a movement value would produce "weird" movement for a
     * given cancha size (everyone moves, or overlapping promote/relegate sets).
     * Returns a human-readable warning string, or null if fine.
     */
    public function movementWarning(int $movement, int $canchaSize): ?string
    {
        if ($canchaSize <= 0) return null;
        $half = intdiv($canchaSize, 2);

        if ($movement > $half) {
            return "Con canchas de {$canchaSize} jugadores y ascenso/descenso de {$movement}, "
                . "los grupos de ascenso y descenso se traslapan. Se ajustará automáticamente a {$half}.";
        }
        if ($movement === $half && $canchaSize % 2 === 0) {
            return "Con ascenso/descenso de {$movement} en canchas de {$canchaSize}, "
                . "todos los jugadores suben o bajan (no hay jugadores que se queden).";
        }
        return null;
    }
    /**
     * Build a per-cancha, per-player breakdown of a jornada's results, annotated
     * with forward-looking movement (up / down / stay) for the NEXT jornada.
     *
     * Returns an ordered array (Cancha 1 first):
     * [
     *   [
     *     'cancha_id' => 12,
     *     'label'     => 'Cancha 1',
     *     'position'  => 1,
     *     'is_top'    => true,
     *     'is_bottom' => false,
     *     'players'   => [
     *       ['player_id'=>5,'rank'=>1,'won'=>18,'lost'=>9,'diff'=>9,'movement'=>'stay'],
     *       ...
     *     ],
     *   ],
     *   ...
     * ]
     */
    public function jornadaBreakdown(Jornada $jornada, int $movement): array
    {
        $jornada->loadMissing(['canchas.rounds', 'canchas.players']);

        $canchas = $jornada->canchas
            ->sortBy(fn($c) => $c->position ?? $c->id)
            ->values();

        if ($canchas->isEmpty()) return [];

        $n = $canchas->count();
        $out = [];

        foreach ($canchas as $i => $cancha) {
            // Per-player stats for this cancha (won/lost across the 3 rounds)
            $stats = $this->canchaPlayerStats($cancha);

            // Order best-first: won desc, then diff desc
            $ordered = collect($stats)
                ->sortByDesc(fn($s) => [$s['won'], $s['diff']])
                ->values();

            $size = $ordered->count();
            $m = min($movement, intdiv($size, 2));

            $players = $ordered->map(function ($s, $rankIdx) use ($i, $n, $size, $m) {
                $isTop    = $rankIdx < $m;
                $isBottom = $rankIdx >= ($size - $m);

                // Forward-looking movement, honoring edge canchas
                $movement = 'stay';
                if ($isTop && $i > 0) {
                    $movement = 'up';
                } elseif ($isBottom && $i < $n - 1) {
                    $movement = 'down';
                }

                return [
                    'player_id' => $s['player_id'],
                    'rank'      => $rankIdx + 1,
                    'won'       => $s['won'],
                    'lost'      => $s['lost'],
                    'diff'      => $s['diff'],
                    'movement'  => $movement,
                ];
            })->all();

            $out[] = [
                'cancha_id' => $cancha->id,
                'label'     => $cancha->label,
                'position'  => $cancha->position ?? ($i + 1),
                'is_top'    => $i === 0,
                'is_bottom' => $i === $n - 1,
                'players'   => $players,
            ];
        }

        return $out;
    }

    /**
     * Per-player won/lost games for one cancha across all its rounds.
     * Returns playerId => ['player_id','won','lost','diff'].
     */
    private function canchaPlayerStats(Cancha $cancha): array
    {
        $cancha->loadMissing('rounds');
        $stats = [];

        foreach ($cancha->rounds as $round) {
            $teamA = $round->team_a_player_ids ?? [];
            $teamB = $round->team_b_player_ids ?? [];
            $tally = $round->tally();
            $gamesA = $tally['games_a'] ?? 0;
            $gamesB = $tally['games_b'] ?? 0;

            foreach ($teamA as $pid) {
                $stats[$pid]['won']  = ($stats[$pid]['won']  ?? 0) + $gamesA;
                $stats[$pid]['lost'] = ($stats[$pid]['lost'] ?? 0) + $gamesB;
            }
            foreach ($teamB as $pid) {
                $stats[$pid]['won']  = ($stats[$pid]['won']  ?? 0) + $gamesB;
                $stats[$pid]['lost'] = ($stats[$pid]['lost'] ?? 0) + $gamesA;
            }
        }

        $out = [];
        foreach ($stats as $pid => $s) {
            $won = $s['won'] ?? 0;
            $lost = $s['lost'] ?? 0;
            $out[$pid] = [
                'player_id' => (int) $pid,
                'won'       => $won,
                'lost'      => $lost,
                'diff'      => $won - $lost,
            ];
        }
        return $out;
    }
}
