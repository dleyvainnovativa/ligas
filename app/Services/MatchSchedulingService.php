<?php

namespace App\Services;

use App\Models\Cancha;
use App\Models\GameMatch;
use App\Models\Jornada;
use App\Models\League;
use App\Models\Pista;
use Illuminate\Support\Facades\DB;

class MatchSchedulingService
{
    /**
     * Ensure a cancha has its match records.
     * - Individual mode: 3 matches with rotations AB-CD, AC-BD, AD-BC
     * - Pairs mode: 1 match with the two pairs
     *
     * Idempotent: if matches already exist (by rotation_index), we leave them alone.
     */
    public function ensureMatches(Cancha $cancha): void
    {
        $cancha->load(['jornada.group.league', 'players' => fn($q) => $q->orderBy('cancha_player.slot'), 'pairs' => fn($q) => $q->orderBy('cancha_pair.slot')]);
        $format = $cancha->jornada->group->league->format;

        $existing = $cancha->matches()->pluck('rotation_index')->all();

        if ($format === League::FORMAT_PAIRS) {
            $this->ensurePairsMatch($cancha, $existing);
        } else {
            $this->ensureIndividualMatches($cancha, $existing);
        }
    }

    private function ensurePairsMatch(Cancha $cancha, array $existing): void
    {
        if (in_array(1, $existing, true)) return;
        if ($cancha->pairs->count() < 2) return; // need two pairs to form a match

        $pairA = $cancha->pairs->firstWhere('pivot.slot', 1) ?? $cancha->pairs[0];
        $pairB = $cancha->pairs->firstWhere('pivot.slot', 2) ?? $cancha->pairs[1];

        $cancha->matches()->create([
            'rotation_index'    => 1,
            'team_a_player_ids' => [$pairA->player_a_id, $pairA->player_b_id],
            'team_b_player_ids' => [$pairB->player_a_id, $pairB->player_b_id],
            'team_a_pair_id'    => $pairA->id,
            'team_b_pair_id'    => $pairB->id,
            'status'            => GameMatch::STATUS_UNSCHEDULED,
        ]);
    }

    private function ensureIndividualMatches(Cancha $cancha, array $existing): void
    {
        // Need 4 players at known slots
        $bySlot = [];
        foreach ($cancha->players as $p) {
            $bySlot[$p->pivot->slot] = $p;
        }
        if (count(array_filter([1, 2, 3, 4], fn($s) => isset($bySlot[$s]))) < 4) return;

        [$a, $b, $c, $d] = [$bySlot[1], $bySlot[2], $bySlot[3], $bySlot[4]];

        $rotations = [
            1 => [[$a->id, $b->id], [$c->id, $d->id]],   // AB vs CD
            2 => [[$a->id, $c->id], [$b->id, $d->id]],   // AC vs BD
            3 => [[$a->id, $d->id], [$b->id, $c->id]],   // AD vs BC
        ];

        foreach ($rotations as $index => [$teamA, $teamB]) {
            if (in_array($index, $existing, true)) continue;

            $cancha->matches()->create([
                'rotation_index'    => $index,
                'team_a_player_ids' => $teamA,
                'team_b_player_ids' => $teamB,
                'status'            => GameMatch::STATUS_UNSCHEDULED,
            ]);
        }
    }

    /**
     * Schedule a match at (date, time_slot, pista). Sets status appropriately.
     * Passing null for date or pista clears the schedule.
     */
    public function scheduleMatch(GameMatch $match, ?string $date, ?string $timeSlot, ?int $pistaId): GameMatch
    {
        if ($date === null || $timeSlot === null || $pistaId === null) {
            $match->update([
                'date'      => null,
                'time_slot' => null,
                'pista_id'  => null,
                'status'    => GameMatch::STATUS_UNSCHEDULED,
            ]);
            return $match->fresh();
        }

        // Check pista belongs to the league
        $cancha = $match->cancha()->with('jornada.group.league.sedes.pistas')->first();
        $league = $cancha->jornada->group->league;
        $allowedPistaIds = $league->sedes->flatMap->pistas->pluck('id')->all();
        abort_unless(in_array($pistaId, $allowedPistaIds, true), 422, 'La pista no pertenece a esta liga.');

        $match->update([
            'date'      => $date,
            'time_slot' => $timeSlot,
            'pista_id'  => $pistaId,
            'status'    => GameMatch::STATUS_SCHEDULED,
        ]);
        return $match->fresh();
    }

