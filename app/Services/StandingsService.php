<?php

namespace App\Services;

use App\Models\GameMatch;
use App\Models\Group;
use App\Models\League;

class StandingsService
{
    /**
     * Compute standings rows for a group.
     *
     * Individual mode: row per player, points = sum of games won across all their matches in this group.
     * Pairs mode: row per pair, points via win/draw/loss formula.
     *
     * @return array  list of rows, sorted, each row has shape documented below.
     */
    public function forGroup(Group $group): array
    {
        $group->load('league');
        return $group->league->format === League::FORMAT_PAIRS
            ? $this->forPairsGroup($group)
            : $this->forIndividualGroup($group);
    }

    // ---------- INDIVIDUAL ----------
    private function forIndividualGroup(Group $group): array
    {
        $league = $group->league;

        // All matches in any cancha of any jornada belonging to this group
        $matches = GameMatch::query()
            ->whereIn('cancha_id', function ($q) use ($group) {
                $q->select('canchas.id')
                    ->from('canchas')
                    ->join('jornadas', 'jornadas.id', '=', 'canchas.jornada_id')
                    ->where('jornadas.group_id', $group->id);
            })
            ->whereNotNull('sets')
            ->get();

        // Player ids currently in the group
        $playerIds = $group->players()->pluck('players.id')->all();
        $playersById = $group->players()->get()->keyBy('id');

        // Initialize rows
        $rows = [];
        foreach ($playerIds as $pid) {
            $rows[$pid] = $this->blankIndividualRow($playersById[$pid]);
        }

        foreach ($matches as $m) {
            $tally = $m->tally();
            $teamA = $m->team_a_player_ids ?? [];
            $teamB = $m->team_b_player_ids ?? [];
            $noShow   = $m->no_show_player_ids ?? [];
            $suplente = $m->suplente_player_ids ?? [];

            $this->applyIndividualMatch($rows, $teamA, $tally['games_a'], $tally['games_b'], $tally['sets_a'], $tally['sets_b'], $m->winner === 'a', $m->winner === 'b', $noShow, $suplente, $league);
            $this->applyIndividualMatch($rows, $teamB, $tally['games_b'], $tally['games_a'], $tally['sets_b'], $tally['sets_a'], $m->winner === 'b', $m->winner === 'a', $noShow, $suplente, $league);
        }
        // dd($rows);

        // Sort: points desc, sets_diff desc, games_diff desc, name asc
        $list = array_values($rows);
        usort($list, function ($x, $y) {
            return [$y['points'], $y['sets_diff'], $y['games_diff'], $x['name']]
                <=> [$x['points'], $x['sets_diff'], $x['games_diff'], $y['name']];
        });

        // Add rank
        foreach ($list as $i => $row) $list[$i]['rank'] = $i + 1;
        return $list;
    }

    private function applyIndividualMatch(
        array &$rows,
        array $teamPlayerIds,
        int $gamesFor,
        int $gamesAgainst,
        int $setsFor,
        int $setsAgainst,
        bool $won,
        bool $lost,
        array $noShow,
        array $suplente,
        League $league
    ): void {
        foreach ($teamPlayerIds as $pid) {
            if (!isset($rows[$pid])) continue; // player moved out of the group — historical match doesn't count
            $rows[$pid]['played']++;
            $rows[$pid]['games_raw']     += $gamesFor;
            $rows[$pid]['games_for']     += $gamesFor;
            $rows[$pid]['games_against'] += $gamesAgainst;
            $rows[$pid]['sets_for']      += $setsFor;
            $rows[$pid]['sets_against']  += $setsAgainst;
            if ($won)  $rows[$pid]['wins']++;
            if ($lost) $rows[$pid]['losses']++;

            // Individual scoring: games won = points

            // Penalty: no-show
            if (in_array($pid, $noShow, true)) {
                $rows[$pid]['no_shows']++;
                $rows[$pid]['games_for'] -= $league->penalty_no_show;
                // $gamesFor -= $league->penalty_no_show;
                $rows[$pid]['points'] -= $league->penalty_no_show;
                $rows[$pid]['penalty_points'] += $league->penalty_no_show;
            }

            // Penalty: suplente
            if (in_array($pid, $suplente, true)) {
                $rows[$pid]['suplentes']++;
                $rows[$pid]['games_for'] -= $league->penalty_suplente;
                // $gamesFor -= $league->penalty_suplente;
                $rows[$pid]['points'] -= $league->penalty_suplente;
                $rows[$pid]['penalty_points'] += $league->penalty_suplente;
            }
            $rows[$pid]['points'] += $gamesFor;

            // Recompute diffs after each update
            $rows[$pid]['points'] = $rows[$pid]['games_for'] - $rows[$pid]['games_against'];
            $rows[$pid]['games_diff'] = $rows[$pid]['games_for'] - $rows[$pid]['games_against'];
            $rows[$pid]['sets_diff']  = $rows[$pid]['sets_for']  - $rows[$pid]['sets_against'];
        }
    }

