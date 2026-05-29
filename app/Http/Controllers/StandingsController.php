<?php

namespace App\Http\Controllers;

use App\Models\Group;
use App\Models\League;
use App\Services\StandingsService;

class StandingsController extends Controller
{
    public function __construct(private StandingsService $standings) {}

    /** League-wide standings: shows each group as a separate table. */
    public function index(League $league)
    {
        $this->authorize('view', $league);

        $league->load(['groups']);
        $tables = [];
        foreach ($league->groups as $group) {
            $tables[] = [
                'group' => $group,
                'rows'  => $this->standings->forGroup($group),
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

        return view('leagues.standings.group', [
            'league' => $league,
            'group'  => $group,
            'rows'   => $this->standings->forGroup($group),
        ]);
    }
}
