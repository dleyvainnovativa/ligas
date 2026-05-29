<?php

namespace App\Http\Controllers;

use App\Http\Requests\PistaRequest;
use App\Models\League;
use App\Models\Pista;
use App\Models\Sede;

class PistaController extends Controller
{
    public function store(PistaRequest $request, League $league, Sede $sede)
    {
        $this->authorize('update', $sede);
        abort_unless($sede->league_id === $league->id, 404);

        $pista = $sede->pistas()->create([
            'name'     => $request->validated()['name'],
            'position' => $sede->pistas()->max('position') + 1,
        ]);

        return response()->json(['pista' => ['id' => $pista->id, 'name' => $pista->name]]);
    }

    public function update(PistaRequest $request, League $league, Sede $sede, Pista $pista)
    {
        $this->authorize('update', $pista);
        abort_unless($pista->sede_id === $sede->id && $sede->league_id === $league->id, 404);

        $pista->update($request->validated());
        return response()->json(['pista' => ['id' => $pista->id, 'name' => $pista->name]]);
    }

    public function destroy(League $league, Sede $sede, Pista $pista)
    {
        $this->authorize('delete', $pista);
        abort_unless($pista->sede_id === $sede->id && $sede->league_id === $league->id, 404);

        $pista->delete();
        return response()->json(['ok' => true]);
    }
}
