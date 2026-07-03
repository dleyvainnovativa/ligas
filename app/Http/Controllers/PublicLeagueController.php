<?php

namespace App\Http\Controllers;

use App\Models\Cancha;
use App\Models\League;
use App\Services\StandingsService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class PublicLeagueController extends Controller
{
    public function __construct(private StandingsService $standings) {}

    public function show(string $slug)
    {
        $league = $this->loadLeague($slug);
        $payload = $this->buildHomePayload($league);

        return view('public.league.show', [
            'league'      => $league,
            'payload'     => $payload,
            'active_page' => 'inicio',
        ]);
    }

    public function calendario(string $slug)
    {
        $league = $this->loadLeague($slug);
        $payload = $this->buildCalendarioPayload($league);

        return view('public.league.calendario', [
            'league'      => $league,
            'payload'     => $payload,
            'active_page' => 'calendario',
        ]);
    }

    public function jornada(string $slug, int $number)
    {
        $league = $this->loadLeague($slug);
        $payload = $this->buildJornadaPayload($league, $number);

        if (!$payload) abort(404);

        return view('public.league.jornada', [
            'league'      => $league,
            'payload'     => $payload,
            'active_page' => 'calendario',
        ]);
    }

    public function clasificacion(string $slug)
    {
        $league = $this->loadLeague($slug);
        $payload = $this->buildClasificacionPayload($league);

        return view('public.league.clasificacion', [
            'league'      => $league,
            'payload'     => $payload,
            'active_page' => 'clasificacion',
        ]);
    }

    public function jugadores(Request $request, string $slug)
    {
        $league = $this->loadLeague($slug);
        // Jugadores page is search/filter heavy on the client; we cache the raw list only.
        $payload = $this->buildJugadoresPayload($league);

        return view('public.league.jugadores', [
            'league'      => $league,
            'payload'     => $payload,
            'active_page' => 'jugadores',
        ]);
    }

    public function reglas(string $slug)
    {
        $league = $this->loadLeague($slug);
        // Rules are stable — cache longer
        $payload = $this->buildReglasPayload($league);

        return view('public.league.reglas', [
            'league'      => $league,
            'payload'     => $payload,
            'active_page' => 'reglas',
        ]);
    }

    // ============================================================
    // Loaders + payload builders
    // ============================================================

    private function loadLeague(string $slug): League
    {
        return League::query()
            ->where('slug', $slug)
            ->whereIn('status', [League::STATUS_ACTIVE, League::STATUS_COMPLETED])
            ->with(['groups.jornadas', 'sedes.pistas'])
            ->firstOrFail();
    }

    private function buildHomePayload(League $league): array
    {
        $playerNames = $league->players()->pluck('full_name', 'id');
        $current = $this->findCurrentJornadaNumber($league);
        $anyJornadas = $league->groups->flatMap->jornadas->isNotEmpty();
        $noCurrentReason = null;
        if (!$current) {
            if ($league->status === 'completed') {
                $noCurrentReason = 'completed';
            } elseif (!$anyJornadas) {
                $noCurrentReason = 'not_started';   // no jornadas created at all
            } else {
                $noCurrentReason = 'no_pending';    // jornadas exist but none pending/assigned
            }
        }
        $promo = app(\App\Services\PromotionRelegationService::class);

        $groupsPayload = [];
        foreach ($league->groups as $group) {
            $currentJornada = $current
                ? $group->jornadas->firstWhere('number', $current)
                : null;

            $breakdown = [];
            $complete = false;
            $canchas   = collect();

            if ($currentJornada) {
                $raw = $promo->jornadaBreakdown($currentJornada, (int) $league->promotion_relegation);

                // Load the canchas so we can attach schedule/pista metadata
                $canchas = $this->loadCanchas($currentJornada->id)->keyBy('id');

                $totalCanchas = count($raw);

                $breakdown = collect($raw)->map(function ($cancha, $idx) use ($playerNames, $canchas, $totalCanchas) {
                    // Resolve player names
                    $cancha['players'] = collect($cancha['players'])->map(function ($p) use ($playerNames) {
                        $p['name'] = $playerNames[$p['player_id']] ?? '—';
                        return $p;
                    })->all();

                    // Attach schedule + pista from the serialized cancha
                    $model = $canchas->get($cancha['cancha_id']);
                    if ($model) {
                        $cancha['date_display'] = $model->date?->translatedFormat('D d M');
                        $cancha['time_slot']    = $model->time_slot;
                        $cancha['pista']        = $model->pista?->name;
                        $cancha['sede']         = $model->pista?->sede?->name;
                        $cancha['status']       = $model->status;
                    } else {
                        $cancha['date_display'] = null;
                        $cancha['time_slot']    = null;
                        $cancha['pista']        = null;
                        $cancha['sede']         = null;
                        $cancha['status']       = null;
                    }

                    // Tier tint: position 1 = warm (~45°), last = cool (~210°)
                    $pos = $cancha['position'] ?? ($idx + 1);
                    if ($totalCanchas > 1) {
                        $t = ($pos - 1) / ($totalCanchas - 1);   // 0 at top, 1 at bottom
                    } else {
                        $t = 0;                                   // single cancha = warm
                    }
                    $cancha['tint_hue'] = (int) round(45 + $t * (210 - 45));

                    return $cancha;
                })->all();

                $complete = $currentJornada->canchas()->count() > 0
                    && !$currentJornada->canchas()->where('status', '!=', \App\Models\Cancha::STATUS_COMPLETED)->exists();
            }

            $standings = $this->standings->forGroup($group);

            $groupsPayload[] = [
                'group'         => $group,
                'canchas'      => $canchas->map(fn($c) => $this->serializeCancha($c, $playerNames))->all(),
                'breakdown'     => $breakdown,
                'jornada_done'  => $complete,
                'top_3'         => array_slice($standings, 0, 3),
                'total_players' => $group->players()->count(),
            ];
        }


        $totalCanchas = Cancha::query()
            ->whereIn('jornada_id', function ($q) use ($league) {
                $q->select('jornadas.id')->from('jornadas')
                    ->join('groups', 'groups.id', '=', 'jornadas.group_id')
                    ->where('groups.league_id', $league->id);
            })->count();

        $completedCanchas = Cancha::query()
            ->whereIn('jornada_id', function ($q) use ($league) {
                $q->select('jornadas.id')->from('jornadas')
                    ->join('groups', 'groups.id', '=', 'jornadas.group_id')
                    ->where('groups.league_id', $league->id);
            })
            ->where('status', Cancha::STATUS_COMPLETED)
            ->count();


        return [
            'groups'           => $groupsPayload,
            'current_jornada'  => $current,
            'no_current_reason' => $noCurrentReason,
            'stats' => [
                'players'           => $league->players()->count(),
                'groups'            => $league->groups->count(),
                'total_canchas'     => $totalCanchas,
                'completed_canchas' => $completedCanchas,
                'completion_pct'    => $totalCanchas > 0
                    ? round(($completedCanchas / $totalCanchas) * 100)
                    : 0,
            ],
        ];
    }

    private function buildCalendarioPayload(League $league): array
    {
        // Jornadas across all groups; group by number for a clean list
        $byNumber = [];
        foreach ($league->groups as $group) {
            foreach ($group->jornadas as $j) {
                $byNumber[$j->number] ??= [
                    'number' => $j->number,
                    'window_start' => $j->window_start,
                    'window_end'   => $j->window_end,
                    'group_data'   => [],
                ];
                $canchas = $this->loadCanchas($j->id);
                $byNumber[$j->number]['group_data'][] = [
                    'group_name'      => $group->name,
                    'total_canchas'   => $canchas->count(),
                    'completed_canchas' => $canchas->where('status', Cancha::STATUS_COMPLETED)->count(),
                ];
            }
        }

        $jornadas = collect($byNumber)->sortBy('number')->values()->map(function ($j) {
            $total = collect($j['group_data'])->sum('total_canchas');
            $done  = collect($j['group_data'])->sum('completed_canchas');
            $status = $this->deriveJornadaStatus($total, $done);

            return [
                'number'       => $j['number'],
                'window_start' => $j['window_start'],
                'window_end'   => $j['window_end'],
                'date_display' => $this->formatJornadaWindow($j['window_start'], $j['window_end']),
                'total'        => $total,
                'done'         => $done,
                'progress_pct' => $total > 0 ? round(($done / $total) * 100) : 0,
                'status'       => $status,
            ];
        })->all();

        return ['jornadas' => $jornadas];
    }

    private function buildJornadaPayload(League $league, int $number): ?array
    {
        $playerNames = $league->players()->pluck('full_name', 'id');
        $promo = app(\App\Services\PromotionRelegationService::class);

        $jornadas = collect();
        foreach ($league->groups as $group) {
            $j = $group->jornadas->firstWhere('number', $number);
            if ($j) $jornadas->push(['group' => $group, 'jornada' => $j]);
        }
        if ($jornadas->isEmpty()) return null;

        $allNumbers = $league->groups->flatMap->jornadas->pluck('number')->unique()->sort()->values();
        $prev = $allNumbers->filter(fn($n) => $n < $number)->max();
        $next = $allNumbers->filter(fn($n) => $n > $number)->min();

        $groupsPayload = $jornadas->map(function ($pair) use ($playerNames, $promo, $league) {
            $jornada = $pair['jornada'];
            $canchas = $this->loadCanchas($jornada->id);

            // Build ranked breakdown for this jornada, keyed by cancha_id
            $rawBreakdown = $promo->jornadaBreakdown($jornada, (int) $league->promotion_relegation);
            $breakdownByCancha = [];
            foreach ($rawBreakdown as $cb) {
                // resolve player names
                $cb['players'] = collect($cb['players'])->map(function ($p) use ($playerNames) {
                    $p['name'] = $playerNames[$p['player_id']] ?? '—';
                    return $p;
                })->all();
                $breakdownByCancha[$cb['cancha_id']] = $cb;
            }

            $complete = $canchas->isNotEmpty()
                && $canchas->every(fn($c) => $c->status === \App\Models\Cancha::STATUS_COMPLETED);

            // Serialize each cancha and attach its standings breakdown
            $canchaList = $canchas->map(function ($c) use ($playerNames, $breakdownByCancha) {
                $serialized = $this->serializeCancha($c, $playerNames);
                $serialized['breakdown'] = $breakdownByCancha[$c->id] ?? null;
                return $serialized;
            })->all();

            return [
                'group_name' => $pair['group']->name,
                'complete'   => $complete,
                'canchas'    => $canchaList,
            ];
        })->all();

        $firstJornada = $jornadas->first()['jornada'];

        return [
            'number'       => $number,
            'window_start' => $firstJornada->window_start,
            'window_end'   => $firstJornada->window_end,
            'date_display' => $this->formatJornadaWindow($firstJornada->window_start, $firstJornada->window_end),
            'groups'       => $groupsPayload,
            'prev_number'  => $prev,
            'next_number'  => $next,
        ];
    }

    private function buildClasificacionPayload(League $league): array
    {
        $promo = app(\App\Services\PromotionRelegationService::class);
        $playerNames = $league->players()->pluck('full_name', 'id');

        $groupsPayload = [];
        foreach ($league->groups as $group) {
            $groupsPayload[] = [
                'group'     => $group,
                'standings' => $promo->groupSeasonStandings($group, (int) $league->promotion_relegation, $playerNames),
            ];
        }
        return ['groups' => $groupsPayload];
    }

    private function buildJugadoresPayload(League $league): array
    {
        $promo = app(\App\Services\PromotionRelegationService::class);
        $playerNames = $league->players()->pluck('full_name', 'id');

        // Aggregate across all groups, build a player_id => stats map
        $statsByPlayer = [];
        foreach ($league->groups as $group) {
            $standings = $promo->groupSeasonStandings($group, (int) $league->promotion_relegation, $playerNames);
            foreach ($standings as $row) {
                $statsByPlayer[$row['player_id']] = [
                    'group'  => $group->name,
                    'stats'  => $row,
                ];
            }
        }

        $players = $league->players()->orderBy('full_name')->get();

        $rows = $players->map(function ($player) use ($statsByPlayer) {
            $entry = $statsByPlayer[$player->id] ?? null;
            $stats = $entry['stats'] ?? null;
            return [
                'id'               => $player->id,
                'name'             => $player->full_name,
                'group_name'       => $entry['group'] ?? null,
                'won'              => $stats['won']              ?? 0,
                'lost'             => $stats['lost']             ?? 0,
                'diff'             => $stats['diff']             ?? 0,
                'jornadas_played'  => $stats['jornadas_played']  ?? 0,
                'current_position' => $stats['current_position'] ?? null,
            ];
        })->values()->all();

        return [
            'players'     => $rows,
            'group_names' => $league->groups->pluck('name')->values()->all(),
            'total'       => count($rows),
        ];
    }

    private function buildReglasPayload(League $league): array
    {
        $dayLabels = [
            'mon' => 'Lunes',
            'tue' => 'Martes',
            'wed' => 'Miércoles',
            'thu' => 'Jueves',
            'fri' => 'Viernes',
            'sat' => 'Sábado',
            'sun' => 'Domingo',
        ];
        $days = collect($league->days_of_week ?? [])
            ->map(fn($d) => $dayLabels[$d] ?? $d)
            ->all();

        return [
            'format'        => $league->format,
            'num_jornadas'  => $league->num_jornadas,
            'cost'          => $league->cost_per_player,
            'points' => [
                'win'  => $league->points_win,
                'draw' => $league->points_draw,
                'loss' => $league->points_loss,
            ],
            'penalties' => [
                'no_show'  => $league->penalty_no_show,
                'suplente' => $league->penalty_suplente,
            ],
            'schedule' => [
                'days'       => $days,
                'time_slots' => $league->time_slots ?? [],
            ],
            'sedes' => $league->sedes->map(fn($s) => [
                'name'    => $s->name,
                'address' => $s->address,
                'pistas'  => $s->pistas->pluck('name')->all(),
            ])->all(),
        ];
    }

    // ============================================================
    // Helpers
    // ============================================================

    private function loadCanchas(int $jornadaId)
    {
        return Cancha::query()
            ->where('jornada_id', $jornadaId)
            ->with(['players', 'pairs', 'pista.sede', 'rounds.pendingProposal'])
            ->orderBy('date')
            ->orderBy('time_slot')
            ->get();
    }

    private function findCurrentJornadaNumber(League $league): ?int
    {
        // Lowest-numbered jornada with at least one not-yet-completed cancha
        $byNumber = [];
        foreach ($league->groups as $group) {
            foreach ($group->jornadas as $j) {
                $hasOpen = $j->canchas()->where('status', '!=', Cancha::STATUS_COMPLETED)->exists()
                    || !$j->canchas()->exists();
                if ($hasOpen) {
                    $byNumber[$j->number] = true;
                }
            }
        }
        $sorted = collect(array_keys($byNumber))->sort()->values();
        return $sorted->first();
    }

    private function deriveJornadaStatus(int $total, int $done): string
    {
        if ($total === 0) return 'sin_canchas';
        if ($done === 0)  return 'proxima';
        if ($done < $total) return 'en_curso';
        return 'terminada';
    }

    private function formatJornadaWindow($start, $end): string
    {
        if (!$start && !$end) return '';
        $startC = $start ? \Carbon\Carbon::parse($start) : null;
        $endC   = $end   ? \Carbon\Carbon::parse($end)   : null;
        if ($startC && $endC) {
            if ($startC->isSameMonth($endC)) {
                return $startC->translatedFormat('d') . ' al ' . $endC->translatedFormat('d \d\e M');
            }
            return $startC->translatedFormat('d M') . ' al ' . $endC->translatedFormat('d M');
        }
        return ($startC ?? $endC)->translatedFormat('d M');
    }

    private function serializeCancha(Cancha $c, $playerNames): array
    {
        $players = $c->players->isNotEmpty()
            ? $c->players->map(fn($p) => $playerNames[$p->id] ?? '?')->all()
            : $c->pairs->flatMap(fn($pair) => [
                $playerNames[$pair->player_a_id] ?? '?',
                $playerNames[$pair->player_b_id] ?? '?',
            ])->all();

        $rounds = $c->rounds->map(function ($round) use ($playerNames) {
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
            'rounds'       => $rounds,
        ];
    }
    public function jornadaStandings(
        string $slug,
        int $number,
        \App\Services\PromotionRelegationService $promo
    ) {
        $league = $this->loadLeague($slug);

        // $payload = Cache::remember(
        //     "public_league:{$league->id}:jornada:{$number}:standings:v1",
        //     60,
        //     fn() => $this->buildJornadaStandingsPayload($league, $number, $promo)
        // );
        $payload = $this->buildJornadaStandingsPayload($league, $number, $promo);

        if (!$payload) abort(404);

        return view('public.league.jornada-standings', [
            'league'      => $league,
            'payload'     => $payload,
            'active_page' => 'calendario',
        ]);
    }

    private function buildJornadaStandingsPayload(
        League $league,
        int $number,
        \App\Services\PromotionRelegationService $promo
    ): ?array {
        $playerNames = $league->players()->pluck('full_name', 'id');

        $groupsData = collect();
        foreach ($league->groups as $group) {
            $jornada = $group->jornadas->firstWhere('number', $number);
            if (!$jornada) continue;

            $breakdown = $promo->jornadaBreakdown($jornada, (int) $league->promotion_relegation);
            $complete = $jornada->canchas()->count() > 0
                && !$jornada->canchas()->where('status', '!=', \App\Models\Cancha::STATUS_COMPLETED)->exists();

            $groupsData->push([
                'group_name' => $group->name,
                'breakdown'  => $breakdown,
                'complete'   => $complete,
            ]);
        }

        if ($groupsData->isEmpty()) return null;

        // Resolve player names into the breakdown for the view (so the public
        // view doesn't need the full name map separately)
        $groupsData = $groupsData->map(function ($g) use ($playerNames) {
            $g['breakdown'] = collect($g['breakdown'])->map(function ($cancha) use ($playerNames) {
                $cancha['players'] = collect($cancha['players'])->map(function ($p) use ($playerNames) {
                    $p['name'] = $playerNames[$p['player_id']] ?? '—';
                    return $p;
                })->all();
                return $cancha;
            })->all();
            return $g;
        })->all();

        return [
            'number' => $number,
            'groups' => $groupsData,
        ];
    }
    public function jugador(
        string $slug,
        int $playerId,
        \App\Services\PromotionRelegationService $promo
    ) {
        $league = $this->loadLeague($slug);

        $player = $league->players()->find($playerId);
        if (!$player) abort(404);

        // $payload = Cache::remember(
        //     "public_league:{$league->id}:jugador:{$playerId}:v1",
        //     60,
        //     fn() => $this->buildJugadorPayload($league, $player, $promo)
        // );
        $payload = $this->buildJugadorPayload($league, $player, $promo);

        return view('public.league.jugador', [
            'league'      => $league,
            'player'      => $player,
            'payload'     => $payload,
            'active_page' => 'jugadores',
        ]);
    }

    private function buildJugadorPayload(
        League $league,
        \App\Models\Player $player,
        \App\Services\PromotionRelegationService $promo
    ): array {
        $data = $promo->playerHistory($player, (int) $league->promotion_relegation);

        // Which group is the player in (most recent)?
        $groupName = collect($data['history'])->last()['group_name'] ?? null;

        return [
            'name'       => $player->full_name,
            'group_name' => $groupName,
            'history'    => $data['history'],
            'totals'     => $data['totals'],
        ];
    }
}
