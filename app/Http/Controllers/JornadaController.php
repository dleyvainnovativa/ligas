<?php

namespace App\Http\Controllers;

use App\Models\Group;
use App\Models\Jornada;
use App\Models\League;
use App\Services\CanchaService;
use Illuminate\Http\Request;

class JornadaController extends Controller
{
    public function __construct(private CanchaService $canchas) {}

    public function index(League $league, Group $group)
    {
        $this->authorize('view', $league);
        abort_unless($group->league_id === $league->id, 404);

        $group->load('jornadas');

        return view('leagues.jornadas.index', compact('league', 'group'));
    }

    public function show(League $league, Group $group, Jornada $jornada)
    {
        $this->authorize('view', $jornada);
        abort_unless(
            $jornada->group_id === $group->id && $group->league_id === $league->id,
            404
        );

        $jornada->load([
            'canchas.players',
            'canchas.pairs.playerA',
            'canchas.pairs.playerB',
            'group.league',
        ]);

        return view('leagues.jornadas.show', [
            'league'  => $league,
            'group'   => $group,
            'jornada' => $jornada,
        ]);
    }

    public function store(Request $request, League $league, Group $group)
    {
        $this->authorize('update', $league);
        abort_unless($group->league_id === $league->id, 404);

        $nextNumber = ($group->jornadas()->max('number') ?? 0) + 1;

        $jornada = $group->jornadas()->create([
            'number' => $nextNumber,
            'status' => Jornada::STATUS_DRAFT,
        ]);

        return response()->json(['jornada' => $this->serialize($jornada)]);
    }

    public function update(Request $request, League $league, Group $group, Jornada $jornada)
    {
        $this->authorize('update', $jornada);
        abort_unless($jornada->group_id === $group->id && $group->league_id === $league->id, 404);

        $data = $request->validate([
            'window_start' => ['nullable', 'date'],
            'window_end'   => ['nullable', 'date', 'after_or_equal:window_start'],
            'notes'        => ['nullable', 'string', 'max:500'],
            'status'       => ['nullable', 'in:draft,scheduled,completed'],
        ]);

        $jornada->update($data);
        return response()->json(['jornada' => $this->serialize($jornada->fresh())]);
    }

    public function destroy(League $league, Group $group, Jornada $jornada)
    {
        $this->authorize('delete', $jornada);
        abort_unless($jornada->group_id === $group->id && $group->league_id === $league->id, 404);

        $jornada->delete();
        return response()->json(['ok' => true]);
    }

    public function autoFill(League $league, Group $group, Jornada $jornada)
    {
        $this->authorize('update', $jornada);
        abort_unless($jornada->group_id === $group->id && $group->league_id === $league->id, 404);

        $this->canchas->autoFill($jornada);
        return response()->json(['ok' => true]);
    }

    private function serialize(Jornada $j): array
    {
        return [
            'id'           => $j->id,
            'number'       => $j->number,
            'status'       => $j->status,
            'window_start' => $j->window_start?->toDateString(),
            'window_end'   => $j->window_end?->toDateString(),
            'canchas_count' => $j->canchas()->count(),
        ];
    }
}
