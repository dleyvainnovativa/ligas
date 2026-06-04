<?php

namespace App\Services;

use App\Models\Cancha;
use App\Models\GameMatch;
use App\Models\Jornada;
use App\Models\League;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class MatchAutoGenerateService
{
    private const MAX_ATTEMPTS = 30;

    public function __construct(
        private MatchSchedulingService $scheduler
    ) {}

    /**
     * Attempt to schedule all unscheduled matches in a jornada.
     *
     * @param Jornada $jornada
     * @param bool $clearExisting If true, clear all current schedules first.
     * @return array{ok:bool, placed:int, skipped:int, message?:string, conflicts?:array}
     */
    public function generate(Jornada $jornada, bool $clearExisting = false): array
    {
        $jornada->load(['canchas.matches', 'group.league.sedes.pistas']);
        $league = $jornada->group->league;
        $mode = $league->format;

        // Ensure match records exist for every filled cancha
        foreach ($jornada->canchas as $cancha) {
            $this->scheduler->ensureMatches($cancha);
        }
        $jornada->load('canchas.matches');

        // Optionally clear all current schedules
        if ($clearExisting) {
            DB::table('game_matches')
                ->whereIn('cancha_id', $jornada->canchas->pluck('id'))
                ->update([
                    'date'      => null,
                    'time_slot' => null,
                    'pista_id'  => null,
                    'status'    => GameMatch::STATUS_UNSCHEDULED,
                ]);
            $jornada->load('canchas.matches');
        }

        // Build the resource catalog
        $dates = $this->enumerateDates($jornada);
        $slots = collect($league->time_slots ?? [])->sort()->values()->all();
        $pistas = $league->sedes->flatMap->pistas->values();

        if (empty($dates) || empty($slots) || $pistas->isEmpty()) {
            return [
                'ok' => false,
                'placed' => 0,
                'skipped' => 0,
                'message' => 'Falta configuración: días, horarios, o pistas.',
            ];
        }

        // Build the set of cells already occupied (from manual placements we want to preserve)
        $alreadyOccupied = $this->buildOccupancyMap($jornada);

        // Collect canchas with unscheduled matches
        $canchasToSchedule = [];
        foreach ($jornada->canchas as $cancha) {
            $unscheduled = $cancha->matches->filter(fn($m) => !$m->date)->values();
            if ($unscheduled->isEmpty()) continue;

            // In individual mode, partial placements are weird. If any of the 3
            // rotations is already scheduled, we don't try to slot the rest —
            // we'd have to honor the existing pista/time, and that's a search problem.
            // Skip and let the manager finish manually.
            if ($mode === League::FORMAT_INDIVIDUAL && $cancha->matches->whereNotNull('date')->isNotEmpty()) {
                continue;
            }

            $canchasToSchedule[] = $cancha;
        }

        if (empty($canchasToSchedule)) {
            return [
                'ok' => true,
                'placed' => 0,
                'skipped' => 0,
                'message' => 'No hay canchas pendientes para programar.',
            ];
        }

        // Try multiple random seeds; commit the first attempt that places everything
        $bestAttempt = null;
        for ($attempt = 1; $attempt <= self::MAX_ATTEMPTS; $attempt++) {
            $result = $this->tryGenerate(
                $canchasToSchedule,
                $dates,
                $slots,
                $pistas,
                $alreadyOccupied,
                $mode
            );
            if ($result['placed_all']) {
                $bestAttempt = $result;
                break;
            }
            // Track the best partial attempt as fallback
            if ($bestAttempt === null || $result['placements_count'] > $bestAttempt['placements_count']) {
                $bestAttempt = $result;
            }
        }

        // Commit the best attempt we found
        $placed = 0;
        DB::transaction(function () use ($bestAttempt, &$placed) {
            foreach ($bestAttempt['placements'] as $p) {
                $match = GameMatch::find($p['match_id']);
                if (!$match) continue;
                $this->scheduler->scheduleMatch($match, $p['date'], $p['time_slot'], $p['pista_id']);
                $placed++;
            }
        });

        $skipped = count($bestAttempt['unplaced_canchas']);

        return [
            'ok'      => $bestAttempt['placed_all'],
            'placed'  => $placed,
            'skipped' => $skipped,
            'message' => $bestAttempt['placed_all']
                ? "Se programaron {$placed} partidos."
                : "Se programaron {$placed} partidos; quedaron {$skipped} canchas sin asignar. Revisa horarios y pistas disponibles, o asígnalas manualmente.",
            'unplaced_cancha_labels' => $bestAttempt['unplaced_cancha_labels'],
        ];
    }

    /**
     * Single attempt with a randomized order. Returns:
     *   placements: array of [match_id, date, time_slot, pista_id]
     *   unplaced_canchas: array of Cancha objects
     *   placed_all: bool
     */
    private function tryGenerate(
        array $canchas,
        array $dates,
        array $slots,
        $pistas,
        array $alreadyOccupied,
        string $mode
    ): array {
        // Shuffle canchas so different attempts try different orderings
        $shuffled = collect($canchas)->shuffle()->values();

        // Occupied: keyed "date|slot|pista_id" => true (cell taken)
        $occupied = $alreadyOccupied;
        // Player schedule: keyed "playerId|date|slot" => true
        $playerBusy = $this->buildPlayerBusyMap($canchas[0]->jornada_id);

        $placements = [];
        $unplacedCanchas = [];

        foreach ($shuffled as $cancha) {
            $matchesToPlace = $cancha->matches->whereNull('date')->values();
            if ($matchesToPlace->isEmpty()) continue;

            $placement = $this->findPlacementForCancha(
                $cancha,
                $matchesToPlace,
                $dates,
                $slots,
                $pistas,
                $occupied,
                $playerBusy,
                $mode
            );

            if ($placement === null) {
                $unplacedCanchas[] = $cancha;
                continue;
            }

            // Commit to the in-memory maps (not DB yet)
            foreach ($placement as $p) {
                $occupied["{$p['date']}|{$p['time_slot']}|{$p['pista_id']}"] = true;
                foreach ($p['player_ids'] as $pid) {
                    $playerBusy["{$pid}|{$p['date']}|{$p['time_slot']}"] = true;
                }
                $placements[] = [
                    'match_id'  => $p['match_id'],
                    'date'      => $p['date'],
                    'time_slot' => $p['time_slot'],
                    'pista_id'  => $p['pista_id'],
                ];
            }
        }

        return [
            'placements'             => $placements,
            'placements_count'       => count($placements),
            'unplaced_canchas'       => $unplacedCanchas,
            'unplaced_cancha_labels' => array_map(fn($c) => $c->label, $unplacedCanchas),
            'placed_all'             => empty($unplacedCanchas),
        ];
    }

    /**
     * Find a valid placement for a cancha's matches. Returns array of placements
     * (one per match), or null if no valid placement exists with current occupancy.
     */
    private function findPlacementForCancha(
        Cancha $cancha,
        $matchesToPlace,
        array $dates,
        array $slots,
        $pistas,
        array $occupied,
        array $playerBusy,
        string $mode
    ): ?array {
        // Shuffle the search space for randomness
        $shuffledDates  = collect($dates)->shuffle()->values();
        $shuffledPistas = $pistas->shuffle()->values();

        // For individual mode we need N consecutive slots on the same pista (same date)
        $consecutiveNeeded = $mode === League::FORMAT_INDIVIDUAL ? $matchesToPlace->count() : 1;

        foreach ($shuffledDates as $date) {
            $dateStr = $date->toDateString();
            foreach ($shuffledPistas as $pista) {
                // Try every possible starting slot
                $startable = max(0, count($slots) - $consecutiveNeeded);
                for ($startIdx = 0; $startIdx <= $startable; $startIdx++) {

                    // Build candidate cells
                    $candidateCells = [];
                    $cellsAllOpen = true;
                    for ($i = 0; $i < $consecutiveNeeded; $i++) {
                        $slot = $slots[$startIdx + $i];
                        $key = "{$dateStr}|{$slot}|{$pista->id}";
                        if (isset($occupied[$key])) {
                            $cellsAllOpen = false;
                            break;
                        }
                        $candidateCells[] = ['date' => $dateStr, 'slot' => $slot, 'pista_id' => $pista->id];
                    }
                    if (!$cellsAllOpen) continue;

                    // Check player conflicts for each match
                    $playersOk = true;
                    foreach ($matchesToPlace as $i => $match) {
                        $cell = $candidateCells[$i];
                        $playerIds = array_merge(
                            $match->team_a_player_ids ?? [],
                            $match->team_b_player_ids ?? []
                        );
                        foreach ($playerIds as $pid) {
                            $busyKey = "{$pid}|{$cell['date']}|{$cell['slot']}";
                            if (isset($playerBusy[$busyKey])) {
                                $playersOk = false;
                                break 2;
                            }
                        }
                    }
                    if (!$playersOk) continue;

                    // Build the result
                    $result = [];
                    foreach ($matchesToPlace as $i => $match) {
                        $result[] = [
                            'match_id'   => $match->id,
                            'date'       => $candidateCells[$i]['date'],
                            'time_slot'  => $candidateCells[$i]['slot'],
                            'pista_id'   => $candidateCells[$i]['pista_id'],
                            'player_ids' => array_merge(
                                $match->team_a_player_ids ?? [],
                                $match->team_b_player_ids ?? []
                            ),
                        ];
                    }
                    return $result;
                }
            }
        }
        return null;
    }

    /** Cells already occupied by matches that we're preserving. */
    private function buildOccupancyMap(Jornada $jornada): array
    {
        $occupied = [];
        $matches = GameMatch::query()
            ->whereIn('cancha_id', $jornada->canchas->pluck('id'))
            ->whereNotNull('date')
            ->whereNotNull('time_slot')
            ->whereNotNull('pista_id')
            ->get();

        foreach ($matches as $m) {
            $key = $m->date->toDateString() . "|{$m->time_slot}|{$m->pista_id}";
            $occupied[$key] = true;
        }
        return $occupied;
    }

    /** Player busy map from already-placed matches in this jornada. */
    private function buildPlayerBusyMap(int $jornadaId): array
    {
        $busy = [];
        $matches = GameMatch::query()
            ->whereIn('cancha_id', function ($q) use ($jornadaId) {
                $q->select('id')->from('canchas')->where('jornada_id', $jornadaId);
            })
            ->whereNotNull('date')
            ->whereNotNull('time_slot')
            ->get();

        foreach ($matches as $m) {
            $playerIds = array_merge($m->team_a_player_ids ?? [], $m->team_b_player_ids ?? []);
            foreach ($playerIds as $pid) {
                $key = "{$pid}|{$m->date->toDateString()}|{$m->time_slot}";
                $busy[$key] = true;
            }
        }
        return $busy;
    }

    /** Borrowed from MatchSchedulingService — kept local so the service is self-contained. */
    private function enumerateDates(Jornada $jornada): array
    {
        $league = $jornada->group->league;
        $days = collect($league->days_of_week ?? []);
        $map = ['mon' => 1, 'tue' => 2, 'wed' => 3, 'thu' => 4, 'fri' => 5, 'sat' => 6, 'sun' => 7];
        $allowedIso = $days->map(fn($d) => $map[$d] ?? null)->filter()->all();

        $start = $jornada->window_start
            ? Carbon::parse($jornada->window_start)
            : now()->startOfWeek();
        $end = $jornada->window_end
            ? Carbon::parse($jornada->window_end)
            : (clone $start)->addDays(13);

        $out = [];
        $cursor = $start->copy();
        while ($cursor->lte($end)) {
            if (in_array($cursor->dayOfWeekIso, $allowedIso, true)) {
                $out[] = $cursor->copy();
            }
            $cursor->addDay();
        }
        return $out;
    }
}
