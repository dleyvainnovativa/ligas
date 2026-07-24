<?php

namespace App\Services;

use App\Models\Cancha;
use App\Models\Jornada;
use App\Models\Pair;
use App\Models\Player;
use Illuminate\Support\Facades\DB;

class CanchaService
{
    /** Assign a player to a cancha at a given slot, removing them from any other cancha in the same jornada. */
    public function assignPlayer(Cancha $cancha, Player $player, ?int $preferredSlot = null): int
    {
        return DB::transaction(function () use ($cancha, $player, $preferredSlot) {
            $jornadaId = $cancha->jornada_id;

            // Remove from any cancha in this jornada
            DB::table('cancha_player')
                ->whereIn('cancha_id', function ($q) use ($jornadaId) {
                    $q->select('id')->from('canchas')->where('jornada_id', $jornadaId);
                })
                ->where('player_id', $player->id)
                ->delete();

            // Find a free slot
            $usedSlots = DB::table('cancha_player')
                ->where('cancha_id', $cancha->id)
                ->pluck('slot')->all();

            if (count($usedSlots) >= Cancha::MAX_PLAYERS) {
                throw new \DomainException('Esta cancha ya está llena (máximo 4 jugadores).');
            }

            $slot = null;
            if ($preferredSlot && in_array($preferredSlot, [1, 2, 3, 4], true) && !in_array($preferredSlot, $usedSlots, true)) {
                $slot = $preferredSlot;
            } else {
                for ($i = 1; $i <= Cancha::MAX_PLAYERS; $i++) {
                    if (!in_array($i, $usedSlots, true)) {
                        $slot = $i;
                        break;
                    }
                }
            }

            DB::table('cancha_player')->insert([
                'cancha_id'  => $cancha->id,
                'player_id'  => $player->id,
                'slot'       => $slot,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            return $slot;
        });
    }

    public function unassignPlayer(Player $player, Jornada $jornada): void
    {
        DB::table('cancha_player')
            ->whereIn('cancha_id', function ($q) use ($jornada) {
                $q->select('id')->from('canchas')->where('jornada_id', $jornada->id);
            })
            ->where('player_id', $player->id)
            ->delete();
    }

    public function assignPair(Cancha $cancha, Pair $pair): int
    {
        return DB::transaction(function () use ($cancha, $pair) {
            $jornadaId = $cancha->jornada_id;

            DB::table('cancha_pair')
                ->whereIn('cancha_id', function ($q) use ($jornadaId) {
                    $q->select('id')->from('canchas')->where('jornada_id', $jornadaId);
                })
                ->where('pair_id', $pair->id)
                ->delete();

            $usedSlots = DB::table('cancha_pair')->where('cancha_id', $cancha->id)->pluck('slot')->all();
            if (count($usedSlots) >= Cancha::MAX_PAIRS) {
                throw new \DomainException('Esta cancha ya está llena (máximo 2 parejas).');
            }

            $slot = !in_array(1, $usedSlots, true) ? 1 : 2;

            DB::table('cancha_pair')->insert([
                'cancha_id'  => $cancha->id,
                'pair_id'    => $pair->id,
                'slot'       => $slot,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            return $slot;
        });
    }

    public function unassignPair(Pair $pair, Jornada $jornada): void
    {
        DB::table('cancha_pair')
            ->whereIn('cancha_id', function ($q) use ($jornada) {
                $q->select('id')->from('canchas')->where('jornada_id', $jornada->id);
            })
            ->where('pair_id', $pair->id)
            ->delete();
    }

    /**
     * Phase 4 stub: shuffles the unassigned roster into canchas of 4 (or pairs of 2).
     * Real social-golfer / americano-rotation algorithm is Phase 9.
     */
    public function autoFill(Jornada $jornada): void
    {
        DB::transaction(function () use ($jornada) {
            $jornada->load(['group.league', 'canchas']);
            $league = $jornada->group->league;

            // Wipe existing assignments in this jornada (manager clicked auto-fill explicitly)
            $canchaIds = $jornada->canchas->pluck('id');
            DB::table('cancha_player')->whereIn('cancha_id', $canchaIds)->delete();
            DB::table('cancha_pair')->whereIn('cancha_id', $canchaIds)->delete();

            if ($league->format === 'pairs') {
                $pairs = $jornada->group->pairs()->get()->shuffle()->values();
                $needed = (int) ceil($pairs->count() / Cancha::MAX_PAIRS);

                $this->ensureCanchaCount($jornada, $needed);
                $canchas = $jornada->canchas()->orderBy('position')->get();

                foreach ($pairs as $i => $pair) {
                    $cancha = $canchas[intdiv($i, Cancha::MAX_PAIRS)] ?? null;
                    if (!$cancha) break;
                    $this->assignPair($cancha, $pair);
                }
            } else {
                $players = $jornada->group->players()->get()->shuffle()->values();
                $needed = (int) ceil($players->count() / Cancha::MAX_PLAYERS);

                $this->ensureCanchaCount($jornada, $needed);
                $canchas = $jornada->canchas()->orderBy('position')->get();

                foreach ($players as $i => $player) {
                    $cancha = $canchas[intdiv($i, Cancha::MAX_PLAYERS)] ?? null;
                    if (!$cancha) break;
                    $this->assignPlayer($cancha, $player);
                }
            }
        });
    }

    public function ensureCanchaCount(Jornada $jornada, int $needed): void
    {
        $current = $jornada->canchas()->count();

        if ($current < $needed) {
            for ($i = $current; $i < $needed; $i++) {
                $jornada->canchas()->create([
                    'label'    => 'Cancha ' . ($i + 1),
                    'position' => $i + 1,
                ]);
            }
        } elseif ($current > $needed) {
            // Remove only empty canchas from the tail
            $jornada->canchas()->orderByDesc('position')->get()->each(function ($c) use (&$current, $needed) {
                if ($current <= $needed) return;
                if ($c->players()->count() === 0 && $c->pairs()->count() === 0) {
                    $c->delete();
                    $current--;
                }
            });
        }
    }
    /**
     * Swap two players between canchas (or between a cancha and the pool).
     * If targetCanchaId is null, the source player moves to the pool — single side move.
     * Returns the new slots assigned.
     */
    public function swapPlayers(Jornada $jornada, Player $sourcePlayer, Player $targetPlayer): void
    {
        DB::transaction(function () use ($jornada, $sourcePlayer, $targetPlayer) {
            // Find current cancha + slot for each player in this jornada
            $sourceRow = DB::table('cancha_player')
                ->whereIn('cancha_id', $jornada->canchas()->pluck('id'))
                ->where('player_id', $sourcePlayer->id)
                ->first();

            $targetRow = DB::table('cancha_player')
                ->whereIn('cancha_id', $jornada->canchas()->pluck('id'))
                ->where('player_id', $targetPlayer->id)
                ->first();

            // Detach both (so the unique constraints don't fire mid-swap)
            DB::table('cancha_player')
                ->where('player_id', $sourcePlayer->id)
                ->whereIn('cancha_id', $jornada->canchas()->pluck('id'))
                ->delete();

            DB::table('cancha_player')
                ->where('player_id', $targetPlayer->id)
                ->whereIn('cancha_id', $jornada->canchas()->pluck('id'))
                ->delete();

            // Re-insert each at the OTHER's previous slot
            if ($sourceRow && $targetRow) {
                DB::table('cancha_player')->insert([
                    'cancha_id'  => $targetRow->cancha_id,
                    'player_id'  => $sourcePlayer->id,
                    'slot'       => $targetRow->slot,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
                DB::table('cancha_player')->insert([
                    'cancha_id'  => $targetRow->cancha_id === $sourceRow->cancha_id
                        ? $sourceRow->cancha_id   // same cancha, just slot swap
                        : $sourceRow->cancha_id,
                    'player_id'  => $targetPlayer->id,
                    'slot'       => $sourceRow->slot,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            } else if ($sourceRow && !$targetRow) {
                // Target was in the pool, source was in a cancha → they trade places
                DB::table('cancha_player')->insert([
                    'cancha_id'  => $sourceRow->cancha_id,
                    'player_id'  => $targetPlayer->id,
                    'slot'       => $sourceRow->slot,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
                // Source is now in the pool (no insert needed)
            } else if (!$sourceRow && $targetRow) {
                // Source was in the pool, target was in a cancha → they trade places
                DB::table('cancha_player')->insert([
                    'cancha_id'  => $targetRow->cancha_id,
                    'player_id'  => $sourcePlayer->id,
                    'slot'       => $targetRow->slot,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
                // Target is now in the pool
            }
            // Both in pool → nothing to do
        });
    }

    public function swapPairs(Jornada $jornada, Pair $sourcePair, Pair $targetPair): void
    {
        DB::transaction(function () use ($jornada, $sourcePair, $targetPair) {
            $canchaIds = $jornada->canchas()->pluck('id');

            $sourceRow = DB::table('cancha_pair')
                ->whereIn('cancha_id', $canchaIds)
                ->where('pair_id', $sourcePair->id)
                ->first();
            $targetRow = DB::table('cancha_pair')
                ->whereIn('cancha_id', $canchaIds)
                ->where('pair_id', $targetPair->id)
                ->first();

            DB::table('cancha_pair')
                ->whereIn('cancha_id', $canchaIds)
                ->whereIn('pair_id', [$sourcePair->id, $targetPair->id])
                ->delete();

            if ($sourceRow && $targetRow) {
                DB::table('cancha_pair')->insert([
                    ['cancha_id' => $targetRow->cancha_id, 'pair_id' => $sourcePair->id, 'slot' => $targetRow->slot, 'created_at' => now(), 'updated_at' => now()],
                    ['cancha_id' => $sourceRow->cancha_id, 'pair_id' => $targetPair->id, 'slot' => $sourceRow->slot, 'created_at' => now(), 'updated_at' => now()],
                ]);
            } else if ($sourceRow) {
                DB::table('cancha_pair')->insert([
                    'cancha_id'  => $sourceRow->cancha_id,
                    'pair_id'    => $targetPair->id,
                    'slot'       => $sourceRow->slot,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            } else if ($targetRow) {
                DB::table('cancha_pair')->insert([
                    'cancha_id'  => $targetRow->cancha_id,
                    'pair_id'    => $sourcePair->id,
                    'slot'       => $targetRow->slot,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        });
    }
    /**
     * Reset a cancha back to unscheduled: clear its schedule (date/time/pista)
     * and wipe all round results. Keeps the cancha row and its player/pair
     * assignments intact. Used when a roster change invalidates a cancha that
     * was already scheduled or had results.
     */
    public function resetCanchaScheduleAndResults(Cancha $cancha): void
    {
        DB::transaction(function () use ($cancha) {
            // Clear results on every round of this cancha
            foreach ($cancha->rounds()->get() as $round) {
                // Drop any pending proposals tied to this round
                \App\Models\MatchScoreProposal::where('match_id', $round->id)->delete();

                $round->update([
                    'sets'                => null,
                    'winner'              => null,
                    'no_show_player_ids'  => null,
                    'suplente_player_ids' => null,
                    'status'              => \App\Models\GameMatch::STATUS_PENDING,
                ]);
            }

            // Clear the cancha's schedule
            $cancha->update([
                'date'      => null,
                'time_slot' => null,
                'pista_id'  => null,
                'status'    => Cancha::STATUS_UNSCHEDULED,
            ]);
        });
    }

    /**
     * Does this cancha have anything worth warning about before a reset?
     * True if it's scheduled OR any round has recorded results.
     */
    public function canchaHasScheduleOrResults(Cancha $cancha): bool
    {
        if ($cancha->date || $cancha->time_slot || $cancha->pista_id) {
            return true;
        }
        return $cancha->rounds()
            ->whereNotNull('sets')
            ->exists();
    }

    /**
     * For an assignPlayer operation: the affected canchas are the one the player
     * currently sits in (if any) and the destination cancha. Returns those among
     * them that have schedule/results (i.e. would be reset).
     */
    public function affectedByAssignPlayer(Cancha $destination, Player $player): \Illuminate\Support\Collection
    {
        $jornadaId = $destination->jornada_id;

        $affected = collect();

        // Destination cancha (unless the player is already in it)
        // Current cancha of the player in this jornada
        $currentCanchaId = DB::table('cancha_player')
            ->whereIn('cancha_id', function ($q) use ($jornadaId) {
                $q->select('id')->from('canchas')->where('jornada_id', $jornadaId);
            })
            ->where('player_id', $player->id)
            ->value('cancha_id');

        $ids = collect([$destination->id, $currentCanchaId])->filter()->unique();

        foreach ($ids as $id) {
            $cancha = Cancha::find($id);
            if ($cancha && $this->canchaHasScheduleOrResults($cancha)) {
                $affected->push($cancha);
            }
        }

        return $affected;
    }

    /**
     * For a swapPlayers operation: affected canchas are the two players' current
     * canchas. If it's an intra-cancha slot swap (same cancha), the player SET is
     * unchanged, so nothing needs resetting — we return empty in that case.
     */
    public function affectedBySwapPlayers(Jornada $jornada, Player $source, Player $target): \Illuminate\Support\Collection
    {
        $canchaIds = $jornada->canchas()->pluck('id');

        $sourceCanchaId = DB::table('cancha_player')
            ->whereIn('cancha_id', $canchaIds)->where('player_id', $source->id)->value('cancha_id');
        $targetCanchaId = DB::table('cancha_player')
            ->whereIn('cancha_id', $canchaIds)->where('player_id', $target->id)->value('cancha_id');

        // Same cancha → pure slot reorder, player sets unchanged → no reset needed
        if ($sourceCanchaId && $targetCanchaId && $sourceCanchaId === $targetCanchaId) {
            return collect();
        }

        $affected = collect();
        foreach (collect([$sourceCanchaId, $targetCanchaId])->filter()->unique() as $id) {
            $cancha = Cancha::find($id);
            if ($cancha && $this->canchaHasScheduleOrResults($cancha)) {
                $affected->push($cancha);
            }
        }
        return $affected;
    }

    /**
     * For unassignPlayer: the one cancha the player currently sits in.
     */
    public function affectedByUnassignPlayer(Jornada $jornada, Player $player): \Illuminate\Support\Collection
    {
        $canchaId = DB::table('cancha_player')
            ->whereIn('cancha_id', $jornada->canchas()->pluck('id'))
            ->where('player_id', $player->id)
            ->value('cancha_id');

        if (!$canchaId) return collect();

        $cancha = Cancha::find($canchaId);
        return ($cancha && $this->canchaHasScheduleOrResults($cancha))
            ? collect([$cancha])
            : collect();
    }

    public function affectedByAssignPair(Cancha $destination, Pair $pair): \Illuminate\Support\Collection
    {
        $jornadaId = $destination->jornada_id;

        $currentCanchaId = DB::table('cancha_pair')
            ->whereIn('cancha_id', function ($q) use ($jornadaId) {
                $q->select('id')->from('canchas')->where('jornada_id', $jornadaId);
            })
            ->where('pair_id', $pair->id)
            ->value('cancha_id');

        $affected = collect();
        foreach (collect([$destination->id, $currentCanchaId])->filter()->unique() as $id) {
            $cancha = Cancha::find($id);
            if ($cancha && $this->canchaHasScheduleOrResults($cancha)) {
                $affected->push($cancha);
            }
        }
        return $affected;
    }

    public function affectedBySwapPairs(Jornada $jornada, Pair $source, Pair $target): \Illuminate\Support\Collection
    {
        $canchaIds = $jornada->canchas()->pluck('id');

        $sourceCanchaId = DB::table('cancha_pair')
            ->whereIn('cancha_id', $canchaIds)->where('pair_id', $source->id)->value('cancha_id');
        $targetCanchaId = DB::table('cancha_pair')
            ->whereIn('cancha_id', $canchaIds)->where('pair_id', $target->id)->value('cancha_id');

        if ($sourceCanchaId && $targetCanchaId && $sourceCanchaId === $targetCanchaId) {
            return collect();
        }

        $affected = collect();
        foreach (collect([$sourceCanchaId, $targetCanchaId])->filter()->unique() as $id) {
            $cancha = Cancha::find($id);
            if ($cancha && $this->canchaHasScheduleOrResults($cancha)) {
                $affected->push($cancha);
            }
        }
        return $affected;
    }

    public function affectedByUnassignPair(Jornada $jornada, Pair $pair): \Illuminate\Support\Collection
    {
        $canchaId = DB::table('cancha_pair')
            ->whereIn('cancha_id', $jornada->canchas()->pluck('id'))
            ->where('pair_id', $pair->id)
            ->value('cancha_id');

        if (!$canchaId) return collect();

        $cancha = Cancha::find($canchaId);
        return ($cancha && $this->canchaHasScheduleOrResults($cancha))
            ? collect([$cancha])
            : collect();
    }


    /** All canchas whose player set changes, regardless of schedule state. */
    public function touchedByAssignPlayer(Cancha $destination, Player $player): \Illuminate\Support\Collection
    {
        $jornadaId = $destination->jornada_id;

        $currentCanchaId = DB::table('cancha_player')
            ->whereIn('cancha_id', function ($q) use ($jornadaId) {
                $q->select('id')->from('canchas')->where('jornada_id', $jornadaId);
            })
            ->where('player_id', $player->id)
            ->value('cancha_id');

        return collect([$destination->id, $currentCanchaId])
            ->filter()->unique()
            ->map(fn($id) => Cancha::find($id))
            ->filter()
            ->values();
    }

    public function touchedBySwapPlayers(Jornada $jornada, Player $source, Player $target): \Illuminate\Support\Collection
    {
        $canchaIds = $jornada->canchas()->pluck('id');

        $sourceCanchaId = DB::table('cancha_player')
            ->whereIn('cancha_id', $canchaIds)->where('player_id', $source->id)->value('cancha_id');
        $targetCanchaId = DB::table('cancha_player')
            ->whereIn('cancha_id', $canchaIds)->where('player_id', $target->id)->value('cancha_id');

        return collect([$sourceCanchaId, $targetCanchaId])
            ->filter()->unique()
            ->map(fn($id) => Cancha::find($id))
            ->filter()
            ->values();
    }

    public function touchedByUnassignPlayer(Jornada $jornada, Player $player): \Illuminate\Support\Collection
    {
        $canchaId = DB::table('cancha_player')
            ->whereIn('cancha_id', $jornada->canchas()->pluck('id'))
            ->where('player_id', $player->id)
            ->value('cancha_id');

        return $canchaId
            ? collect([Cancha::find($canchaId)])->filter()->values()
            : collect();
    }
}
