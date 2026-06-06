<?php

namespace App\Http\Controllers;

use App\Models\GameMatch;
use App\Models\League;
use App\Services\StandingsService;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;

class PublicLeagueController extends Controller
{
    public function __construct(private StandingsService $standings) {}

    public function show(string $slug)
    {
        $league = League::query()
            ->where('slug', $slug)
            ->whereIn('status', [League::STATUS_ACTIVE, League::STATUS_COMPLETED])
            ->with([
                'groups' => fn($q) => $q->orderBy('position'),
                'groups.jornadas',
                'sedes.pistas',
                'activeAds',
            ])
            ->firstOrFail();

        // Short cache: the public page is hit a lot, doesn't need to be hot-fresh
        // $payload = Cache::remember("public_league:{$league->id}:v1", now()->addSeconds(60), function () use ($league) {
        //     return $this->buildPayload($league);
        // });
        $payload = $this->buildPayload($league);

        return view('public.league.show', [
            'league'   => $league,
            'payload'  => $payload,
        ]);
    }
    private function buildPayload(League $league): array
    {
        $playerNames = $league->players()->pluck('full_name', 'id');

        $canchas = \App\Models\Cancha::query()
            ->whereIn('jornada_id', function ($q) use ($league) {
                $q->select('jornadas.id')
                    ->from('jornadas')
                    ->join('groups', 'groups.id', '=', 'jornadas.group_id')
                    ->where('groups.league_id', $league->id);
            })
            ->with(['jornada.group', 'pista.sede', 'players', 'pairs', 'rounds.pendingProposal'])
            ->whereNotNull('date')
            ->orderBy('date')
            ->orderBy('time_slot')
            ->get();

        $groupsPayload = [];
        foreach ($league->groups as $group) {
            $groupCanchas = $canchas->filter(fn($c) => $c->jornada->group_id === $group->id);

            $upcoming = $groupCanchas
                ->filter(fn($c) => $c->status !== \App\Models\Cancha::STATUS_COMPLETED && $this->isCanchaUpcoming($c))
                ->sortBy([['date', 'asc'], ['time_slot', 'asc']])
                ->values();

            $recent = $groupCanchas
                ->filter(fn($c) => $c->status === \App\Models\Cancha::STATUS_COMPLETED)
                ->sortByDesc(fn($c) => $c->updated_at?->timestamp ?? 0)
                ->values();

            $groupsPayload[] = [
                'group'     => $group,
                'standings' => $this->standings->forGroup($group),
                'upcoming'  => $upcoming->map(fn($c) => $this->serializeCancha($c, $playerNames))->all(),
                'recent'    => $recent->take(10)->map(fn($c) => $this->serializeCancha($c, $playerNames))->all(),
                'jornadas'  => $group->jornadas->map(fn($j) => [
                    'id'     => $j->id,
                    'number' => $j->number,
                    'status' => $j->status,
                ])->all(),
            ];
        }

        $currentJornada = $this->computeCurrentJornada($league, $canchas);

        return [
            'groups'          => $groupsPayload,
            'current_jornada' => $currentJornada,
            'totals'          => [
                'players'  => $league->players()->count(),
                'jornadas' => $league->groups->flatMap->jornadas->count(),
                'matches_played' => $canchas->where('status', \App\Models\Cancha::STATUS_COMPLETED)->count(),
            ],
        ];
    }

    private function isCanchaUpcoming(\App\Models\Cancha $c): bool
    {
        if (!$c->date) return false;
        return \Carbon\Carbon::parse($c->date)->isFuture() || \Carbon\Carbon::parse($c->date)->isToday();
    }

    private function serializeCancha(\App\Models\Cancha $c, $playerNames): array
    {
        // Player roster from chips
        $players = $c->players->isNotEmpty()
            ? $c->players->map(fn($p) => $playerNames[$p->id] ?? '?')->all()
            : $c->pairs->flatMap(fn($pair) => [
                $playerNames[$pair->player_a_id] ?? '?',
                $playerNames[$pair->player_b_id] ?? '?',
            ])->all();

        $roundsPayload = $c->rounds->map(function ($round) use ($playerNames) {
            $t = $round->status === 'completed' ? $round->tally() : null;
            $proposal = $round->pendingProposal;
            return [
                'id'             => $round->id,
                'rotation_index' => $round->rotation_index,
                'status'         => $round->status,
                'team_a'         => collect($round->team_a_player_ids)->map(fn($id) => $playerNames[$id] ?? '?')->all(),
                'team_b'         => collect($round->team_b_player_ids)->map(fn($id) => $playerNames[$id] ?? '?')->all(),
                'sets'           => $round->sets,
                'sets_a'         => $t['sets_a'] ?? null,
                'sets_b'         => $t['sets_b'] ?? null,
                'winner'         => $round->winner,
                'pending_proposal' => $proposal ? [
                    'proposer_name' => $proposal->proposer_name,
                    'sets'          => $proposal->sets,
                    'created_at'    => $proposal->created_at->diffForHumans(),
                ] : null,
            ];
        })->all();

        return [
            'id'           => $c->id,
            'label'        => $c->label,
            'date'         => $c->date?->toDateString(),
            'date_display' => $c->date?->translatedFormat('D d M'),
            'time_slot'    => $c->time_slot,
            'pista'        => $c->pista?->name,
            'sede'         => $c->pista?->sede?->name,
            'status'       => $c->status,
            'players'      => $players,
            'rounds'       => $roundsPayload,
        ];
    }

    private function computeCurrentJornada(League $league, $canchas): ?array
    {
        // Lowest-numbered jornada with at least one not-yet-completed cancha
        $byJornada = $canchas->groupBy(fn($c) => $c->jornada_id);

        $bestNumber = null;
        $bestJornadas = [];

        foreach ($league->groups as $group) {
            foreach ($group->jornadas as $j) {
                $jCanchas = $byJornada[$j->id] ?? collect();
                $hasOpen = $jCanchas->isEmpty() || $jCanchas->contains(fn($c) => $c->status !== \App\Models\Cancha::STATUS_COMPLETED);
                if (!$hasOpen) continue;

                if ($bestNumber === null || $j->number < $bestNumber) {
                    $bestNumber = $j->number;
                    $bestJornadas = [$j];
                } elseif ($j->number === $bestNumber) {
                    $bestJornadas[] = $j;
                }
            }
        }

        if (!$bestNumber) return null;

        return [
            'number' => $bestNumber,
            'group_names' => collect($bestJornadas)->map(fn($j) => $j->group->name ?? '')->unique()->values()->all(),
        ];
    }
}
