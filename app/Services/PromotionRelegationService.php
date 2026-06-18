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
    /**
     * Reconstruct a single player's journey across all jornadas in their group(s).
     *
     * Returns:
     * [
     *   'history' => [
     *     ['jornada'=>1, 'cancha_label'=>'Cancha 3', 'cancha_position'=>3,
     *      'rank'=>1, 'won'=>20, 'lost'=>13, 'diff'=>7, 'movement'=>'up', 'complete'=>true],
     *     ...
     *   ],
     *   'totals' => ['won'=>.., 'lost'=>.., 'diff'=>.., 'jornadas_played'=>..,
     *                'best_position'=>.., 'current_position'=>..],
     * ]
     */
    public function playerHistory(\App\Models\Player $player, int $movement): array
    {
        $league = $player->league;

        // Find every (group, jornada) where this player was assigned to a cancha
        $history = [];

        foreach ($league->groups as $group) {
            foreach ($group->jornadas->sortBy('number') as $jornada) {
                // Is the player in any cancha of this jornada?
                $cancha = $jornada->canchas()
                    ->whereHas('players', fn($q) => $q->where('players.id', $player->id))
                    ->with('rounds')
                    ->first();

                if (!$cancha) continue;

                $breakdown = $this->jornadaBreakdown($jornada, $movement);

                // Find this cancha + this player's row in the breakdown
                $canchaBreakdown = collect($breakdown)->firstWhere('cancha_id', $cancha->id);
                if (!$canchaBreakdown) continue;

                $row = collect($canchaBreakdown['players'])->firstWhere('player_id', $player->id);
                if (!$row) continue;

                $complete = $jornada->canchas()->count() > 0
                    && !$jornada->canchas()->where('status', '!=', \App\Models\Cancha::STATUS_COMPLETED)->exists();

                $history[] = [
                    'jornada'         => $jornada->number,
                    'group_name'      => $group->name,
                    'cancha_label'    => $canchaBreakdown['label'],
                    'cancha_position' => $canchaBreakdown['position'],
                    'rank'            => $row['rank'],
                    'won'             => $row['won'],
                    'lost'            => $row['lost'],
                    'diff'            => $row['diff'],
                    'movement'        => $complete ? $row['movement'] : null,
                    'complete'        => $complete,
                ];
            }
        }

        // Totals
        $totalWon  = collect($history)->sum('won');
        $totalLost = collect($history)->sum('lost');
        $positions = collect($history)->pluck('cancha_position')->filter();

        return [
            'history' => $history,
            'totals'  => [
                'won'              => $totalWon,
                'lost'             => $totalLost,
                'diff'             => $totalWon - $totalLost,
                'jornadas_played'  => count($history),
                'best_position'    => $positions->min(),   // lowest number = highest court
                'current_position' => $positions->isNotEmpty() ? collect($history)->last()['cancha_position'] : null,
            ],
        ];
    }

    /**
     * Cumulative season standings for a group, ranked the king-of-the-court way:
     * by current cancha position (lower = higher court = better), then by total
     * games won, then by games difference.
     *
     * Returns an ordered array (best first):
     * [
     *   ['player_id'=>5, 'name'=>'Juan', 'won'=>54, 'lost'=>33, 'diff'=>21,
     *    'jornadas_played'=>3, 'current_position'=>1, 'current_cancha'=>'Cancha 1'],
     *   ...
     * ]
     */
    public function groupSeasonStandings(Group $group, int $movement, $playerNames = null): array
    {
        $playerNames ??= $group->league->players()->pluck('full_name', 'id');

        // Aggregate each player's games across every jornada in this group
        $agg = []; // player_id => ['won','lost','jornadas','last_jornada','last_position','last_label']

        foreach ($group->jornadas->sortBy('number') as $jornada) {
            $breakdown = $this->jornadaBreakdown($jornada, $movement);

            foreach ($breakdown as $cancha) {
                foreach ($cancha['players'] as $p) {
                    $pid = $p['player_id'];
                    if (!isset($agg[$pid])) {
                        $agg[$pid] = [
                            'won' => 0,
                            'lost' => 0,
                            'jornadas' => 0,
                            'last_jornada' => 0,
                            'last_position' => null,
                            'last_label' => null,
                        ];
                    }
                    $agg[$pid]['won']  += $p['won'];
                    $agg[$pid]['lost'] += $p['lost'];
                    $agg[$pid]['jornadas']++;

                    // Track the most recent jornada's cancha as "current"
                    if ($jornada->number >= $agg[$pid]['last_jornada']) {
                        $agg[$pid]['last_jornada']  = $jornada->number;
                        $agg[$pid]['last_position'] = $cancha['position'];
                        $agg[$pid]['last_label']    = $cancha['label'];
                    }
                }
            }
        }

        // Build rows
        $rows = [];
        foreach ($agg as $pid => $a) {
            $rows[] = [
                'player_id'        => $pid,
                'name'             => $playerNames[$pid] ?? '—',
                'won'              => $a['won'],
                'lost'             => $a['lost'],
                'diff'             => $a['won'] - $a['lost'],
                'jornadas_played'  => $a['jornadas'],
                'current_position' => $a['last_position'],
                'current_cancha'   => $a['last_label'],
            ];
        }

        // Rank: current cancha asc (1 = top), then games won desc, then diff desc.
        // Players with no position (never assigned) sink to the bottom.
        usort($rows, function ($a, $b) {
            $posA = $a['current_position'] ?? PHP_INT_MAX;
            $posB = $b['current_position'] ?? PHP_INT_MAX;
            if ($posA !== $posB) return $posA <=> $posB;
            if ($a['won'] !== $b['won']) return $b['won'] <=> $a['won'];
            return $b['diff'] <=> $a['diff'];
        });

        return $rows;
    }
}
