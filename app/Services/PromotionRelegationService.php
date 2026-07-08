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
        $chain = $cancha->jornada->group->league->standingsOrder();
        $stats = array_values($this->canchaPlayerStats($cancha));
        $sorted = $this->sortByChain($stats, $chain);
        return array_map(fn($r) => $r['player_id'], $sorted);
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
        $jornada->loadMissing(['canchas.rounds', 'canchas.players', 'group.league']);
        $chain = $jornada->group->league->standingsOrder();

        $canchas = $jornada->canchas
            ->sortBy(fn($c) => $c->position ?? $c->id)
            ->values();

        if ($canchas->isEmpty()) return [];

        $n = $canchas->count();
        $out = [];

        foreach ($canchas as $i => $cancha) {
            $stats = array_values($this->canchaPlayerStats($cancha));
            $ordered = $this->sortByChain($stats, $chain);   // ← was sortByDesc([...])

            $size = count($ordered);
            $m = min($movement, intdiv($size, 2));

            $players = [];
            foreach ($ordered as $rankIdx => $s) {
                $isTop    = $rankIdx < $m;
                $isBottom = $rankIdx >= ($size - $m);

                $mv = 'stay';
                if ($isTop && $i > 0) {
                    $mv = 'up';
                } elseif ($isBottom && $i < $n - 1) {
                    $mv = 'down';
                }

                $players[] = [
                    'player_id' => $s['player_id'],
                    'rank'      => $rankIdx + 1,
                    'won'       => $s['won'],
                    'lost'      => $s['lost'],
                    'diff'      => $s['diff'],
                    'rounds'    => $s['rounds'],   // ← now available for display too
                    'penalty'   => $s['penalty'] ?? 0,           // ← must be here
                    'movement'  => $mv,
                ];
            }

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
        $cancha->loadMissing(['rounds', 'jornada.group.league']);
        $league = $cancha->jornada->group->league;

        $penaltyNoShow   = (int) ($league->penalty_no_show   ?? 0);
        $penaltySuplente = (int) ($league->penalty_suplente ?? 0);

        $stats = [];

        // Track which players we've already penalized in THIS cancha, so a flag
        // (stored on a single round) is only ever applied once per cancha.
        $penalized = [];

        foreach ($cancha->rounds as $round) {
            $teamA = $round->team_a_player_ids ?? [];
            $teamB = $round->team_b_player_ids ?? [];
            $tally = $round->tally();
            $gamesA = $tally['games_a'] ?? 0;
            $gamesB = $tally['games_b'] ?? 0;

            $aWon = $gamesA > $gamesB;
            $bWon = $gamesB > $gamesA;

            foreach ($teamA as $pid) {
                $stats[$pid]['won']    = ($stats[$pid]['won']    ?? 0) + $gamesA;
                $stats[$pid]['lost']   = ($stats[$pid]['lost']   ?? 0) + $gamesB;
                $stats[$pid]['rounds'] = ($stats[$pid]['rounds'] ?? 0) + ($aWon ? 1 : 0);
            }
            foreach ($teamB as $pid) {
                $stats[$pid]['won']    = ($stats[$pid]['won']    ?? 0) + $gamesB;
                $stats[$pid]['lost']   = ($stats[$pid]['lost']   ?? 0) + $gamesA;
                $stats[$pid]['rounds'] = ($stats[$pid]['rounds'] ?? 0) + ($bWon ? 1 : 0);
            }

            // Apply penalties recorded on this round (flags live on one round only)
            $noShowIds   = $round->no_show_player_ids   ?? [];
            $suplenteIds = $round->suplente_player_ids ?? [];

            foreach ($noShowIds as $pid) {
                if (!isset($penalized[$pid]['no_show'])) {
                    $stats[$pid]['penalty'] = ($stats[$pid]['penalty'] ?? 0) + $penaltyNoShow;
                    $penalized[$pid]['no_show'] = true;
                }
            }
            foreach ($suplenteIds as $pid) {
                if (!isset($penalized[$pid]['suplente'])) {
                    $stats[$pid]['penalty'] = ($stats[$pid]['penalty'] ?? 0) + $penaltySuplente;
                    $penalized[$pid]['suplente'] = true;
                }
            }
        }

        $out = [];
        foreach ($stats as $pid => $s) {
            $rawWon  = $s['won']     ?? 0;
            $lost    = $s['lost']    ?? 0;
            $penalty = $s['penalty'] ?? 0;

            // Subtract penalty from games won. Clamp at 0 so a heavy penalty can't
            // make "won" negative (which would look absurd in the table).
            $won = max(0, $rawWon - $penalty);


            $out[$pid] = [
                'player_id'   => (int) $pid,
                'won'         => $won,               // penalty already applied
                'won_raw'     => $rawWon,            // pre-penalty, for display if wanted
                'lost'        => $lost,
                'diff'        => $won - $lost,        // flows from penalized "won"
                'rounds'      => $s['rounds'] ?? 0,
                'penalty'     => $penalty,           // how much was subtracted
            ];
        }
        return $out;
    }

    /**
     * Compare two player stat-rows by the league's configured tiebreaker chain.
     * Returns negative if $a ranks higher, positive if $b ranks higher, 0 if fully tied.
     * (Follows the usort/spaceship convention: negative = $a first.)
     *
     * @param array $a  stat row with keys: won, lost, diff, rounds
     * @param array $b  same shape
     * @param array $chain  ordered metric keys, e.g. ['diff','won','rounds']
     */
    public function comparePlayers(array $a, array $b, array $chain): int
    {
        foreach ($chain as $metric) {
            // Higher is better for all current metrics → $b <=> $a for descending
            $cmp = match ($metric) {
                'diff'   => ($b['diff']   ?? 0) <=> ($a['diff']   ?? 0),
                'won'    => ($b['won']    ?? 0) <=> ($a['won']    ?? 0),
                'rounds' => ($b['rounds'] ?? 0) <=> ($a['rounds'] ?? 0),
                default  => 0,
            };
            if ($cmp !== 0) return $cmp;
        }
        return 0;
    }

    /**
     * Sort a collection/array of player stat-rows (best first) by the chain.
     * Returns a plain re-indexed array.
     */
    public function sortByChain(array $rows, array $chain): array
    {
        usort($rows, fn($a, $b) => $this->comparePlayers($a, $b, $chain));
        return $rows;
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
                    // in the aggregation loop, alongside $agg[$pid]['won'] += ...
                    $agg[$pid]['penalty'] = ($agg[$pid]['penalty'] ?? 0) + ($p['penalty'] ?? 0);
                    $agg[$pid]['won_raw'] = ($agg[$pid]['won_raw'] ?? 0) + ($p['won_raw'] ?? $p['won']);

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
                'penalty'  => $a['penalty']  ?? 0,
                'won_raw'  => $a['won_raw']  ?? $a['won'],
            ];
        }
        $chain = $group->league->standingsOrder();

        usort($rows, function ($a, $b) use ($chain) {
            $posA = $a['current_position'] ?? PHP_INT_MAX;
            $posB = $b['current_position'] ?? PHP_INT_MAX;
            if ($posA !== $posB) return $posA <=> $posB;   // cancha ladder first
            return $this->comparePlayers($a, $b, $chain);   // then configured chain
        });
        // usort($rows, function ($a, $b) {
        //     $posA = $a['current_position'] ?? PHP_INT_MAX;
        //     $posB = $b['current_position'] ?? PHP_INT_MAX;
        //     if ($posA !== $posB) return $posA <=> $posB;
        //     if ($a['diff'] !== $b['diff']) return $b['diff'] <=> $a['diff'];  // diff first
        //     return $b['won'] <=> $a['won'];                                    // games won as tiebreaker
        // });

        return $rows;
    }
}
