<?php

namespace App\Http\Controllers;

use App\Models\Cancha;
use App\Models\Group;
use App\Models\Jornada;
use App\Models\League;
use App\Models\Pair;
use App\Models\Player;
use App\Services\CanchaService;
use Illuminate\Http\Request;

class CanchaController extends Controller
{
    public function __construct(private CanchaService $canchas) {}

    public function store(Request $request, League $league, Group $group, Jornada $jornada)
    {
        $this->authorize('update', $jornada);
        abort_unless($jornada->group_id === $group->id && $group->league_id === $league->id, 404);

        $position = ($jornada->canchas()->max('position') ?? 0) + 1;
        $cancha = $jornada->canchas()->create([
            'label'    => "Cancha {$position}",
            'position' => $position,
        ]);

        return response()->json(['cancha' => $this->serialize($cancha)]);
    }

    public function update(Request $request, League $league, Group $group, Jornada $jornada, Cancha $cancha)
    {
        $this->authorize('update', $cancha);
        abort_unless($cancha->jornada_id === $jornada->id, 404);

        $data = $request->validate(['label' => ['required', 'string', 'max:80']]);
        $cancha->update($data);

        return response()->json(['cancha' => $this->serialize($cancha->fresh())]);
    }

    public function destroy(League $league, Group $group, Jornada $jornada, Cancha $cancha)
    {
        $this->authorize('delete', $cancha);
        abort_unless($cancha->jornada_id === $jornada->id, 404);

        $cancha->delete();
        return response()->json(['ok' => true]);
    }

    /** Assign a player or pair into a specific cancha (or unassign if cancha=0). */
    public function assign(Request $request, League $league, Group $group, Jornada $jornada)
    {
        $this->authorize('update', $jornada);
        abort_unless($jornada->group_id === $group->id && $group->league_id === $league->id, 404);

        if ($league->format === League::FORMAT_PAIRS) {
            $data = $request->validate([
                'pair_id'   => ['required', 'integer'],
                'cancha_id' => ['nullable', 'integer'],
            ]);
            $pair = $league->pairs()->findOrFail($data['pair_id']);

            if (empty($data['cancha_id'])) {
                $this->canchas->unassignPair($pair, $jornada);
                return response()->json(['ok' => true]);
            }
            $cancha = $jornada->canchas()->findOrFail($data['cancha_id']);
            $slot = $this->canchas->assignPair($cancha, $pair);
            return response()->json(['ok' => true, 'slot' => $slot]);
        }

        $data = $request->validate([
            'player_id'      => ['required', 'integer'],
            'cancha_id'      => ['nullable', 'integer'],
            'preferred_slot' => ['nullable', 'integer', 'between:1,4'],
        ]);
        $player = $league->players()->findOrFail($data['player_id']);

        if (empty($data['cancha_id'])) {
            $this->canchas->unassignPlayer($player, $jornada);
            return response()->json(['ok' => true]);
        }
        $cancha = $jornada->canchas()->findOrFail($data['cancha_id']);
        $slot = $this->canchas->assignPlayer($cancha, $player, $data['preferred_slot'] ?? null);
        return response()->json(['ok' => true, 'slot' => $slot]);
    }

    public function swap(Request $request, League $league, Group $group, Jornada $jornada)
    {
        $this->authorize('update', $jornada);
        abort_unless($jornada->group_id === $group->id && $group->league_id === $league->id, 404);

        if ($league->format === League::FORMAT_PAIRS) {
            $data = $request->validate([
                'source_pair_id' => ['required', 'integer'],
                'target_pair_id' => ['required', 'integer', 'different:source_pair_id'],
            ]);
            $source = $league->pairs()->findOrFail($data['source_pair_id']);
            $target = $league->pairs()->findOrFail($data['target_pair_id']);
            $this->canchas->swapPairs($jornada, $source, $target);
            return response()->json(['ok' => true]);
        }

        $data = $request->validate([
            'source_player_id' => ['required', 'integer'],
            'target_player_id' => ['required', 'integer', 'different:source_player_id'],
        ]);
        $source = $league->players()->findOrFail($data['source_player_id']);
        $target = $league->players()->findOrFail($data['target_player_id']);
        $this->canchas->swapPlayers($jornada, $source, $target);
        return response()->json(['ok' => true]);
    }

    private function serialize(Cancha $c): array
    {
        return [
            'id'       => $c->id,
            'label'    => $c->label,
            'position' => $c->position,
        ];
    }
}
