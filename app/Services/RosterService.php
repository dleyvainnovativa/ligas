<?php

namespace App\Services;

use App\Models\Group;
use App\Models\League;
use App\Models\Pair;
use App\Models\Player;
use Illuminate\Support\Facades\DB;

class RosterService
{
    /** Move a player to a group. If group is null, removes them from any group. */
    public function movePlayer(Player $player, ?Group $group): void
    {
        DB::transaction(function () use ($player, $group) {
            $now = now();

            // Close current membership(s)
            $player->groups()
                ->wherePivotNull('left_at')
                ->each(function ($g) use ($player, $now) {
                    $player->groups()->updateExistingPivot($g->id, ['left_at' => $now]);
                });

            // Open new one (if a target group was given)
            if ($group) {
                if ($group->league_id !== $player->league_id) {
                    throw new \DomainException('El grupo pertenece a otra liga.');
                }
                $player->groups()->attach($group->id, ['joined_at' => $now]);
            }
        });
    }

    /** Move a pair to a group. If group is null, removes the pair from any group. */
    public function movePair(Pair $pair, ?Group $group): void
    {
        DB::transaction(function () use ($pair, $group) {
            $now = now();

            // Close current
            DB::table('group_pair')
                ->where('pair_id', $pair->id)
                ->whereNull('left_at')
                ->update(['left_at' => $now, 'updated_at' => $now]);

            // Open new
            if ($group) {
                if ($group->league_id !== $pair->league_id) {
                    throw new \DomainException('El grupo pertenece a otra liga.');
                }
                DB::table('group_pair')->insert([
                    'group_id'   => $group->id,
                    'pair_id'    => $pair->id,
                    'joined_at'  => $now,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            }
        });
    }
    /**
     * Auto-assign up to $count unassigned players to the given group.
     * Shuffles to avoid alphabetical bias. Returns number actually assigned.
     */
    public function autoFillPlayers(Group $group, int $count): int
    {
        $league = $group->league;

        // Collect player IDs currently in any group of this league
        $assignedIds = \Illuminate\Support\Facades\DB::table('group_player')
            ->whereIn('group_id', $league->groups()->pluck('id'))
            ->whereNull('left_at')
            ->pluck('player_id')
            ->all();

        $unassigned = $league->players()
            ->whereNotIn('id', $assignedIds)
            ->get()
            ->shuffle()
            ->take($count);

        foreach ($unassigned as $player) {
            $this->movePlayer($player, $group);
        }

        return $unassigned->count();
    }

    public function autoFillPairs(Group $group, int $count): int
    {
        $league = $group->league;

        $assignedIds = \Illuminate\Support\Facades\DB::table('group_pair')
            ->whereIn('group_id', $league->groups()->pluck('id'))
            ->whereNull('left_at')
            ->pluck('pair_id')
            ->all();

        $unassigned = $league->pairs()
            ->whereNotIn('id', $assignedIds)
            ->get()
            ->shuffle()
            ->take($count);

        foreach ($unassigned as $pair) {
            $this->movePair($pair, $group);
        }

        return $unassigned->count();
    }

    /**
     * Count of unassigned players (or pairs in pairs mode) for a league.
     */
    public function unassignedCount(League $league): int
    {
        if ($league->format === League::FORMAT_PAIRS) {
            $assignedIds = \Illuminate\Support\Facades\DB::table('group_pair')
                ->whereIn('group_id', $league->groups()->pluck('id'))
                ->whereNull('left_at')
                ->pluck('pair_id')
                ->all();
            return $league->pairs()->whereNotIn('id', $assignedIds)->count();
        }

        $assignedIds = \Illuminate\Support\Facades\DB::table('group_player')
            ->whereIn('group_id', $league->groups()->pluck('id'))
            ->whereNull('left_at')
            ->pluck('player_id')
            ->all();
        return $league->players()->whereNotIn('id', $assignedIds)->count();
    }
}
