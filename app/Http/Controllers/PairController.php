<?php

namespace App\Http\Controllers;

use App\Http\Requests\PairRequest;
use App\Models\League;
use App\Models\Pair;
use Illuminate\Http\Request;

class PairController extends Controller
{
    public function store(PairRequest $request, League $league)
    {
        $this->authorize('update', $league);
        abort_unless($league->format === League::FORMAT_PAIRS, 422, 'La liga no es por parejas.');

        $data = $request->validated();

        // Validate both players belong to this league
        $playerIds = $league->players()->whereIn('id', [$data['player_a_id'], $data['player_b_id']])->pluck('id')->all();
        abort_if(count($playerIds) !== 2, 422, 'Los jugadores deben pertenecer a esta liga.');

        // Validate neither player is already in a pair
        $existingA = $league->pairs()->where('player_a_id', $data['player_a_id'])->orWhere('player_b_id', $data['player_a_id'])->exists();
        $existingB = $league->pairs()->where('player_a_id', $data['player_b_id'])->orWhere('player_b_id', $data['player_b_id'])->exists();
        abort_if($existingA || $existingB, 422, 'Uno de los jugadores ya está en una pareja.');

        $pair = $league->pairs()->create($data)->load(['playerA', 'playerB']);

        return response()->json(['pair' => $this->serialize($pair)]);
    }

    public function update(PairRequest $request, League $league, Pair $pair)
    {
        $this->authorize('update', $pair);
        abort_unless($pair->league_id === $league->id, 404);

        $pair->update(['label' => $request->validated()['label'] ?? null]);
        return response()->json(['pair' => $this->serialize($pair->fresh()->load(['playerA', 'playerB']))]);
    }

    public function destroy(League $league, Pair $pair)
    {
        $this->authorize('delete', $pair);
        abort_unless($pair->league_id === $league->id, 404);

        $pair->delete();
        return response()->json(['ok' => true]);
    }

    private function serialize(Pair $p): array
    {
        return [
            'id'           => $p->id,
            'label'        => $p->label,
            'display_name' => $p->display_name,
            'player_a'     => ['id' => $p->playerA?->id, 'full_name' => $p->playerA?->full_name],
            'player_b'     => ['id' => $p->playerB?->id, 'full_name' => $p->playerB?->full_name],
        ];
    }
}
