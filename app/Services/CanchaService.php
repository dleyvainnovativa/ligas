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
}
