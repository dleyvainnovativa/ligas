<?php

namespace App\Services;

use App\Models\Cancha;
use App\Models\GameMatch;
use App\Models\Jornada;
use App\Models\League;
use Illuminate\Support\Facades\DB;

class MatchSchedulingService
{
    /** Ensure a cancha has its round records (1 for pairs, 3 for individual). */
    public function ensureRounds(Cancha $cancha): void
    {
        $cancha->load([
            'jornada.group.league',
            'players' => fn($q) => $q->orderBy('cancha_player.slot'),
            'pairs'   => fn($q) => $q->orderBy('cancha_pair.slot'),
        ]);
        $format = $cancha->jornada->group->league->format;
        $existing = $cancha->rounds()->pluck('rotation_index')->all();

        if ($format === League::FORMAT_PAIRS) {
            $this->ensurePairsRound($cancha, $existing);
        } else {
            $this->ensureIndividualRounds($cancha, $existing);
        }
    }

    // Backward-compat alias for older code that still calls ensureMatches
    public function ensureMatches(Cancha $cancha): void
    {
        $this->ensureRounds($cancha);
    }

    private function ensurePairsRound(Cancha $cancha, array $existing): void
    {
        if (in_array(1, $existing, true)) return;
        if ($cancha->pairs->count() < 2) return;

        $pairA = $cancha->pairs->firstWhere('pivot.slot', 1) ?? $cancha->pairs[0];
        $pairB = $cancha->pairs->firstWhere('pivot.slot', 2) ?? $cancha->pairs[1];

        $cancha->rounds()->create([
            'rotation_index'    => 1,
            'team_a_player_ids' => [$pairA->player_a_id, $pairA->player_b_id],
            'team_b_player_ids' => [$pairB->player_a_id, $pairB->player_b_id],
            'team_a_pair_id'    => $pairA->id,
            'team_b_pair_id'    => $pairB->id,
            'status'            => GameMatch::STATUS_PENDING,
        ]);
    }

    private function ensureIndividualRounds(Cancha $cancha, array $existing): void
    {
        $bySlot = [];
        foreach ($cancha->players as $p) {
            $bySlot[$p->pivot->slot] = $p;
        }
        if (count(array_filter([1, 2, 3, 4], fn($s) => isset($bySlot[$s]))) < 4) return;

        [$a, $b, $c, $d] = [$bySlot[1], $bySlot[2], $bySlot[3], $bySlot[4]];

        $rotations = [
            1 => [[$a->id, $b->id], [$c->id, $d->id]],
            2 => [[$a->id, $c->id], [$b->id, $d->id]],
            3 => [[$a->id, $d->id], [$b->id, $c->id]],
        ];

        foreach ($rotations as $index => [$teamA, $teamB]) {
            if (in_array($index, $existing, true)) continue;
            $cancha->rounds()->create([
                'rotation_index'    => $index,
                'team_a_player_ids' => $teamA,
                'team_b_player_ids' => $teamB,
                'status'            => GameMatch::STATUS_PENDING,
            ]);
        }
    }

    /**
     * Schedule (or unschedule) a cancha. Passing null for any of date/time/pista clears the schedule.
     */
    public function scheduleCancha(Cancha $cancha, ?string $date, ?string $timeSlot, ?int $pistaId): Cancha
    {
        if ($date === null || $timeSlot === null || $pistaId === null) {
            $cancha->update([
                'date'      => null,
                'time_slot' => null,
                'pista_id'  => null,
                'status'    => $this->deriveStatus($cancha, scheduled: false),
            ]);
            return $cancha->fresh();
        }

        $league = $cancha->jornada->group->league;
        $allowedPistaIds = $league->sedes()->with('pistas')->get()->flatMap->pistas->pluck('id')->all();
        abort_unless(in_array($pistaId, $allowedPistaIds, true), 422, 'La pista no pertenece a esta liga.');

        $cancha->update([
            'date'      => $date,
            'time_slot' => $timeSlot,
            'pista_id'  => $pistaId,
            'status'    => $this->deriveStatus($cancha->fresh(), scheduled: true),
        ]);
        return $cancha->fresh();
    }

    /** Recompute cancha status based on rounds + schedule. */
    public function deriveStatus(Cancha $cancha, ?bool $scheduled = null): string
    {
        $isScheduled = $scheduled ?? ($cancha->date && $cancha->time_slot && $cancha->pista_id);
        if (!$isScheduled) return Cancha::STATUS_UNSCHEDULED;

        $rounds = $cancha->rounds()->get();
        if ($rounds->isEmpty()) return Cancha::STATUS_SCHEDULED;
        $allCompleted = $rounds->every(fn($r) => $r->status === GameMatch::STATUS_COMPLETED);
        return $allCompleted ? Cancha::STATUS_COMPLETED : Cancha::STATUS_SCHEDULED;
    }

    /** Detect player double-booking across all canchas in a jornada. */
    public function detectConflicts(Jornada $jornada): array
    {
        $canchas = $jornada->canchas()->with(['players', 'pairs'])->get();

        // Build (date|slot) → array of player_ids being busy and which canchas
        $buckets = []; // key: "playerId|date|slot" => cancha_ids[]
        foreach ($canchas as $cancha) {
            if (!$cancha->date || !$cancha->time_slot) continue;
            $playerIds = $cancha->players->pluck('id')->all();
            // In pairs mode, players come via pairs
            if (empty($playerIds)) {
                foreach ($cancha->pairs as $pair) {
                    $playerIds[] = $pair->player_a_id;
                    $playerIds[] = $pair->player_b_id;
                }
            }
            foreach ($playerIds as $pid) {
                $key = "{$pid}|{$cancha->date->toDateString()}|{$cancha->time_slot}";
                $buckets[$key][] = $cancha->id;
            }
        }

        $allPlayerIds = collect(array_keys($buckets))
            ->map(fn($k) => (int) explode('|', $k)[0])
            ->unique()->values();

        $playerNames = \App\Models\Player::query()
            ->whereIn('id', $allPlayerIds)
            ->pluck('full_name', 'id');

        $conflicts = [];
        foreach ($buckets as $key => $canchaIds) {
            if (count($canchaIds) < 2) continue;
            [$pid, $date, $slot] = explode('|', $key);
            $pid = (int) $pid;
            $conflicts[] = [
                'player_id'   => $pid,
                'player_name' => $playerNames[$pid] ?? 'Jugador desconocido',
                'date'        => $date,
                'time_slot'   => $slot,
                'cancha_ids'  => $canchaIds,
            ];
        }
        return $conflicts;
    }

    /** Carbon-aware date enumeration for the jornada window. */
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
