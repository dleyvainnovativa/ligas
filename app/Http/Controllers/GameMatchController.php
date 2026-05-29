<?php

namespace App\Http\Controllers;

use App\Models\Cancha;
use App\Models\GameMatch;
use App\Models\Group;
use App\Models\Jornada;
use App\Models\League;
use App\Services\MatchSchedulingService;
use Illuminate\Http\Request;

class GameMatchController extends Controller
{
    public function __construct(private MatchSchedulingService $scheduler) {}

    /** Returns the full scheduling-grid payload for a jornada. */
    public function gridIndex(League $league, Group $group, Jornada $jornada)
    {
        $this->authorize('view', $jornada);
        abort_unless($jornada->group_id === $group->id && $group->league_id === $league->id, 404);

        // Generate matches for all canchas that have full rosters
        $jornada->load([
            'canchas.players' => fn($q) => $q->orderBy('cancha_player.slot'),
            'canchas.pairs'   => fn($q) => $q->orderBy('cancha_pair.slot'),
            'canchas.matches',
            'group.league.sedes.pistas',
        ]);
        foreach ($jornada->canchas as $cancha) {
            $this->scheduler->ensureMatches($cancha);
        }
        $jornada->load(['canchas.matches']);

        return view('leagues.matches.grid', [
            'league'  => $league,
            'group'   => $group,
            'jornada' => $jornada,
        ]);
    }

    public function schedule(Request $request, League $league, Group $group, Jornada $jornada, GameMatch $match)
    {
        $this->authorize('update', $match);
        abort_unless($match->cancha->jornada_id === $jornada->id, 404);

        $data = $request->validate([
            'date'      => ['nullable', 'date'],
            'time_slot' => ['nullable', 'regex:/^([01]\d|2[0-3]):[0-5]\d$/'],
            'pista_id'  => ['nullable', 'integer'],
        ]);

        $updated = $this->scheduler->scheduleMatch(
            $match,
            $data['date'] ?? null,
            $data['time_slot'] ?? null,
            $data['pista_id'] ?? null,
        );

        return response()->json(['match' => $this->serialize($updated)]);
    }

    public function autoFit(Request $request, League $league, Group $group, Jornada $jornada, Cancha $cancha)
    {
        $this->authorize('update', $jornada);
        abort_unless($cancha->jornada_id === $jornada->id, 404);

        $data = $request->validate([
            'date'      => ['required', 'date'],
            'time_slot' => ['required', 'regex:/^([01]\d|2[0-3]):[0-5]\d$/'],
            'pista_id'  => ['required', 'integer'],
        ]);

        $applied = $this->scheduler->autoFitCancha($cancha, $data['date'], $data['time_slot'], $data['pista_id']);
        return response()->json(['applied' => $applied]);
    }

    public function conflicts(League $league, Group $group, Jornada $jornada)
    {
        $this->authorize('view', $jornada);
        abort_unless($jornada->group_id === $group->id && $group->league_id === $league->id, 404);

        return response()->json([
            'conflicts' => $this->scheduler->detectConflicts($jornada),
        ]);
    }

    private function serialize(GameMatch $m): array
    {
        return [
            'id'              => $m->id,
            'cancha_id'       => $m->cancha_id,
            'rotation_index'  => $m->rotation_index,
            'date'            => $m->date?->toDateString(),
            'time_slot'       => $m->time_slot,
            'pista_id'        => $m->pista_id,
            'status'          => $m->status,
            'team_a'          => $m->team_a_player_ids,
            'team_b'          => $m->team_b_player_ids,
        ];
    }
    public function showResult(League $league, Group $group, Jornada $jornada, GameMatch $match)
    {
        $this->authorize('view', $match);
        abort_unless($match->cancha->jornada_id === $jornada->id, 404);

        $match->load(['cancha.jornada.group.league', 'pista']);

        $playerNames = $league->players()->pluck('full_name', 'id');

        return response()->json([
            'match' => [
                'id'             => $match->id,
                'rotation_index' => $match->rotation_index,
                'date'           => $match->date?->toDateString(),
                'time_slot'      => $match->time_slot,
                'pista'          => $match->pista?->name,
                'status'         => $match->status,
                'sets'           => $match->sets ?? [],
                'winner'         => $match->winner,
                'team_a'         => collect($match->team_a_player_ids)
                    ->map(fn($id) => ['id' => $id, 'name' => $playerNames[$id] ?? '?'])->values(),
                'team_b'         => collect($match->team_b_player_ids)
                    ->map(fn($id) => ['id' => $id, 'name' => $playerNames[$id] ?? '?'])->values(),
                'no_show'        => $match->no_show_player_ids ?? [],
                'suplente'       => $match->suplente_player_ids ?? [],
            ],
        ]);
    }

    public function saveResult(Request $request, League $league, Group $group, Jornada $jornada, GameMatch $match, \App\Services\MatchResultService $results)
    {
        $this->authorize('update', $match);
        abort_unless($match->cancha->jornada_id === $jornada->id, 404);

        $data = $request->validate([
            'sets'              => ['nullable', 'array', 'max:5'],
            'sets.*'            => ['array', 'size:2'],
            'sets.*.0'          => ['integer', 'min:0', 'max:99'],
            'sets.*.1'          => ['integer', 'min:0', 'max:99'],
            'no_show_ids'       => ['nullable', 'array'],
            'no_show_ids.*'     => ['integer'],
            'suplente_ids'      => ['nullable', 'array'],
            'suplente_ids.*'    => ['integer'],
        ]);

        $updated = $results->save(
            $match,
            $data['sets']         ?? [],
            $data['no_show_ids']  ?? [],
            $data['suplente_ids'] ?? [],
        );

        return response()->json([
            'match' => [
                'id'     => $updated->id,
                'sets'   => $updated->sets,
                'winner' => $updated->winner,
                'status' => $updated->status,
            ],
        ]);
    }
}
