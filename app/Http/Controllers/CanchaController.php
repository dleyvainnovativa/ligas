<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\GuardsFrozenJornadas;
use App\Models\Cancha;
use App\Models\Group;
use App\Models\Jornada;
use App\Models\League;
use App\Models\Pair;
use App\Models\Player;
use App\Services\CanchaService;
use App\Services\PublicCacheService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CanchaController extends Controller
{
    use GuardsFrozenJornadas;

    public function __construct(private CanchaService $canchas) {}

    public function store(Request $request, League $league, Group $group, Jornada $jornada)
    {
        $this->authorize('update', $jornada);
        abort_unless($jornada->group_id === $group->id && $group->league_id === $league->id, 404);
        $this->ensureJornadaEditable($jornada);

        $position = ($jornada->canchas()->max('position') ?? 0) + 1;
        $cancha = $jornada->canchas()->create([
            'label'    => "Cancha {$position}",
            'position' => $position,
        ]);

        return response()->json(['cancha' => $this->serialize($cancha)]);
    }

    public function update(Request $request, League $league, Group $group, Jornada $jornada, Cancha $cancha)
    {
        $this->authorize('update', $cancha);
        abort_unless($cancha->jornada_id === $jornada->id, 404);
        $this->ensureJornadaEditable($jornada);

        $data = $request->validate(['label' => ['required', 'string', 'max:80']]);
        $cancha->update($data);

        return response()->json(['cancha' => $this->serialize($cancha->fresh())]);
    }

    public function destroy(League $league, Group $group, Jornada $jornada, Cancha $cancha)
    {
        $this->authorize('delete', $cancha);
        abort_unless($cancha->jornada_id === $jornada->id, 404);
        $this->ensureJornadaEditable($jornada);

        $cancha->delete();
        return response()->json(['ok' => true]);
    }

    /** Assign a player or pair into a specific cancha (or unassign if cancha=0). */
    public function assign(Request $request, League $league, Group $group, Jornada $jornada)
    {
        $this->authorize('update', $jornada);
        abort_unless($jornada->group_id === $group->id && $group->league_id === $league->id, 404);
        $this->ensureJornadaEditable($jornada);

        // ---------- PAIRS ----------
        if ($league->format === League::FORMAT_PAIRS) {
            $data = $request->validate([
                'pair_id'       => ['required', 'integer'],
                'cancha_id'     => ['nullable', 'integer'],
                'confirm_reset' => ['nullable', 'boolean'],
            ]);
            $pair = $league->pairs()->findOrFail($data['pair_id']);

            // Unassign
            if (empty($data['cancha_id'])) {
                $affected = $this->canchas->affectedByUnassignPair($jornada, $pair);
                if ($guard = $this->maybeConfirm($affected, $data)) return $guard;

                DB::transaction(function () use ($affected, $pair, $jornada) {
                    foreach ($affected as $c) $this->canchas->resetCanchaScheduleAndResults($c);
                    $this->canchas->unassignPair($pair, $jornada);
                });
                app(PublicCacheService::class)->bust($league);
                return response()->json(['ok' => true]);
            }

            // Assign into a cancha
            $cancha = $jornada->canchas()->findOrFail($data['cancha_id']);
            $affected = $this->canchas->affectedByAssignPair($cancha, $pair);
            if ($guard = $this->maybeConfirm($affected, $data)) return $guard;

            $slot = null;
            DB::transaction(function () use ($affected, $cancha, $pair, &$slot) {
                foreach ($affected as $c) $this->canchas->resetCanchaScheduleAndResults($c);
                $slot = $this->canchas->assignPair($cancha, $pair);
            });
            app(PublicCacheService::class)->bust($league);
            return response()->json(['ok' => true, 'slot' => $slot]);
        }

        // ---------- INDIVIDUAL ----------
        $data = $request->validate([
            'player_id'      => ['required', 'integer'],
            'cancha_id'      => ['nullable', 'integer'],
            'preferred_slot' => ['nullable', 'integer', 'between:1,4'],
            'confirm_reset'  => ['nullable', 'boolean'],
        ]);
        $player = $league->players()->findOrFail($data['player_id']);

        // Unassign
        if (empty($data['cancha_id'])) {
            $affected = $this->canchas->affectedByUnassignPlayer($jornada, $player);
            if ($guard = $this->maybeConfirm($affected, $data)) return $guard;

            $touched = $this->canchas->touchedByUnassignPlayer($jornada, $player);   // ← before

            DB::transaction(function () use ($affected, $player, $jornada, $touched) {
                foreach ($affected as $c) $this->canchas->resetCanchaScheduleAndResults($c);
                $this->canchas->unassignPlayer($player, $jornada);

                $scheduler = app(\App\Services\MatchSchedulingService::class);
                foreach ($touched as $c) $scheduler->rebuildRounds($c->fresh());   // ← after
            });

            app(PublicCacheService::class)->bust($league);
            return response()->json(['ok' => true]);
        }

        // Assign into a cancha
        $cancha = $jornada->canchas()->findOrFail($data['cancha_id']);
        $affected = $this->canchas->affectedByAssignPlayer($cancha, $player);
        if ($guard = $this->maybeConfirm($affected, $data)) return $guard;

        $touched = $this->canchas->touchedByAssignPlayer($cancha, $player);   // ← before

        $slot = null;
        DB::transaction(function () use ($affected, $cancha, $player, $data, &$slot, $touched) {
            foreach ($affected as $c) $this->canchas->resetCanchaScheduleAndResults($c);
            $slot = $this->canchas->assignPlayer($cancha, $player, $data['preferred_slot'] ?? null);

            $scheduler = app(\App\Services\MatchSchedulingService::class);
            foreach ($touched as $c) $scheduler->rebuildRounds($c->fresh());   // ← after
        });

        app(PublicCacheService::class)->bust($league);
        return response()->json(['ok' => true, 'slot' => $slot]);
    }

    public function swap(Request $request, League $league, Group $group, Jornada $jornada)
    {
        $this->authorize('update', $jornada);
        abort_unless($jornada->group_id === $group->id && $group->league_id === $league->id, 404);
        $this->ensureJornadaEditable($jornada);

        if ($league->format === League::FORMAT_PAIRS) {
            $data = $request->validate([
                'source_pair_id' => ['required', 'integer'],
                'target_pair_id' => ['required', 'integer', 'different:source_pair_id'],
                'confirm_reset'  => ['nullable', 'boolean'],
            ]);
            $source = $league->pairs()->findOrFail($data['source_pair_id']);
            $target = $league->pairs()->findOrFail($data['target_pair_id']);

            $affected = $this->canchas->affectedBySwapPlayers($jornada, $source, $target);
            if ($guard = $this->maybeConfirm($affected, $data)) return $guard;

            DB::transaction(function () use ($affected, $jornada, $source, $target) {
                foreach ($affected as $c) $this->canchas->resetCanchaScheduleAndResults($c);
                $this->canchas->swapPlayers($jornada, $source, $target);
            });
            app(PublicCacheService::class)->bust($league);
            return response()->json(['ok' => true]);
        }

        $data = $request->validate([
            'source_player_id' => ['required', 'integer'],
            'target_player_id' => ['required', 'integer', 'different:source_player_id'],
            'confirm_reset'    => ['nullable', 'boolean'],
        ]);
        $source = $league->players()->findOrFail($data['source_player_id']);
        $target = $league->players()->findOrFail($data['target_player_id']);

        $affected = $this->canchas->affectedBySwapPlayers($jornada, $source, $target);
        if ($guard = $this->maybeConfirm($affected, $data)) return $guard;

        $touched = $this->canchas->touchedBySwapPlayers($jornada, $source, $target);   // ← before

        DB::transaction(function () use ($affected, $jornada, $source, $target, $touched) {
            foreach ($affected as $c) $this->canchas->resetCanchaScheduleAndResults($c);
            $this->canchas->swapPlayers($jornada, $source, $target);

            $scheduler = app(\App\Services\MatchSchedulingService::class);
            foreach ($touched as $c) $scheduler->rebuildRounds($c->fresh());   // ← after
        });

        app(PublicCacheService::class)->bust($league);
        return response()->json(['ok' => true]);
    }

    /**
     * If any affected canchas need resetting and the request isn't confirmed,
     * return a 422 asking the frontend to confirm. Otherwise null (proceed).
     */
    private function maybeConfirm(\Illuminate\Support\Collection $affected, array $data)
    {
        if ($affected->isEmpty() || ($data['confirm_reset'] ?? false)) {
            return null;
        }

        return response()->json([
            'needs_confirmation' => true,
            'message'            => $this->buildResetWarning($affected),
            'affected_canchas'   => $affected->map(fn($c) => [
                'id'          => $c->id,
                'label'       => $c->label,
                'had_results' => $c->rounds()->whereNotNull('sets')->exists(),
            ])->values(),
        ], 422);
    }

    private function buildResetWarning(\Illuminate\Support\Collection $affected): string
    {
        $withResults = $affected->filter(fn($c) => $c->rounds()->whereNotNull('sets')->exists());
        $count = $affected->count();

        $base = $count === 1
            ? "Este cambio reprogramará 1 cancha"
            : "Este cambio reprogramará {$count} canchas";

        if ($withResults->isNotEmpty()) {
            $rc = $withResults->count();
            $base .= $rc === 1
                ? " (1 con resultados que se borrarán)"
                : " ({$rc} con resultados que se borrarán)";
        }

        return $base . ". Deberás volver a programar el horario y la pista. ¿Continuar?";
    }

    private function serialize(Cancha $c): array
    {
        return [
            'id'       => $c->id,
            'label'    => $c->label,
            'position' => $c->position,
        ];
    }
}
