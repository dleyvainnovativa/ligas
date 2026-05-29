<?php

namespace App\Http\Controllers;

use App\Http\Requests\SedeRequest;
use App\Models\League;
use App\Models\Sede;
use Illuminate\Http\Request;

class SedeController extends Controller
{
    public function store(SedeRequest $request, League $league)
    {
        $this->authorize('update', $league);

        $sede = $league->sedes()->create([
            ...$request->validated(),
            'position' => $league->sedes()->max('position') + 1,
        ]);

        return response()->json(['sede' => $this->serialize($sede)]);
    }

    public function update(SedeRequest $request, League $league, Sede $sede)
    {
        $this->authorize('update', $sede);
        abort_unless($sede->league_id === $league->id, 404);

        $sede->update($request->validated());
        return response()->json(['sede' => $this->serialize($sede)]);
    }

    public function destroy(League $league, Sede $sede)
    {
        $this->authorize('delete', $sede);
        abort_unless($sede->league_id === $league->id, 404);

        $sede->delete();
        return response()->json(['ok' => true]);
    }

    private function serialize(Sede $sede): array
    {
        return [
            'id'      => $sede->id,
            'name'    => $sede->name,
            'address' => $sede->address,
            'notes'   => $sede->notes,
            'pistas'  => $sede->pistas->map(fn($p) => [
                'id' => $p->id,
                'name' => $p->name,
            ])->all(),
        ];
    }
}
