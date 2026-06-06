<?php

namespace App\Services;

use App\Models\Cancha;
use App\Models\Jornada;
use Illuminate\Support\Facades\DB;

class MatchAutoGenerateService
{
    private const MAX_ATTEMPTS = 30;

    public function __construct(private MatchSchedulingService $scheduler) {}

    public function generate(Jornada $jornada, bool $clearExisting = false): array
    {
        $jornada->load(['canchas.players', 'canchas.pairs', 'group.league.sedes.pistas']);
        $league = $jornada->group->league;

        // Optionally clear all current schedules
        if ($clearExisting) {
            DB::table('canchas')
                ->where('jornada_id', $jornada->id)
                ->update([
                    'date'      => null,
                    'time_slot' => null,
                    'pista_id'  => null,
                    'status'    => Cancha::STATUS_UNSCHEDULED,
                ]);
            $jornada->load('canchas');
        }

        $dates = $this->scheduler->enumerateDates($jornada);
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

        // Cells already taken by canchas we're preserving
        $occupied = $this->buildOccupancyMap($jornada);

        // Canchas needing a placement
        $todo = $jornada->canchas->filter(fn($c) => !$c->isScheduled())->values();
        if ($todo->isEmpty()) {
            return ['ok' => true, 'placed' => 0, 'skipped' => 0, 'message' => 'No hay canchas pendientes.'];
        }

        // Try multiple random seeds
        $bestAttempt = null;
        for ($attempt = 1; $attempt <= self::MAX_ATTEMPTS; $attempt++) {
            $result = $this->tryGenerate($todo, $dates, $slots, $pistas, $occupied);
            if ($result['placed_all']) {
                $bestAttempt = $result;
                break;
            }
            if ($bestAttempt === null || $result['placements_count'] > $bestAttempt['placements_count']) {
                $bestAttempt = $result;
            }
        }

        $placed = 0;
        DB::transaction(function () use ($bestAttempt, &$placed) {
            foreach ($bestAttempt['placements'] as $p) {
                $cancha = Cancha::find($p['cancha_id']);
                if (!$cancha) continue;
                $this->scheduler->scheduleCancha($cancha, $p['date'], $p['time_slot'], $p['pista_id']);
                $placed++;
            }
        });

        $skipped = count($bestAttempt['unplaced']);

        return [
            'ok'      => $bestAttempt['placed_all'],
            'placed'  => $placed,
            'skipped' => $skipped,
            'message' => $bestAttempt['placed_all']
                ? "Se programaron {$placed} canchas."
                : "Se programaron {$placed} canchas; quedaron {$skipped} sin asignar. Revisa horarios y pistas disponibles, o asígnalas manualmente.",
            'unplaced_labels' => array_map(fn($c) => $c->label, $bestAttempt['unplaced']),
        ];
    }

    private function tryGenerate($todo, array $dates, array $slots, $pistas, array $alreadyOccupied): array
    {
        $shuffled = $todo->shuffle()->values();
        $occupied = $alreadyOccupied;
        $playerBusy = $this->buildPlayerBusyMap($todo->first()->jornada_id);

        $placements = [];
        $unplaced = [];

        foreach ($shuffled as $cancha) {
            $playerIds = $this->canchaPlayerIds($cancha);
            $placement = $this->findCellForCancha($playerIds, $dates, $slots, $pistas, $occupied, $playerBusy);

            if ($placement === null) {
                $unplaced[] = $cancha;
                continue;
            }

            $occupied["{$placement['date']}|{$placement['time_slot']}|{$placement['pista_id']}"] = true;
            foreach ($playerIds as $pid) {
                $playerBusy["{$pid}|{$placement['date']}|{$placement['time_slot']}"] = true;
            }
            $placements[] = [
                'cancha_id' => $cancha->id,
                'date'      => $placement['date'],
                'time_slot' => $placement['time_slot'],
                'pista_id'  => $placement['pista_id'],
            ];
        }

        return [
            'placements'       => $placements,
            'placements_count' => count($placements),
            'unplaced'         => $unplaced,
            'placed_all'       => empty($unplaced),
        ];
    }

    private function canchaPlayerIds(Cancha $cancha): array
    {
        $ids = $cancha->players->pluck('id')->all();
        if (empty($ids)) {
            // Pairs mode
            foreach ($cancha->pairs as $pair) {
                $ids[] = $pair->player_a_id;
                $ids[] = $pair->player_b_id;
            }
        }
        return $ids;
    }

    private function findCellForCancha(array $playerIds, array $dates, array $slots, $pistas, array $occupied, array $playerBusy): ?array
    {
        $shuffledDates  = collect($dates)->shuffle()->values();
        $shuffledPistas = $pistas->shuffle()->values();
        $shuffledSlots  = collect($slots)->shuffle()->values();

        foreach ($shuffledDates as $date) {
            $dateStr = $date->toDateString();
            foreach ($shuffledPistas as $pista) {
                foreach ($shuffledSlots as $slot) {
                    $cellKey = "{$dateStr}|{$slot}|{$pista->id}";
                    if (isset($occupied[$cellKey])) continue;

                    $playersOk = true;
                    foreach ($playerIds as $pid) {
                        if (isset($playerBusy["{$pid}|{$dateStr}|{$slot}"])) {
                            $playersOk = false;
                            break;
                        }
                    }
                    if (!$playersOk) continue;

                    return ['date' => $dateStr, 'time_slot' => $slot, 'pista_id' => $pista->id];
                }
            }
        }
        return null;
    }

    private function buildOccupancyMap(Jornada $jornada): array
    {
        $occupied = [];
        foreach ($jornada->canchas as $c) {
            if (!$c->isScheduled()) continue;
            $occupied["{$c->date->toDateString()}|{$c->time_slot}|{$c->pista_id}"] = true;
        }
        return $occupied;
    }

    private function buildPlayerBusyMap(int $jornadaId): array
    {
        $busy = [];
        $canchas = Cancha::where('jornada_id', $jornadaId)
            ->whereNotNull('date')
            ->whereNotNull('time_slot')
            ->with(['players', 'pairs'])
            ->get();

        foreach ($canchas as $c) {
            $playerIds = $c->players->pluck('id')->all();
            if (empty($playerIds)) {
                foreach ($c->pairs as $pair) {
                    $playerIds[] = $pair->player_a_id;
                    $playerIds[] = $pair->player_b_id;
                }
            }
            foreach ($playerIds as $pid) {
                $busy["{$pid}|{$c->date->toDateString()}|{$c->time_slot}"] = true;
            }
        }
        return $busy;
    }
}