    /**
     * "Auto-fit" a whole cancha onto N consecutive time slots starting at (date, startSlot, pista).
     * - Individual mode: places 3 matches on consecutive time slots
     * - Pairs mode: places the 1 match at the given slot
     *
     * Returns array of [match_id => [date,time_slot,pista_id]] applied.
     */
    public function autoFitCancha(Cancha $cancha, string $date, string $startSlot, int $pistaId): array
    {
        $this->ensureMatches($cancha);

        $league = $cancha->jornada->group->league;
        $allSlots = $league->time_slots ?? [];
        // sort numerically so 9:00 < 18:00 lexicographic still works (HH:MM with zero-padding)
        sort($allSlots);

        $startIdx = array_search($startSlot, $allSlots, true);
        if ($startIdx === false) {
            throw new \DomainException('El horario no está definido en esta liga.');
        }

        $matches = $cancha->matches()->orderBy('rotation_index')->get();
        $applied = [];

        return DB::transaction(function () use ($matches, $allSlots, $startIdx, $date, $pistaId, &$applied) {
            foreach ($matches as $i => $match) {
                $slot = $allSlots[$startIdx + $i] ?? null;
                if (!$slot) {
                    throw new \DomainException('No hay suficientes horarios consecutivos para programar las rotaciones.');
                }
                $this->scheduleMatch($match, $date, $slot, $pistaId);
                $applied[$match->id] = ['date' => $date, 'time_slot' => $slot, 'pista_id' => $pistaId];
            }
            return $applied;
        });
    }

    /**
     * Detect player double-booking across all matches in a jornada.
     * Returns array of conflicts: [[player_id, date, time_slot, match_ids[]], ...]
     */
    public function detectConflicts(Jornada $jornada): array
    {
        $matches = GameMatch::query()
            ->whereIn('cancha_id', $jornada->canchas()->pluck('id'))
            ->whereNotNull('date')
            ->whereNotNull('time_slot')
            ->get();

        // Collect every player id that appears in any match, then fetch their names in one query
        $allPlayerIds = $matches->flatMap(fn($m) => array_merge(
            $m->team_a_player_ids ?? [],
            $m->team_b_player_ids ?? []
        ))->unique()->values();

        $playerNames = \App\Models\Player::query()
            ->whereIn('id', $allPlayerIds)
            ->pluck('full_name', 'id');

        $buckets = []; // key: "playerId|date|slot" => match_ids[]
        foreach ($matches as $m) {
            $playerIds = array_merge($m->team_a_player_ids ?? [], $m->team_b_player_ids ?? []);
            foreach ($playerIds as $pid) {
                $key = "{$pid}|{$m->date->toDateString()}|{$m->time_slot}";
                $buckets[$key][] = $m->id;
            }
        }

        $conflicts = [];
        foreach ($buckets as $key => $matchIds) {
            if (count($matchIds) < 2) continue;
            [$pid, $date, $slot] = explode('|', $key);
            $pid = (int) $pid;
            $conflicts[] = [
                'player_id'   => $pid,
                'player_name' => $playerNames[$pid] ?? 'Jugador desconocido',
                'date'        => $date,
                'time_slot'   => $slot,
                'match_ids'   => $matchIds,
            ];
        }
        return $conflicts;
    }
    public function enumerateDates(Jornada $jornada): array
    {
        $league = $jornada->group->league;
        $days = collect($league->days_of_week ?? []);
        $map = ['mon' => 1, 'tue' => 2, 'wed' => 3, 'thu' => 4, 'fri' => 5, 'sat' => 6, 'sun' => 7];
        $allowedIso = $days->map(fn($d) => $map[$d] ?? null)->filter()->all();

        $start = $jornada->window_start
            ? \Carbon\Carbon::parse($jornada->window_start)
            : now()->startOfWeek();
        $end = $jornada->window_end
            ? \Carbon\Carbon::parse($jornada->window_end)
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
