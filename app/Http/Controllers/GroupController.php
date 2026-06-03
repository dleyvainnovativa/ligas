<?php

namespace App\Http\Controllers;

use App\Http\Requests\GroupRequest;
use App\Models\Group;
use App\Models\League;
use App\Models\Player;
use App\Models\Pair;
use App\Services\RosterService;
use Illuminate\Http\Request;

class GroupController extends Controller
{
    public function __construct(private RosterService $roster) {}

    public function index(League $league)
    {
        $this->authorize('view', $league);
        $league->load(['groups.players', 'groups.pairs.playerA', 'groups.pairs.playerB', 'players', 'pairs.playerA', 'pairs.playerB']);
        return view('leagues.groups.index', compact('league'));
    }

    public function store(GroupRequest $request, League $league)
    {
        $this->authorize('update', $league);

        $group = $league->groups()->create([
            'name'     => $request->validated()['name'],
            'position' => ($league->groups()->max('position') ?? 0) + 1,
        ]);

        return response()->json(['group' => $this->serialize($group)]);
    }

    public function update(GroupRequest $request, League $league, Group $group)
    {
        $this->authorize('update', $group);
        abort_unless($group->league_id === $league->id, 404);

        $group->update($request->validated());
        return response()->json(['group' => $this->serialize($group)]);
    }

    public function destroy(League $league, Group $group)
    {
        $this->authorize('delete', $group);
        abort_unless($group->league_id === $league->id, 404);

        // Closes all current memberships via the FK cascade (group_player rows go too).
        $group->delete();
        return response()->json(['ok' => true]);
    }

    /** Move a player into this group (or unassign if group=0). */
    public function movePlayer(Request $request, League $league)
    {
        $this->authorize('update', $league);

        $data = $request->validate([
            'player_id' => ['required', 'integer'],
            'group_id'  => ['nullable', 'integer'], // null/0/missing = unassign
        ]);

        $player = $league->players()->findOrFail($data['player_id']);
        $group  = !empty($data['group_id'])
            ? $league->groups()->findOrFail($data['group_id'])
            : null;

        $this->roster->movePlayer($player, $group);

        return response()->json(['ok' => true]);
    }

    /** Move a pair into this group (pairs mode). */
    public function movePair(Request $request, League $league)
    {
        $this->authorize('update', $league);

        $data = $request->validate([
            'pair_id'  => ['required', 'integer'],
            'group_id' => ['nullable', 'integer'],
        ]);

        $pair  = $league->pairs()->findOrFail($data['pair_id']);
        $group = !empty($data['group_id'])
            ? $league->groups()->findOrFail($data['group_id'])
            : null;

        $this->roster->movePair($pair, $group);

        return response()->json(['ok' => true]);
    }

    private function serialize(Group $g): array
    {
        return [
            'id'       => $g->id,
            'name'     => $g->name,
            'position' => $g->position,
        ];
    }
}
