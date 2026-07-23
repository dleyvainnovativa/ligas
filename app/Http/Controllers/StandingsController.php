<?php

namespace App\Http\Controllers;

use App\Models\Group;
use App\Models\League;
use App\Services\PromotionRelegationService;
use App\Services\StandingsService;

class StandingsController extends Controller
{
    public function __construct(
        private StandingsService $standings,
        private PromotionRelegationService $promo,
    ) {}

    /** League-wide standings: shows each group as a separate table. */
    public function index(League $league)
    {
        $this->authorize('view', $league);

        $league->load(['groups.jornadas', 'players']);
        $playerNames = $league->players()->pluck('full_name', 'id');
        $movement = (int) $league->promotion_relegation;

        $tables = [];
        foreach ($league->groups as $group) {
            $tables[] = [
                'group' => $group,
                'rows'  => $this->rowsFor($group, $movement, $playerNames),
            ];
        }

        return view('leagues.standings.index', [
            'league' => $league,
            'tables' => $tables,
        ]);
    }

    public function group(League $league, Group $group)
    {
        $this->authorize('view', $group);
        abort_unless($group->league_id === $league->id, 404);

        $playerNames = $league->players()->pluck('full_name', 'id');

        return view('leagues.standings.group', [
            'league' => $league,
            'group'  => $group,
            'rows'   => $this->rowsFor($group, (int) $league->promotion_relegation, $playerNames),
        ]);
    }

    /**
     * Individual mode uses the games-based ranking (same as the public pages),
     * so the penalty rule lives in exactly one place. Pairs mode still uses the
     * classic points system.
     */
    private function rowsFor(Group $group, int $movement, $playerNames): array
    {
        if ($group->league->format === League::FORMAT_PAIRS) {
            return $this->standings->forGroup($group);
        }

        $rows = $this->promo->groupSeasonStandings($group, $movement, $playerNames);

        // Add a rank field so the view can highlight the top 3
        foreach ($rows as $i => $row) {
            $rows[$i]['rank'] = $i + 1;
        }

        return $rows;
    }
}
