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

        $jornada->load([
            'canchas.players' => fn($q) => $q->orderBy('cancha_player.slot'),
            'canchas.pairs'   => fn($q) => $q->orderBy('cancha_pair.slot'),
            'canchas.rounds',
            'canchas.pista.sede',
            'group.league.sedes.pistas',
        ]);
        foreach ($jornada->canchas as $cancha) {
            $this->scheduler->ensureRounds($cancha);
        }
        $jornada->load(['canchas.rounds']);

        return view('leagues.matches.grid', [
            'league'  => $league,
            'group'   => $group,
            'jornada' => $jornada,
        ]);
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
    public function showResult(League $league, Group $group, Jornada $jornada, Cancha $cancha)
    {
        $this->authorize('view', $cancha);
        abort_unless($cancha->jornada_id === $jornada->id, 404);

        $cancha->load(['jornada.group.league', 'pista', 'rounds.pendingProposal']);
        $playerNames = $league->players()->pluck('full_name', 'id');

        $rounds = $cancha->rounds->map(function ($round) use ($playerNames) {
            $proposal = $round->pendingProposal;
            return [
                'id'             => $round->id,
                'rotation_index' => $round->rotation_index,
                'status'         => $round->status,
                'sets'           => $round->sets ?? [],
                'winner'         => $round->winner,
                'team_a'         => collect($round->team_a_player_ids)
                    ->map(fn($id) => ['id' => $id, 'name' => $playerNames[$id] ?? '?'])->values(),
                'team_b'         => collect($round->team_b_player_ids)
                    ->map(fn($id) => ['id' => $id, 'name' => $playerNames[$id] ?? '?'])->values(),
                'no_show'        => $round->no_show_player_ids ?? [],
                'suplente'       => $round->suplente_player_ids ?? [],
                'proposal' => $proposal ? [
                    'id'             => $proposal->id,
                    'proposer_name'  => $proposal->proposer_name,
                    'sets'           => $proposal->sets,
                    'created_at'     => $proposal->created_at->diffForHumans(),
                ] : null,
            ];
        });

        return response()->json([
            'cancha' => [
                'id'        => $cancha->id,
                'label'     => $cancha->label,
                'date'      => $cancha->date?->toDateString(),
                'time_slot' => $cancha->time_slot,
                'pista'     => $cancha->pista?->name,
                'status'    => $cancha->status,
            ],
            'rounds' => $rounds,
        ]);
    }

    public function saveResult(
        Request $request,
        League $league,
        Group $group,
        Jornada $jornada,
        Cancha $cancha,
        \App\Services\MatchResultService $results,
        \App\Services\MatchProposalService $proposals
    ) {
        $this->authorize('update', $cancha);
        abort_unless($cancha->jornada_id === $jornada->id, 404);

        $data = $request->validate([
            'rounds'                       => ['required', 'array', 'min:1'],
            'rounds.*.round_id'            => ['required', 'integer'],
            'rounds.*.sets'                => ['nullable', 'array', 'max:5'],
            'rounds.*.sets.*'              => ['array', 'size:2'],
            'rounds.*.sets.*.0'            => ['integer', 'min:0', 'max:99'],
            'rounds.*.sets.*.1'            => ['integer', 'min:0', 'max:99'],
            'rounds.*.no_show_ids'         => ['nullable', 'array'],
            'rounds.*.no_show_ids.*'       => ['integer'],
            'rounds.*.suplente_ids'        => ['nullable', 'array'],
            'rounds.*.suplente_ids.*'      => ['integer'],
        ]);

        $rounds = $cancha->rounds()->get()->keyBy('id');

        foreach ($data['rounds'] as $r) {
            $round = $rounds->get($r['round_id']);
            if (!$round) continue;

            $pending = $round->pendingProposal()->first();

            $updated = $results->save(
                $round,
                $r['sets']         ?? [],
                $r['no_show_ids']  ?? [],
                $r['suplente_ids'] ?? [],
            );

            if ($pending && !empty($r['sets'] ?? [])) {
                if ($this->setsMatch($r['sets'], $pending->sets ?? [])) {
                    $proposals->markAccepted($pending);
                } else {
                    $proposals->markModified($pending);
                }
            }
        }

        // Update cancha-level status
        $cancha->update([
            'status' => $this->scheduler->deriveStatus($cancha->fresh()),
        ]);

        \Illuminate\Support\Facades\Cache::forget("public_league:{$league->id}:v2");

        return response()->json(['ok' => true]);
    }

    private function setsMatch(array $a, array $b): bool
    {
        if (count($a) !== count($b)) return false;
        foreach ($a as $i => $set) {
            if (!isset($b[$i])) return false;
            if ((int) $set[0] !== (int) $b[$i][0]) return false;
            if ((int) $set[1] !== (int) $b[$i][1]) return false;
        }
        return true;
    }

    public function rejectProposal(
        League $league,
        Group $group,
        Jornada $jornada,
        GameMatch $match,
        \App\Models\MatchScoreProposal $proposal,
        \App\Services\MatchProposalService $proposals
    ) {
        $this->authorize('update', $match);
        abort_unless(
            $match->cancha->jornada_id === $jornada->id &&
                $proposal->match_id === $match->id,
            404
        );

        $proposals->reject($proposal);

        \Illuminate\Support\Facades\Cache::forget("public_league:{$league->id}:v2");

        return response()->json(['ok' => true]);
    }
    public function autoGenerate(
        Request $request,
        League $league,
        Group $group,
        Jornada $jornada,
        \App\Services\MatchAutoGenerateService $generator
    ) {
        $this->authorize('update', $jornada);
        abort_unless($jornada->group_id === $group->id && $group->league_id === $league->id, 404);

        $data = $request->validate([
            'clear_existing' => ['nullable', 'boolean'],
        ]);

        $result = $generator->generate($jornada, (bool) ($data['clear_existing'] ?? false));
        return response()->json($result);
    }
}
