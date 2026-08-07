<?php

namespace App\Services;

use App\Models\League;

/**
 * Single source of truth for per-league limits.
 *
 * Convention: a NULL limit means unlimited. An integer is a hard cap.
 * Every "canAdd…" method returns true when the corresponding limit is null.
 *
 * This is deliberately parallel to TierService (which governs account-scope
 * limits like how many active leagues a manager may run). This service only
 * governs what happens *inside* a single league.
 */
class LeagueTierService
{
    /**
     * Current usage counts for a league. Keeping these in one place avoids
     * drift between the enforcement checks and the admin/usage display.
     */
    public function usage(League $league): array
    {
        return [
            'players'  => $league->players()->count(),
            'jornadas' => $this->jornadaCount($league),
            'groups'   => $league->groups()->count(),
        ];
    }

    public function canAddPlayer(League $league): bool
    {
        return $this->within($league->max_players, $league->players()->count());
    }

    public function canAddJornada(League $league): bool
    {
        return $this->within($league->max_jornadas, $this->jornadaCount($league));
    }

    public function canAddGroup(League $league): bool
    {
        return $this->within($league->max_groups, $league->groups()->count());
    }

    /**
     * Structured view for UIs: used, limit (null = ∞), and whether the league
     * is currently at or over the cap for each resource.
     */
    public function snapshot(League $league): array
    {
        $usage = $this->usage($league);

        return [
            'players'  => $this->line($league->max_players,  $usage['players']),
            'jornadas' => $this->line($league->max_jornadas, $usage['jornadas']),
            'groups'   => $this->line($league->max_groups,   $usage['groups']),
        ];
    }

    /**
     * Jornadas belong to groups, not directly to the league, so the count is
     * summed across the league's groups. Adjust the relationship path here if
     * your schema exposes a direct league->jornadas relation.
     */
    private function jornadaCount(League $league): int
    {
        if (method_exists($league, 'jornadas')) {
            return $league->jornadas()->count();
        }

        return $league->groups()
            ->withCount('jornadas')
            ->get()
            ->sum('jornadas_count');
    }

    private function within(?int $limit, int $used): bool
    {
        return $limit === null || $used < $limit;
    }

    private function line(?int $limit, int $used): array
    {
        return [
            'used'      => $used,
            'limit'     => $limit,          // null === unlimited
            'unlimited' => $limit === null,
            'at_limit'  => $limit !== null && $used >= $limit,
            'remaining' => $limit === null ? null : max(0, $limit - $used),
        ];
    }
}
