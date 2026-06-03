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

        // All scheduled or completed matches in this league, eagerly loaded with relations needed for display
        $matches = GameMatch::query()
            ->whereIn('cancha_id', function ($q) use ($league) {
                $q->select('canchas.id')
                    ->from('canchas')
                    ->join('jornadas', 'jornadas.id', '=', 'canchas.jornada_id')
                    ->join('groups',  'groups.id',  '=', 'jornadas.group_id')
                    ->where('groups.league_id', $league->id);
            })
            ->with(['cancha.jornada.group', 'pista.sede', 'pendingProposal'])
            ->whereNotNull('date')
            ->orderBy('date')
            ->orderBy('time_slot')
            ->get();

        // Build per-group structures
        $groupsPayload = [];
        foreach ($league->groups as $group) {
            $groupMatches = $matches->filter(fn($m) => $m->cancha->jornada->group_id === $group->id);

            $upcoming = $groupMatches
                ->filter(fn($m) => $m->status !== GameMatch::STATUS_COMPLETED && $this->isUpcoming($m))
                ->sortBy([['date', 'asc'], ['time_slot', 'asc']])
                ->values();

            $recent = $groupMatches
                ->filter(fn($m) => $m->status === GameMatch::STATUS_COMPLETED)
                ->sortByDesc(fn($m) => $m->played_at?->timestamp ?? 0)
                ->values();

            $groupsPayload[] = [
                'group'     => $group,
                'standings' => $this->standings->forGroup($group),
                'upcoming'  => $upcoming->map(fn($m) => $this->serializeMatch($m, $playerNames))->all(),
                'recent'    => $recent->take(10)->map(fn($m) => $this->serializeMatch($m, $playerNames))->all(),
                'jornadas'  => $group->jornadas->map(fn($j) => [
                    'id'     => $j->id,
                    'number' => $j->number,
                    'status' => $j->status,
                ])->all(),
            ];
        }

        // League-wide quick stats
        $currentJornada = $this->computeCurrentJornada($league, $matches);

        return [
            'groups'          => $groupsPayload,
            'current_jornada' => $currentJornada,
            'totals'          => [
                'players'  => $league->players()->count(),
                'jornadas' => $league->groups->flatMap->jornadas->count(),
                'matches_played' => $matches->where('status', GameMatch::STATUS_COMPLETED)->count(),
            ],
        ];
    }

    private function isUpcoming(GameMatch $m): bool
    {
        if (!$m->date) return false;
        // Today and future
        return Carbon::parse($m->date)->isFuture()
            || Carbon::parse($m->date)->isToday();
    }

    private function serializeMatch(GameMatch $m, $playerNames): array
    {
        $tally = $m->status === GameMatch::STATUS_COMPLETED ? $m->tally() : null;

        // Load relation if not eager (it should be, but defensively)
        if (!$m->relationLoaded('pendingProposal')) {
            $m->load('pendingProposal');
        }
        $p = $m->pendingProposal;

        return [
            'id'             => $m->id,
            'date'           => $m->date?->toDateString(),
            'date_display'   => $m->date?->translatedFormat('D d M'),
            'time_slot'      => $m->time_slot,
            'pista'          => $m->pista?->name,
            'sede'           => $m->pista?->sede?->name,
            'rotation_index' => $m->rotation_index,
            'status'         => $m->status,
            'team_a'         => collect($m->team_a_player_ids)
                ->map(fn($id) => $playerNames[$id] ?? '?')->all(),
            'team_b'         => collect($m->team_b_player_ids)
                ->map(fn($id) => $playerNames[$id] ?? '?')->all(),
            'sets'           => $m->sets,
            'winner'         => $m->winner,
            'sets_a'         => $tally['sets_a'] ?? null,
            'sets_b'         => $tally['sets_b'] ?? null,
            'pending_proposal' => $p ? [
                'proposer_name' => $p->proposer_name,
                'sets'          => $p->sets,
                'created_at'    => $p->created_at->diffForHumans(),
            ] : null,
        ];
    }

    private function computeCurrentJornada(League $league, $matches): ?array
    {
        // The earliest jornada (lowest number) that has at least one not-yet-completed match across any group.
        $byJornada = $matches->groupBy(fn($m) => $m->cancha->jornada_id);

        $bestNumber = null;
        $bestJornadas = [];

        foreach ($league->groups as $group) {
            foreach ($group->jornadas as $j) {
                $jMatches = $byJornada[$j->id] ?? collect();
                $hasOpen = $jMatches->isEmpty() || $jMatches->contains(fn($m) => $m->status !== GameMatch::STATUS_COMPLETED);
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
