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
        $sorted = $this->sortByChainWithPenaltyGate($stats, $chain);   // ← gated
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
        $jornada->loadMissing(['canchas.rounds', 'group.league']);

        $isPairs = $jornada->group->league->format === \App\Models\League::FORMAT_PAIRS;

        // Canchas ordered by their position (Cancha 1 = top). We use the
        // cancha's position/label ordering; fall back to id.
        $canchas = $jornada->canchas
            ->sortBy(fn($c) => $c->position ?? $c->id)
            ->values();

        if ($canchas->isEmpty()) return [];

        // Rank each cancha's competitors (best-first). In doubles the competitor
        // is the PAIR (both players move together); in singles it's the player.
        $ranked = $canchas
            ->map(fn($c) => $isPairs ? $this->rankCanchaPairs($c) : $this->rankCancha($c))
            ->all();

        return $this->distributeFromRanked($ranked, $movement);
    }

    /**
     * Shared promotion/relegation distributor. Given per-cancha ranked id lists
     * (player ids for singles, pair ids for doubles), returns the next jornada's
     * canchas: the top `movement` move up, the bottom `movement` move down, the
     * rest stay. Edge canchas keep whoever has nowhere to go. Movement is clamped
     * so promote/relegate groups never overlap.
     */
    private function distributeFromRanked(array $ranked, int $movement): array
    {
        $n = count($ranked);
        $next = array_fill(0, $n, []);

        foreach ($ranked as $i => $ids) {
            $size = count($ids);
            $m = min($movement, intdiv($size, 2));

            foreach ($ids as $rank => $id) {
                $isTop    = $rank < $m;
                $isBottom = $rank >= ($size - $m);

                if ($isTop && $i > 0) {
                    $next[$i - 1][] = $id;
                } elseif ($isBottom && $i < $n - 1) {
                    $next[$i + 1][] = $id;
                } else {
                    $next[$i][] = $id;
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
        if ($jornada->group->league->format === \App\Models\League::FORMAT_PAIRS) {
            return $this->jornadaBreakdownPairs($jornada, $movement, false);
        }

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
            // $ordered = $this->sortByChain($stats, $chain);   // ← was sortByDesc([...])
            $ordered = $this->sortByChainWithPenaltyGate($stats, $chain);   // ← gated

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
                    'won_raw'   => $s['won_raw'] ?? $s['won'],
                    'lost'      => $s['lost'],
                    'diff'      => $s['diff'],
                    'rounds'    => $s['rounds'],
                    'rounds_lost' => $s['rounds_lost'] ?? 0,   // ← must be here
                    'penalty'   => $s['penalty']   ?? 0,
                    'no_shows'  => $s['no_shows']  ?? 0,   // ← add
                    'suplentes' => $s['suplentes'] ?? 0,   // ← add
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
     * Like jornadaBreakdown, but a "what-if" projection: rounds with no confirmed
     * result but a pending proposal are counted as if the proposal were accepted.
     * Nothing is persisted. Each cancha also carries:
     *   'coverage' => ['total','filled','complete']  (filled = confirmed or proposed)
     * so the UI can badge partially-projected canchas as "incompleto".
     */
    public function jornadaBreakdownPreview(Jornada $jornada, int $movement): array
    {
        if ($jornada->group->league->format === \App\Models\League::FORMAT_PAIRS) {
            return $this->jornadaBreakdownPairs($jornada, $movement, true);
        }

        $jornada->loadMissing(['canchas.rounds.pendingProposal', 'canchas.players', 'group.league']);
        $chain = $jornada->group->league->standingsOrder();

        $canchas = $jornada->canchas
            ->sortBy(fn($c) => $c->position ?? $c->id)
            ->values();

        if ($canchas->isEmpty()) return [];

        $n = $canchas->count();
        $out = [];

        foreach ($canchas as $i => $cancha) {
            $stats = array_values($this->canchaPlayerStats($cancha, true));
            $ordered = $this->sortByChainWithPenaltyGate($stats, $chain);

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
                    'player_id'   => $s['player_id'],
                    'rank'        => $rankIdx + 1,
                    'won'         => $s['won'],
                    'won_raw'     => $s['won_raw'] ?? $s['won'],
                    'lost'        => $s['lost'],
                    'diff'        => $s['diff'],
                    'rounds'      => $s['rounds'],
                    'rounds_lost' => $s['rounds_lost'] ?? 0,
                    'penalty'     => $s['penalty']   ?? 0,
                    'no_shows'    => $s['no_shows']  ?? 0,
                    'suplentes'   => $s['suplentes'] ?? 0,
                    'movement'    => $mv,
                ];
            }

            // Coverage: how many rounds have a confirmed result or a pending proposal.
            $total = $cancha->rounds->count();
            $filled = $cancha->rounds->filter(function ($r) {
                return !empty($r->sets) || ($r->pendingProposal !== null);
            })->count();

            $out[] = [
                'cancha_id' => $cancha->id,
                'label'     => $cancha->label,
                'position'  => $cancha->position ?? ($i + 1),
                'is_top'    => $i === 0,
                'is_bottom' => $i === $n - 1,
                'players'   => $players,
                'coverage'  => [
                    'total'    => $total,
                    'filled'   => $filled,
                    'complete' => $total > 0 && $filled === $total,
                ],
            ];
        }

        return $out;
    }

    /**
     * Per-player won/lost games for one cancha across all its rounds.
     * Returns playerId => ['player_id','won','lost','diff'].
     */
    /**
     * Tally a raw sets array (same rule as GameMatch::tally) — used so preview
     * mode can tally a proposal's sets without a GameMatch instance.
     */
    private function tallySets($sets): array
    {
        $out = ['games_a' => 0, 'games_b' => 0, 'sets_a' => 0, 'sets_b' => 0];
        foreach ((array) $sets as $set) {
            if (!is_array($set) || count($set) !== 2) continue;
            [$a, $b] = [(int) $set[0], (int) $set[1]];
            $out['games_a'] += $a;
            $out['games_b'] += $b;
            if ($a > $b)     $out['sets_a']++;
            elseif ($b > $a) $out['sets_b']++;
        }
        return $out;
    }

    private function canchaPlayerStats(Cancha $cancha, bool $usePreview = false): array
    {
        $cancha->loadMissing(['rounds', 'jornada.group.league']);
        if ($usePreview) $cancha->loadMissing(['rounds.pendingProposal']);
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

            // In preview mode, if a round has no confirmed result but has a
            // pending proposal, use the proposal's sets/flags as if confirmed.
            // Confirmed results always take precedence over a proposal.
            $sets        = $round->sets ?? [];
            $noShowIds   = $round->no_show_player_ids   ?? [];
            $suplenteIds = $round->suplente_player_ids ?? [];

            if ($usePreview && empty($sets)) {
                $proposal = $round->pendingProposal;
                if ($proposal) {
                    $sets        = $proposal->sets ?? [];
                    $noShowIds   = $proposal->no_show_player_ids   ?? [];
                    $suplenteIds = $proposal->suplente_player_ids ?? [];
                }
            }

            $tally = $this->tallySets($sets);
            $gamesA = $tally['games_a'];
            $gamesB = $tally['games_b'];

            $aWon = $gamesA > $gamesB;
            $bWon = $gamesB > $gamesA;

            foreach ($teamA as $pid) {
                $stats[$pid]['won']    = ($stats[$pid]['won']    ?? 0) + $gamesA;
                $stats[$pid]['lost']   = ($stats[$pid]['lost']   ?? 0) + $gamesB;
                $stats[$pid]['rounds'] = ($stats[$pid]['rounds'] ?? 0) + ($aWon ? 1 : 0);
                $stats[$pid]['rounds_lost'] = ($stats[$pid]['rounds_lost'] ?? 0) + ($bWon ? 1 : 0);
            }
            foreach ($teamB as $pid) {
                $stats[$pid]['won']    = ($stats[$pid]['won']    ?? 0) + $gamesB;
                $stats[$pid]['lost']   = ($stats[$pid]['lost']   ?? 0) + $gamesA;
                $stats[$pid]['rounds'] = ($stats[$pid]['rounds'] ?? 0) + ($bWon ? 1 : 0);
                $stats[$pid]['rounds_lost'] = ($stats[$pid]['rounds_lost'] ?? 0) + ($aWon ? 1 : 0);
            }

            foreach ($noShowIds as $pid) {
                if (!isset($penalized[$pid]['no_show'])) {
                    $stats[$pid]['penalty']  = ($stats[$pid]['penalty']  ?? 0) + $penaltyNoShow;
                    $stats[$pid]['no_shows'] = ($stats[$pid]['no_shows'] ?? 0) + 1;
                    $penalized[$pid]['no_show'] = true;
                }
            }
            foreach ($suplenteIds as $pid) {
                if (!isset($penalized[$pid]['suplente'])) {
                    $stats[$pid]['penalty']   = ($stats[$pid]['penalty']   ?? 0) + $penaltySuplente;
                    $stats[$pid]['suplentes'] = ($stats[$pid]['suplentes'] ?? 0) + 1;
                    $penalized[$pid]['suplente'] = true;
                }
            }
        }
        $out = [];
        foreach ($stats as $pid => $s) {
            $rawWon  = $s['won']     ?? 0;
            $lost    = $s['lost']    ?? 0;
            $penalty = $s['penalty'] ?? 0;

            // New rule: if the player was penalized in this cancha, the games
            // don't count — the cancha contributes exactly -penalty.
            // Otherwise the normal game difference applies.
            $won  = $rawWon;
            $diff = $penalty > 0
                ? -$penalty
                : $rawWon - $lost;

            $out[$pid] = [
                'player_id'   => (int) $pid,
                'won'         => $won,       // raw games won (not reduced)
                'won_raw'     => $rawWon,
                'lost'        => $lost,
                'diff'        => $diff,      // -penalty when penalized, else game diff
                'rounds'      => $s['rounds'] ?? 0,
                'penalty'     => $penalty,
                'no_shows'    => $s['no_shows']  ?? 0,
                'suplentes'   => $s['suplentes'] ?? 0,
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

                // $breakdown = $this->jornadaBreakdown($jornada, $movement);
                $breakdown = $this->jornadaBreakdown(
                    $jornada,
                    $jornada->group->league->movementForJornada($jornada)
                );

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
        if ($group->league->format === \App\Models\League::FORMAT_PAIRS) {
            return $this->groupSeasonStandingsPairs($group);
        }

        $playerNames ??= $group->league->players()->pluck('full_name', 'id');

        // Aggregate each player's games across every jornada in this group
        $agg = []; // player_id => ['won','lost','jornadas','last_jornada','last_position','last_label']

        foreach ($group->jornadas->sortBy('number') as $jornada) {
            // $breakdown = $this->jornadaBreakdown($jornada, $movement);
            $breakdown = $this->jornadaBreakdown(
                $jornada,
                $group->league->movementForJornada($jornada)
            );
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
                    $agg[$pid]['diff'] = ($agg[$pid]['diff'] ?? 0) + ($p['diff'] ?? 0);   // ← add
                    $agg[$pid]['jornadas']++;
                    // in the aggregation loop, alongside $agg[$pid]['won'] += ...
                    $agg[$pid]['penalty'] = ($agg[$pid]['penalty'] ?? 0) + ($p['penalty'] ?? 0);
                    $agg[$pid]['won_raw'] = ($agg[$pid]['won_raw'] ?? 0) + ($p['won_raw'] ?? $p['won']);

                    $agg[$pid]['no_shows']  = ($agg[$pid]['no_shows']  ?? 0) + ($p['no_shows']  ?? 0);   // ← add
                    $agg[$pid]['suplentes'] = ($agg[$pid]['suplentes'] ?? 0) + ($p['suplentes'] ?? 0);   // ← add

                    $agg[$pid]['rounds']      = ($agg[$pid]['rounds']      ?? 0) + ($p['rounds']      ?? 0);   // ← add
                    $agg[$pid]['rounds_lost'] = ($agg[$pid]['rounds_lost'] ?? 0) + ($p['rounds_lost'] ?? 0);   // ← add

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
                'diff'  => $a['diff'] ?? 0,        // ← was: $a['won'] - $a['lost']
                'jornadas_played'  => $a['jornadas'],
                'current_position' => $a['last_position'],
                'current_cancha'   => $a['last_label'],
                'penalty'  => $a['penalty']  ?? 0,
                'won_raw'  => $a['won_raw']  ?? $a['won'],
                'no_shows'  => $a['no_shows']  ?? 0,   // ← add
                'suplentes' => $a['suplentes'] ?? 0,   // ← add
                'rounds'      => $a['rounds']      ?? 0,   // ← add (rounds won)
                'rounds_lost' => $a['rounds_lost'] ?? 0,   // ← add

            ];
        }
        // Season-level extra/bonus points. Applied here on the cumulative general
        // leaderboard ONLY — never inside jornadaBreakdown / computeNextDistribution —
        // so bonuses are visible in the ranking but can't shift who moves up or down.
        $rows = $this->applyExtraPoints($rows, $group->league, 'player_id');

        $chain = $group->league->standingsOrder();

        // usort($rows, function ($a, $b) use ($chain) {
        //     $posA = $a['current_position'] ?? PHP_INT_MAX;
        //     $posB = $b['current_position'] ?? PHP_INT_MAX;
        //     if ($posA !== $posB) return $posA <=> $posB;   // cancha ladder first
        //     return $this->comparePlayers($a, $b, $chain);   // then configured chain
        // });
        usort($rows, function ($a, $b) use ($chain) {
            return $this->comparePlayers($a, $b, $chain);
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

    /**
     * Like comparePlayers, but any penalized player sinks below any clean player
     * first — regardless of the chain. Used ONLY for per-cancha ranking
     * (promotion/relegation), so a penalty sends the player toward relegation.
     * The season standings deliberately do NOT use this.
     */
    /**
     * Standings for a SINGLE jornada number, aggregated across ALL groups of the
     * league into one flat leaderboard. Same row shape as groupSeasonStandings so
     * the same table partial renders it. Since it's one jornada, jornadas_played
     * is always 1 per player. Uses jornadaBreakdown (the single source of truth).
     */
    public function jornadaSeasonStandings(\App\Models\League $league, int $number, $playerNames = null): array
    {
        if ($league->format === \App\Models\League::FORMAT_PAIRS) {
            return $this->jornadaSeasonStandingsPairs($league, $number);
        }

        $playerNames ??= $league->players()->pluck('full_name', 'id');

        $agg = [];

        foreach ($league->groups as $group) {
            $jornada = $group->jornadas->firstWhere('number', $number);
            if (!$jornada) continue;

            $breakdown = $this->jornadaBreakdown(
                $jornada,
                $league->movementForJornada($jornada)
            );

            foreach ($breakdown as $cancha) {
                foreach ($cancha['players'] as $p) {
                    $pid = $p['player_id'];
                    if (!isset($agg[$pid])) {
                        $agg[$pid] = [
                            'won' => 0,
                            'lost' => 0,
                            'jornadas' => 0,
                            'last_position' => null,
                            'last_label' => null,
                        ];
                    }
                    $agg[$pid]['won']         += $p['won'];
                    $agg[$pid]['lost']        += $p['lost'];
                    $agg[$pid]['diff']         = ($agg[$pid]['diff'] ?? 0) + ($p['diff'] ?? 0);
                    $agg[$pid]['jornadas']++;
                    $agg[$pid]['penalty']      = ($agg[$pid]['penalty'] ?? 0) + ($p['penalty'] ?? 0);
                    $agg[$pid]['won_raw']      = ($agg[$pid]['won_raw'] ?? 0) + ($p['won_raw'] ?? $p['won']);
                    $agg[$pid]['no_shows']     = ($agg[$pid]['no_shows'] ?? 0) + ($p['no_shows'] ?? 0);
                    $agg[$pid]['suplentes']    = ($agg[$pid]['suplentes'] ?? 0) + ($p['suplentes'] ?? 0);
                    $agg[$pid]['rounds']       = ($agg[$pid]['rounds'] ?? 0) + ($p['rounds'] ?? 0);
                    $agg[$pid]['rounds_lost']  = ($agg[$pid]['rounds_lost'] ?? 0) + ($p['rounds_lost'] ?? 0);
                    $agg[$pid]['last_position'] = $cancha['position'];
                    $agg[$pid]['last_label']    = $cancha['label'];
                }
            }
        }

        $rows = [];
        foreach ($agg as $pid => $a) {
            $rows[] = [
                'player_id'        => $pid,
                'name'             => $playerNames[$pid] ?? '—',
                'won'              => $a['won'],
                'lost'             => $a['lost'],
                'diff'             => $a['diff'] ?? 0,
                'jornadas_played'  => $a['jornadas'],
                'current_position' => $a['last_position'],
                'current_cancha'   => $a['last_label'],
                'penalty'          => $a['penalty'] ?? 0,
                'won_raw'          => $a['won_raw'] ?? $a['won'],
                'no_shows'         => $a['no_shows'] ?? 0,
                'suplentes'        => $a['suplentes'] ?? 0,
                'rounds'           => $a['rounds'] ?? 0,
                'rounds_lost'      => $a['rounds_lost'] ?? 0,
            ];
        }

        $chain = $league->standingsOrder();
        usort($rows, fn($a, $b) => $this->comparePlayers($a, $b, $chain));

        return $rows;
    }

    public function comparePlayersWithPenaltyGate(array $a, array $b, array $chain): int
    {
        $aPen = ($a['penalty'] ?? 0) > 0;
        $bPen = ($b['penalty'] ?? 0) > 0;
        if ($aPen !== $bPen) {
            return $aPen <=> $bPen;   // clean (false) before penalized (true)
        }
        return $this->comparePlayers($a, $b, $chain);
    }

    public function sortByChainWithPenaltyGate(array $rows, array $chain): array
    {
        usort($rows, fn($a, $b) => $this->comparePlayersWithPenaltyGate($a, $b, $chain));
        return $rows;
    }

    // =====================================================================
    //  DOUBLES (fixed-pair) king-of-the-court — the pair is the unit that is
    //  ranked and that promotes/relegates together. Mirrors the player engine
    //  above but keyed by pair_id. A doubles cancha holds up to 3 pairs playing
    //  a round-robin; they are ranked by games across their matches, then the
    //  top pair moves up and the bottom pair moves down (middle stays).
    // =====================================================================

    /**
     * Per-pair won/lost games for one cancha across its round(s), keyed by pair id.
     * Same shape as canchaPlayerStats but with 'pair_id' and pair metadata.
     * A no-show/suplente flag on either member penalizes the whole pair (once).
     */
    private function canchaPairStats(Cancha $cancha, bool $usePreview = false): array
    {
        $cancha->loadMissing(['rounds', 'jornada.group.league', 'pairs.playerA', 'pairs.playerB']);
        if ($usePreview) $cancha->loadMissing(['rounds.pendingProposal']);
        $league = $cancha->jornada->group->league;

        $penaltyNoShow   = (int) ($league->penalty_no_show   ?? 0);
        $penaltySuplente = (int) ($league->penalty_suplente ?? 0);

        // Metadata for every pair assigned to the cancha (so a pair with no
        // played round still shows up, like the singles path).
        $pairMeta = [];
        foreach ($cancha->pairs as $pr) {
            $pairMeta[$pr->id] = [
                'name'        => $pr->display_name,
                'player_a_id' => $pr->player_a_id,
                'player_b_id' => $pr->player_b_id,
            ];
        }

        $stats = [];
        $penalized = []; // pairId => ['no_show'=>bool,'suplente'=>bool]

        foreach ($cancha->rounds as $round) {
            $pairAId = $round->team_a_pair_id;
            $pairBId = $round->team_b_pair_id;

            $sets        = $round->sets ?? [];
            $noShowIds   = $round->no_show_player_ids   ?? [];
            $suplenteIds = $round->suplente_player_ids ?? [];

            if ($usePreview && empty($sets)) {
                $proposal = $round->pendingProposal;
                if ($proposal) {
                    $sets        = $proposal->sets ?? [];
                    $noShowIds   = $proposal->no_show_player_ids   ?? [];
                    $suplenteIds = $proposal->suplente_player_ids ?? [];
                }
            }

            $tally  = $this->tallySets($sets);
            $gamesA = $tally['games_a'];
            $gamesB = $tally['games_b'];
            $aWon = $gamesA > $gamesB;
            $bWon = $gamesB > $gamesA;

            if ($pairAId) {
                $stats[$pairAId]['won']         = ($stats[$pairAId]['won']         ?? 0) + $gamesA;
                $stats[$pairAId]['lost']        = ($stats[$pairAId]['lost']        ?? 0) + $gamesB;
                $stats[$pairAId]['rounds']      = ($stats[$pairAId]['rounds']      ?? 0) + ($aWon ? 1 : 0);
                $stats[$pairAId]['rounds_lost'] = ($stats[$pairAId]['rounds_lost'] ?? 0) + ($bWon ? 1 : 0);
            }
            if ($pairBId) {
                $stats[$pairBId]['won']         = ($stats[$pairBId]['won']         ?? 0) + $gamesB;
                $stats[$pairBId]['lost']        = ($stats[$pairBId]['lost']        ?? 0) + $gamesA;
                $stats[$pairBId]['rounds']      = ($stats[$pairBId]['rounds']      ?? 0) + ($bWon ? 1 : 0);
                $stats[$pairBId]['rounds_lost'] = ($stats[$pairBId]['rounds_lost'] ?? 0) + ($aWon ? 1 : 0);
            }

            // Map per-player no-show / suplente flags onto the owning pair.
            $teams = [
                [$pairAId, $round->team_a_player_ids ?? []],
                [$pairBId, $round->team_b_player_ids ?? []],
            ];
            foreach ($teams as [$pid, $members]) {
                if (!$pid) continue;
                if (array_intersect($members, $noShowIds) && !isset($penalized[$pid]['no_show'])) {
                    $stats[$pid]['penalty']  = ($stats[$pid]['penalty']  ?? 0) + $penaltyNoShow;
                    $stats[$pid]['no_shows'] = ($stats[$pid]['no_shows'] ?? 0) + 1;
                    $penalized[$pid]['no_show'] = true;
                }
                if (array_intersect($members, $suplenteIds) && !isset($penalized[$pid]['suplente'])) {
                    $stats[$pid]['penalty']   = ($stats[$pid]['penalty']   ?? 0) + $penaltySuplente;
                    $stats[$pid]['suplentes'] = ($stats[$pid]['suplentes'] ?? 0) + 1;
                    $penalized[$pid]['suplente'] = true;
                }
            }
        }

        $out = [];
        foreach ($stats as $pid => $s) {
            $rawWon  = $s['won']     ?? 0;
            $lost    = $s['lost']    ?? 0;
            $penalty = $s['penalty'] ?? 0;
            $diff    = $penalty > 0 ? -$penalty : $rawWon - $lost;

            $out[$pid] = [
                'pair_id'     => (int) $pid,
                'name'        => $pairMeta[$pid]['name'] ?? '—',
                'player_a_id' => $pairMeta[$pid]['player_a_id'] ?? null,
                'player_b_id' => $pairMeta[$pid]['player_b_id'] ?? null,
                'won'         => $rawWon,
                'won_raw'     => $rawWon,
                'lost'        => $lost,
                'diff'        => $diff,
                'rounds'      => $s['rounds']      ?? 0,
                'rounds_lost' => $s['rounds_lost'] ?? 0,
                'penalty'     => $penalty,
                'no_shows'    => $s['no_shows']  ?? 0,
                'suplentes'   => $s['suplentes'] ?? 0,
            ];
        }
        return $out;
    }

    /** Rank the pairs within a cancha (best first), returning pair ids. */
    public function rankCanchaPairs(Cancha $cancha): array
    {
        $chain  = $cancha->jornada->group->league->standingsOrder();
        $stats  = array_values($this->canchaPairStats($cancha));
        $sorted = $this->sortByChainWithPenaltyGate($stats, $chain);
        return array_map(fn($r) => $r['pair_id'], $sorted);
    }

    /**
     * Pair-keyed version of jornadaBreakdown (+preview). Each cancha's 'players'
     * list holds pair rows (pair_id + name + stats + movement), so the existing
     * breakdown views render them unchanged via $p['name'].
     */
    private function jornadaBreakdownPairs(Jornada $jornada, int $movement, bool $preview = false): array
    {
        $jornada->loadMissing([
            'canchas.rounds' . ($preview ? '.pendingProposal' : ''),
            'canchas.pairs.playerA',
            'canchas.pairs.playerB',
            'group.league',
        ]);
        $chain = $jornada->group->league->standingsOrder();

        $canchas = $jornada->canchas
            ->sortBy(fn($c) => $c->position ?? $c->id)
            ->values();

        if ($canchas->isEmpty()) return [];

        $n = $canchas->count();
        $out = [];

        foreach ($canchas as $i => $cancha) {
            $stats   = array_values($this->canchaPairStats($cancha, $preview));
            $ordered = $this->sortByChainWithPenaltyGate($stats, $chain);

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

                $players[] = array_merge($s, [
                    'rank'     => $rankIdx + 1,
                    'movement' => $mv,
                ]);
            }

            $entry = [
                'cancha_id' => $cancha->id,
                'label'     => $cancha->label,
                'position'  => $cancha->position ?? ($i + 1),
                'is_top'    => $i === 0,
                'is_bottom' => $i === $n - 1,
                'players'   => $players,
            ];

            if ($preview) {
                $total  = $cancha->rounds->count();
                $filled = $cancha->rounds->filter(function ($r) {
                    return !empty($r->sets) || ($r->pendingProposal !== null);
                })->count();
                $entry['coverage'] = [
                    'total'    => $total,
                    'filled'   => $filled,
                    'complete' => $total > 0 && $filled === $total,
                ];
            }

            $out[] = $entry;
        }

        return $out;
    }

    /** Pair-keyed cumulative season standings for a group (the general leaderboard). */
    private function groupSeasonStandingsPairs(Group $group): array
    {
        $agg = []; // pair_id => aggregate

        foreach ($group->jornadas->sortBy('number') as $jornada) {
            $breakdown = $this->jornadaBreakdownPairs($jornada, $group->league->movementForJornada($jornada), false);

            foreach ($breakdown as $cancha) {
                foreach ($cancha['players'] as $p) {
                    $pid = $p['pair_id'];
                    if (!isset($agg[$pid])) {
                        $agg[$pid] = [
                            'name' => $p['name'], 'won' => 0, 'lost' => 0, 'diff' => 0,
                            'jornadas' => 0, 'penalty' => 0, 'won_raw' => 0,
                            'no_shows' => 0, 'suplentes' => 0, 'rounds' => 0, 'rounds_lost' => 0,
                            'last_jornada' => 0, 'last_position' => null, 'last_label' => null,
                        ];
                    }
                    $agg[$pid]['won']         += $p['won'];
                    $agg[$pid]['lost']        += $p['lost'];
                    $agg[$pid]['diff']        += $p['diff'] ?? 0;
                    $agg[$pid]['jornadas']++;
                    $agg[$pid]['penalty']     += $p['penalty']   ?? 0;
                    $agg[$pid]['won_raw']     += $p['won_raw']   ?? $p['won'];
                    $agg[$pid]['no_shows']    += $p['no_shows']  ?? 0;
                    $agg[$pid]['suplentes']   += $p['suplentes'] ?? 0;
                    $agg[$pid]['rounds']      += $p['rounds']      ?? 0;
                    $agg[$pid]['rounds_lost'] += $p['rounds_lost'] ?? 0;

                    if ($jornada->number >= $agg[$pid]['last_jornada']) {
                        $agg[$pid]['last_jornada']  = $jornada->number;
                        $agg[$pid]['last_position'] = $cancha['position'];
                        $agg[$pid]['last_label']    = $cancha['label'];
                    }
                }
            }
        }

        $rows = [];
        foreach ($agg as $pid => $a) {
            $rows[] = [
                'pair_id'          => $pid,
                'name'             => $a['name'],
                'won'              => $a['won'],
                'lost'             => $a['lost'],
                'diff'             => $a['diff'],
                'jornadas_played'  => $a['jornadas'],
                'current_position' => $a['last_position'],
                'current_cancha'   => $a['last_label'],
                'penalty'          => $a['penalty'],
                'won_raw'          => $a['won_raw'],
                'no_shows'         => $a['no_shows'],
                'suplentes'        => $a['suplentes'],
                'rounds'           => $a['rounds'],
                'rounds_lost'      => $a['rounds_lost'],
            ];
        }

        $rows = $this->applyExtraPoints($rows, $group->league, 'pair_id');

        $chain = $group->league->standingsOrder();
        usort($rows, fn($a, $b) => $this->comparePlayers($a, $b, $chain));

        return $rows;
    }

    /** Pair-keyed single-jornada leaderboard across all groups (no season bonuses). */
    private function jornadaSeasonStandingsPairs(\App\Models\League $league, int $number): array
    {
        $agg = [];

        foreach ($league->groups as $group) {
            $jornada = $group->jornadas->firstWhere('number', $number);
            if (!$jornada) continue;

            $breakdown = $this->jornadaBreakdownPairs($jornada, $league->movementForJornada($jornada), false);

            foreach ($breakdown as $cancha) {
                foreach ($cancha['players'] as $p) {
                    $pid = $p['pair_id'];
                    if (!isset($agg[$pid])) {
                        $agg[$pid] = [
                            'name' => $p['name'], 'won' => 0, 'lost' => 0, 'diff' => 0,
                            'jornadas' => 0, 'penalty' => 0, 'won_raw' => 0,
                            'no_shows' => 0, 'suplentes' => 0, 'rounds' => 0, 'rounds_lost' => 0,
                            'last_position' => null, 'last_label' => null,
                        ];
                    }
                    $agg[$pid]['won']         += $p['won'];
                    $agg[$pid]['lost']        += $p['lost'];
                    $agg[$pid]['diff']        += $p['diff'] ?? 0;
                    $agg[$pid]['jornadas']++;
                    $agg[$pid]['penalty']     += $p['penalty']   ?? 0;
                    $agg[$pid]['won_raw']     += $p['won_raw']   ?? $p['won'];
                    $agg[$pid]['no_shows']    += $p['no_shows']  ?? 0;
                    $agg[$pid]['suplentes']   += $p['suplentes'] ?? 0;
                    $agg[$pid]['rounds']      += $p['rounds']      ?? 0;
                    $agg[$pid]['rounds_lost'] += $p['rounds_lost'] ?? 0;
                    $agg[$pid]['last_position'] = $cancha['position'];
                    $agg[$pid]['last_label']    = $cancha['label'];
                }
            }
        }

        $rows = [];
        foreach ($agg as $pid => $a) {
            $rows[] = [
                'pair_id'          => $pid,
                'name'             => $a['name'],
                'won'              => $a['won'],
                'lost'             => $a['lost'],
                'diff'             => $a['diff'],
                'jornadas_played'  => $a['jornadas'],
                'current_position' => $a['last_position'],
                'current_cancha'   => $a['last_label'],
                'penalty'          => $a['penalty'],
                'won_raw'          => $a['won_raw'],
                'no_shows'         => $a['no_shows'],
                'suplentes'        => $a['suplentes'],
                'rounds'           => $a['rounds'],
                'rounds_lost'      => $a['rounds_lost'],
            ];
        }

        $chain = $league->standingsOrder();
        usort($rows, fn($a, $b) => $this->comparePlayers($a, $b, $chain));

        return $rows;
    }

    /**
     * Merge season-level extra/bonus points into leaderboard rows.
     *
     *   'game' unit → adds to games won, shifting `won` and `diff` (the Pts column).
     *   'set'  unit → adds to round/set wins, shifting `rounds` (the Sets tiebreaker).
     *
     * Each row gains signed `extra_games` and `extra_sets` totals so the UI can show
     * an "Extra" column and explain the bonus, instead of it reading as a miscount.
     * Call this ONLY on the cumulative season leaderboard — never on a per-cancha or
     * per-jornada breakdown — so bonuses never affect promotion/relegation.
     *
     * @param array  $column  'player_id' (individual) or 'pair_id' (doubles)
     */
    public function applyExtraPoints(array $rows, \App\Models\League $league, string $column = 'player_id'): array
    {
        $column = $column === 'pair_id' ? 'pair_id' : 'player_id';

        $adjustments = \App\Models\ScoreAdjustment::query()
            ->where('league_id', $league->id)
            ->whereNotNull($column)
            ->get(['id', $column, 'unit', 'amount', 'note']);

        if ($adjustments->isEmpty()) {
            // Still expose the keys (zeroed) so views can rely on them existing.
            foreach ($rows as &$row) {
                $row['extra_games'] = 0;
                $row['extra_sets']  = 0;
                $row['extra_notes'] = [];
            }
            unset($row);
            return $rows;
        }

        // competitorId => ['game' => signed sum, 'set' => signed sum, 'notes' => [lines]]
        $byEntity = [];
        foreach ($adjustments as $a) {
            $id = $a->{$column};
            if ($id === null) continue;
            $byEntity[$id]['game'] = ($byEntity[$id]['game'] ?? 0)
                + ($a->unit === \App\Models\ScoreAdjustment::UNIT_GAME ? (int) $a->amount : 0);
            $byEntity[$id]['set'] = ($byEntity[$id]['set'] ?? 0)
                + ($a->unit === \App\Models\ScoreAdjustment::UNIT_SET ? (int) $a->amount : 0);

            // Keep each motive so the leaderboard can explain the bonus.
            $note = trim((string) ($a->note ?? ''));
            if ($note !== '') {
                $sign = (int) $a->amount > 0 ? '+' : '';
                $unitLabel = $a->unit === \App\Models\ScoreAdjustment::UNIT_SET ? 'sets' : 'games';
                $byEntity[$id]['notes'][] = [
                    'label' => "{$sign}" . (int) $a->amount . " {$unitLabel}",
                    'note'  => $note,
                ];
            }
        }

        foreach ($rows as &$row) {
            $id = $row[$column] ?? null;
            $g = $byEntity[$id]['game'] ?? 0;
            $s = $byEntity[$id]['set']  ?? 0;

            $row['extra_games'] = $g;
            $row['extra_sets']  = $s;
            $row['extra_notes'] = $byEntity[$id]['notes'] ?? [];

            if ($g !== 0) {
                $row['won']  = ($row['won']  ?? 0) + $g;
                $row['diff'] = ($row['diff'] ?? 0) + $g;
            }
            if ($s !== 0) {
                $row['rounds'] = ($row['rounds'] ?? 0) + $s;
            }
        }
        unset($row);

        return $rows;
    }
}