    private function blankIndividualRow($player): array
    {
        return [
            'id'             => $player->id,
            'name'           => $player->full_name,
            'played'         => 0,
            'wins'           => 0,
            'losses'         => 0,
            'games_raw'      => 0,
            'games_for'      => 0,
            'games_against'  => 0,
            'games_diff'     => 0,
            'sets_for'       => 0,
            'sets_against'   => 0,
            'sets_diff'      => 0,
            'no_shows'       => 0,
            'suplentes'      => 0,   // ← add
            'penalty_points' => 0,
            'points'         => 0,
            'rank'           => null,
        ];
    }

    // ---------- PAIRS ----------
    private function forPairsGroup(Group $group): array
    {
        $league = $group->league;

        $matches = GameMatch::query()
            ->whereIn('cancha_id', function ($q) use ($group) {
                $q->select('canchas.id')
                    ->from('canchas')
                    ->join('jornadas', 'jornadas.id', '=', 'canchas.jornada_id')
                    ->where('jornadas.group_id', $group->id);
            })
            ->whereNotNull('sets')
            ->get();

        $pairs = $group->pairs()->with(['playerA', 'playerB'])->get()->keyBy('id');
        $rows = [];
        foreach ($pairs as $pair) {
            $rows[$pair->id] = $this->blankPairRow($pair);
        }

        foreach ($matches as $m) {
            $tally = $m->tally();
            $pairA = $m->team_a_pair_id;
            $pairB = $m->team_b_pair_id;
            if (!$pairA || !$pairB) continue;

            $this->applyPairMatch(
                $rows,
                $pairA,
                $pairB,
                $tally['games_a'],
                $tally['games_b'],
                $tally['sets_a'],
                $tally['sets_b'],
                $m->winner,
                $league
            );
        }

        $list = array_values($rows);
        usort($list, function ($x, $y) {
            return [$y['points'], $y['sets_diff'], $y['games_diff'], $x['name']]
                <=> [$x['points'], $x['sets_diff'], $x['games_diff'], $y['name']];
        });
        foreach ($list as $i => $row) $list[$i]['rank'] = $i + 1;
        return $list;
    }

    private function applyPairMatch(
        array &$rows,
        int $pairAId,
        int $pairBId,
        int $gA,
        int $gB,
        int $sA,
        int $sB,
        ?string $winner,
        League $league
    ): void {
        $this->addPairStats($rows, $pairAId, $gA, $gB, $sA, $sB, $winner === 'a', $winner === 'b', $winner === 'draw', $league);
        $this->addPairStats($rows, $pairBId, $gB, $gA, $sB, $sA, $winner === 'b', $winner === 'a', $winner === 'draw', $league);
    }

    private function addPairStats(
        array &$rows,
        int $pairId,
        int $gF,
        int $gA,
        int $sF,
        int $sA,
        bool $won,
        bool $lost,
        bool $drew,
        League $league
    ): void {
        if (!isset($rows[$pairId])) return;
        $r = &$rows[$pairId];
        $r['played']++;
        $r['games_for']     += $gF;
        $r['games_against'] += $gA;
        $r['sets_for']      += $sF;
        $r['sets_against']  += $sA;

        if ($won) {
            $r['wins']++;
            $r['points'] += $league->points_win;
        } elseif ($lost) {
            $r['losses']++;
            $r['points'] += $league->points_loss;
        } elseif ($drew) {
            $r['draws']++;
            $r['points'] += $league->points_draw;
        }

        $r['games_diff'] = $r['games_for'] - $r['games_against'];
        $r['sets_diff']  = $r['sets_for']  - $r['sets_against'];
    }

    private function blankPairRow($pair): array
    {
        return [
            'id'             => $pair->id,
            'name'           => $pair->display_name,
            'played'         => 0,
            'wins'           => 0,
            'losses'         => 0,
            'draws'          => 0,
            'games_for'      => 0,
            'games_against'  => 0,
            'games_diff'     => 0,
            'sets_for'       => 0,
            'sets_against'   => 0,
            'sets_diff'      => 0,
            'penalty_points' => 0,
            'points'         => 0,
            'rank'           => null,
        ];
    }
}
